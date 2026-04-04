<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use InvalidArgumentException;
use ZipArchive;

class SpreadsheetImportReader
{
    public function read(UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension());

        return match ($extension) {
            'csv', 'txt' => $this->readCsv($file->getRealPath()),
            'xlsx' => $this->readXlsx($file->getRealPath()),
            default => throw new InvalidArgumentException('Format de fichier non supporte. Utilisez un fichier .xlsx ou .csv.'),
        };
    }

    private function readCsv(string $path): array
    {
        $handle = fopen($path, 'rb');
        if (!$handle) {
            throw new InvalidArgumentException('Impossible de lire le fichier CSV fourni.');
        }

        $firstLine = fgets($handle);
        rewind($handle);

        $delimiter = $this->detectCsvDelimiter((string) $firstLine);
        $rows = [];

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $values = array_map([$this, 'normalizeCellValue'], $row);

            if ($this->isEmptyRow($values)) {
                continue;
            }

            $rows[] = $this->trimTrailingEmptyCells($values);
        }

        fclose($handle);

        return $rows;
    }

    private function readXlsx(string $path): array
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new InvalidArgumentException('Impossible d\'ouvrir le fichier Excel fourni.');
        }

        $sharedStrings = $this->readSharedStrings($zip);
        $sheetPath = $this->resolveFirstWorksheetPath($zip);
        $sheetXml = $zip->getFromName($sheetPath);

        if ($sheetXml === false) {
            $zip->close();
            throw new InvalidArgumentException('Impossible de lire la premiere feuille du fichier Excel.');
        }

        $xml = simplexml_load_string($sheetXml);
        if ($xml === false) {
            $zip->close();
            throw new InvalidArgumentException('Le contenu du fichier Excel est invalide.');
        }

        $xml->registerXPathNamespace('main', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $rows = [];

        foreach ($xml->xpath('//main:sheetData/main:row') as $rowNode) {
            $row = [];
            $expectedIndex = 0;

            foreach ($rowNode->xpath('main:c') as $cellNode) {
                $attributes = $cellNode->attributes();
                $cellRef = (string) ($attributes['r'] ?? '');
                $cellIndex = $cellRef !== '' ? $this->columnIndexFromCellReference($cellRef) : $expectedIndex;

                while ($expectedIndex < $cellIndex) {
                    $row[] = null;
                    $expectedIndex++;
                }

                $row[] = $this->extractCellValue($cellNode, $sharedStrings);
                $expectedIndex++;
            }

            $row = $this->trimTrailingEmptyCells($row);
            if (!$this->isEmptyRow($row)) {
                $rows[] = $row;
            }
        }

        $zip->close();

        return $rows;
    }

    private function readSharedStrings(ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/sharedStrings.xml');
        if ($xml === false) {
            return [];
        }

        $shared = simplexml_load_string($xml);
        if ($shared === false) {
            return [];
        }

        $shared->registerXPathNamespace('main', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $values = [];

        foreach ($shared->xpath('//main:si') as $item) {
            $textParts = $item->xpath('.//main:t');
            $text = '';

            foreach ($textParts as $part) {
                $text .= (string) $part;
            }

            $values[] = $this->normalizeCellValue($text);
        }

        return $values;
    }

    private function resolveFirstWorksheetPath(ZipArchive $zip): string
    {
        $workbookXml = $zip->getFromName('xl/workbook.xml');
        $relsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');

        if ($workbookXml === false || $relsXml === false) {
            return 'xl/worksheets/sheet1.xml';
        }

        $workbook = simplexml_load_string($workbookXml);
        $rels = simplexml_load_string($relsXml);

        if ($workbook === false || $rels === false) {
            return 'xl/worksheets/sheet1.xml';
        }

        $workbook->registerXPathNamespace('main', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $workbook->registerXPathNamespace('r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');
        $rels->registerXPathNamespace('rel', 'http://schemas.openxmlformats.org/package/2006/relationships');

        $sheetNodes = $workbook->xpath('//main:sheets/main:sheet');
        if (!$sheetNodes || !isset($sheetNodes[0])) {
            return 'xl/worksheets/sheet1.xml';
        }

        $relationshipId = (string) $sheetNodes[0]->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')['id'];
        foreach ($rels->xpath('//rel:Relationship') as $relationship) {
            $attributes = $relationship->attributes();
            if ((string) $attributes['Id'] === $relationshipId) {
                $target = ltrim((string) $attributes['Target'], '/');
                return str_starts_with($target, 'xl/') ? $target : 'xl/' . ltrim($target, '/');
            }
        }

        return 'xl/worksheets/sheet1.xml';
    }

    private function extractCellValue(\SimpleXMLElement $cellNode, array $sharedStrings): mixed
    {
        $attributes = $cellNode->attributes();
        $type = (string) ($attributes['t'] ?? '');
        $namespaces = $cellNode->getNamespaces(true);
        $mainNs = $namespaces[''] ?? 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
        $cellNode->registerXPathNamespace('main', $mainNs);

        if ($type === 's') {
            $indexNodes = $cellNode->xpath('main:v');
            $index = isset($indexNodes[0]) ? (int) $indexNodes[0] : -1;
            return $sharedStrings[$index] ?? null;
        }

        if ($type === 'inlineStr') {
            $textParts = $cellNode->xpath('.//main:t');
            $value = '';
            foreach ($textParts as $part) {
                $value .= (string) $part;
            }
            return $this->normalizeCellValue($value);
        }

        if ($type === 'b') {
            $valueNodes = $cellNode->xpath('main:v');
            return isset($valueNodes[0]) && (string) $valueNodes[0] === '1' ? '1' : '0';
        }

        $valueNodes = $cellNode->xpath('main:v');
        if (!isset($valueNodes[0])) {
            return null;
        }

        return $this->normalizeCellValue((string) $valueNodes[0]);
    }

    private function columnIndexFromCellReference(string $cellReference): int
    {
        $letters = preg_replace('/[^A-Z]/', '', strtoupper($cellReference));
        $index = 0;

        for ($i = 0; $i < strlen($letters); $i++) {
            $index = ($index * 26) + (ord($letters[$i]) - 64);
        }

        return max(0, $index - 1);
    }

    private function detectCsvDelimiter(string $line): string
    {
        $delimiters = [';', ',', "\t"];
        $bestDelimiter = ';';
        $bestCount = -1;

        foreach ($delimiters as $delimiter) {
            $count = count(str_getcsv($line, $delimiter));
            if ($count > $bestCount) {
                $bestDelimiter = $delimiter;
                $bestCount = $count;
            }
        }

        return $bestDelimiter;
    }

    private function normalizeCellValue(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            $value = preg_replace('/^\xEF\xBB\xBF/', '', $value);
            $value = trim($value);
            return $value === '' ? null : $value;
        }

        return $value;
    }

    private function trimTrailingEmptyCells(array $row): array
    {
        while (!empty($row) && end($row) === null) {
            array_pop($row);
        }

        return $row;
    }

    private function isEmptyRow(array $row): bool
    {
        foreach ($row as $value) {
            if ($value !== null && $value !== '') {
                return false;
            }
        }

        return true;
    }
}