<?php

namespace App\Services;

use DOMDocument;
use DOMElement;
use DOMXPath;
use InvalidArgumentException;
use ZipArchive;

class PtaDocxImportReader
{
    public function read(string $path, ?int $annee = null): array
    {
        if (!is_file($path)) {
            throw new InvalidArgumentException('Fichier PTA introuvable.');
        }

        $xml = $this->readDocumentXml($path);
        $document = new DOMDocument();
        $document->preserveWhiteSpace = false;

        if (!@$document->loadXML($xml)) {
            throw new InvalidArgumentException('Le document PTA est invalide.');
        }

        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

        $tables = $xpath->query('//w:tbl');
        if ($tables === false || $tables->length === 0) {
            throw new InvalidArgumentException('Aucun tableau PTA exploitable n\'a ete trouve dans le document.');
        }

        $annee = $annee ?? $this->extractYear($xpath) ?? now()->year;
        $records = [];

        foreach ($tables as $index => $tableNode) {
            if (!$tableNode instanceof DOMElement) {
                continue;
            }

            $rows = $this->extractTableRows($xpath, $tableNode);
            if ($rows === []) {
                continue;
            }

            $header = implode(' | ', array_map('mb_strtoupper', $rows[0]));

            if (str_contains($header, 'PROVINCES')) {
                $records = array_merge($records, $this->parseSepTable($rows, $annee));
                continue;
            }

            if (str_contains($header, 'COÛT EN CDF') || str_contains($header, 'COUT EN CDF')) {
                $records = array_merge($records, $this->parseSenTable($rows, $annee));
                continue;
            }

            if ($index === 0) {
                $records = array_merge($records, $this->parseIntroTable($rows, $annee));
            }
        }

        return $records;
    }

    private function readDocumentXml(string $path): string
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new InvalidArgumentException('Impossible d\'ouvrir le document PTA.');
        }

        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        if ($xml === false) {
            throw new InvalidArgumentException('Impossible de lire word/document.xml dans le document PTA.');
        }

        return $xml;
    }

    private function extractYear(DOMXPath $xpath): ?int
    {
        $texts = $xpath->query('//w:p');
        if ($texts === false) {
            return null;
        }

        foreach ($texts as $paragraph) {
            $text = $this->normalizeText($paragraph->textContent ?? '');
            if (preg_match('/Activités prioritaires\s+(20\d{2})/iu', $text, $matches)) {
                return (int) $matches[1];
            }
        }

        return null;
    }

    private function extractTableRows(DOMXPath $xpath, DOMElement $tableNode): array
    {
        $rows = [];
        $rowNodes = $xpath->query('./w:tr', $tableNode);

        if ($rowNodes === false) {
            return [];
        }

        foreach ($rowNodes as $rowNode) {
            if (!$rowNode instanceof DOMElement) {
                continue;
            }

            $cells = [];
            $cellNodes = $xpath->query('./w:tc', $rowNode);

            if ($cellNodes === false) {
                continue;
            }

            foreach ($cellNodes as $cellNode) {
                if (!$cellNode instanceof DOMElement) {
                    $cells[] = '';
                    continue;
                }

                $cells[] = $this->extractCellValue($xpath, $cellNode);
            }

            if ($this->isEmptyRow($cells)) {
                continue;
            }

            $rows[] = $cells;
        }

        return $rows;
    }

    private function extractCellValue(DOMXPath $xpath, DOMElement $cellNode): string
    {
        $text = $this->normalizeText($cellNode->textContent ?? '');
        if ($text !== '') {
            return $text;
        }

        return $this->hasQuarterCellFill($xpath, $cellNode) ? 'x' : '';
    }

    private function hasQuarterCellFill(DOMXPath $xpath, DOMElement $cellNode): bool
    {
        $shadingNodes = $xpath->query('./w:tcPr/w:shd', $cellNode);
        if ($shadingNodes === false || $shadingNodes->length === 0) {
            return false;
        }

        foreach ($shadingNodes as $shadingNode) {
            if (!$shadingNode instanceof DOMElement) {
                continue;
            }

            $fill = strtoupper(trim((string) $shadingNode->getAttribute('w:fill')));
            $themeFill = strtoupper(trim((string) $shadingNode->getAttribute('w:themeFill')));

            if ($themeFill !== '') {
                return true;
            }

            if ($fill !== '' && !in_array($fill, ['AUTO', 'FFFFFF', 'FFF'], true)) {
                return true;
            }
        }

        return false;
    }

    private function parseIntroTable(array $rows, int $annee): array
    {
        $records = [];

        foreach (array_slice($rows, 1) as $cells) {
            if (!$this->looksLikeActivityCode($cells[0] ?? null)) {
                continue;
            }

            $records[] = [
                'source_table' => 'intro',
                'code' => $cells[0] ?? null,
                'titre' => $cells[1] ?? null,
                'resultat_attendu' => $cells[2] ?? null,
                'categorie' => null,
                'responsable_code' => $cells[3] ?? null,
                'cout_cdf' => null,
                'niveau_administratif' => 'SEN',
                'validation_niveau' => 'coordination_nationale',
                'annee' => $annee,
                'trimestre' => null,
                'trimestre_1' => false,
                'trimestre_2' => false,
                'trimestre_3' => false,
                'trimestre_4' => false,
                'province_names' => [],
            ];
        }

        return $records;
    }

    private function parseSenTable(array $rows, int $annee): array
    {
        $records = [];
        $currentCategory = null;

        foreach (array_slice($rows, 1) as $cells) {
            if ($this->isSingleValueRow($cells) && !$this->looksLikeActivityCode($cells[0] ?? null)) {
                $currentCategory = $this->normalizeCategory($cells[0] ?? '');
                continue;
            }

            if ($this->isSubtotalRow($cells) || !$this->looksLikeActivityCode($cells[0] ?? null)) {
                continue;
            }

            $quarterFlags = $this->extractQuarterFlags(array_slice($cells, 5, 4));

            $records[] = [
                'source_table' => 'sen',
                'code' => $cells[0] ?? null,
                'titre' => $cells[1] ?? null,
                'resultat_attendu' => $cells[2] ?? null,
                'categorie' => $currentCategory,
                'responsable_code' => $cells[3] ?? null,
                'cout_cdf' => $this->parseAmount($cells[4] ?? null),
                'niveau_administratif' => 'SEN',
                'validation_niveau' => 'coordination_nationale',
                'annee' => $annee,
                'trimestre' => $this->primaryQuarter($quarterFlags),
                'trimestre_1' => $quarterFlags['trimestre_1'],
                'trimestre_2' => $quarterFlags['trimestre_2'],
                'trimestre_3' => $quarterFlags['trimestre_3'],
                'trimestre_4' => $quarterFlags['trimestre_4'],
                'province_names' => [],
            ];
        }

        return $records;
    }

    private function parseSepTable(array $rows, int $annee): array
    {
        $records = [];
        $currentCategory = null;

        foreach (array_slice($rows, 2) as $cells) {
            if ($this->isSingleValueRow($cells) && !str_starts_with((string) ($cells[0] ?? ''), 'Act.')) {
                $currentCategory = $this->normalizeCategory($cells[0] ?? '');
                continue;
            }

            if ($this->isSubtotalRow($cells) || !str_starts_with((string) ($cells[0] ?? ''), 'Act.')) {
                continue;
            }

            $quarterFlags = $this->extractQuarterFlags(array_slice($cells, 4, 4));

            $records[] = [
                'source_table' => 'sep',
                'code' => $cells[0] ?? null,
                'titre' => $cells[1] ?? null,
                'resultat_attendu' => null,
                'categorie' => $currentCategory,
                'responsable_code' => 'SEP',
                'cout_cdf' => $this->parseAmount($cells[3] ?? null),
                'niveau_administratif' => 'SEP',
                'validation_niveau' => 'coordination_provinciale',
                'annee' => $annee,
                'trimestre' => $this->primaryQuarter($quarterFlags),
                'trimestre_1' => $quarterFlags['trimestre_1'],
                'trimestre_2' => $quarterFlags['trimestre_2'],
                'trimestre_3' => $quarterFlags['trimestre_3'],
                'trimestre_4' => $quarterFlags['trimestre_4'],
                'province_names' => $this->extractProvinceNames($cells[2] ?? ''),
            ];
        }

        return $records;
    }

    private function extractQuarterFlags(array $cells): array
    {
        return [
            'trimestre_1' => $this->hasQuarterMark($cells[0] ?? null),
            'trimestre_2' => $this->hasQuarterMark($cells[1] ?? null),
            'trimestre_3' => $this->hasQuarterMark($cells[2] ?? null),
            'trimestre_4' => $this->hasQuarterMark($cells[3] ?? null),
        ];
    }

    private function primaryQuarter(array $flags): ?string
    {
        foreach (['trimestre_1' => 'T1', 'trimestre_2' => 'T2', 'trimestre_3' => 'T3', 'trimestre_4' => 'T4'] as $field => $label) {
            if (!empty($flags[$field])) {
                return $label;
            }
        }

        return null;
    }

    private function hasQuarterMark(?string $value): bool
    {
        $normalized = $this->normalizeText((string) $value);
        return $normalized !== '';
    }

    private function extractProvinceNames(string $value): array
    {
        $normalized = $this->normalizeText($value);
        if ($normalized === '') {
            return [];
        }

        $normalized = str_replace([',', ';', '/'], ' ', $normalized);
        $compact = preg_replace('/\s+/', ' ', $normalized) ?? $normalized;

        return [$compact];
    }

    private function normalizeCategory(string $value): string
    {
        $value = $this->normalizeText($value);
        $value = preg_replace('/^\d+(?:\.\d+)?\.?\s*/u', '', $value) ?? $value;
        $value = preg_replace('/\(SEP\)/iu', '', $value) ?? $value;
        $value = trim($value, " .\t\n\r\0\x0B");

        return $value;
    }

    private function looksLikeActivityCode(?string $value): bool
    {
        if ($value === null) {
            return false;
        }

        $value = $this->normalizeText($value);
        return (bool) preg_match('/^(?:Act\.)?\d+(?:\.\d+)?$/iu', $value);
    }

    private function isSingleValueRow(array $cells): bool
    {
        return count(array_filter($cells, fn (?string $cell) => $this->normalizeText((string) $cell) !== '')) === 1;
    }

    private function isSubtotalRow(array $cells): bool
    {
        $joined = mb_strtoupper(implode(' ', $cells));
        return str_contains($joined, 'SOUS TOTAL') || str_contains($joined, 'TOTAL');
    }

    private function parseAmount(?string $value): ?float
    {
        $value = $this->normalizeText((string) $value);
        if ($value === '') {
            return null;
        }

        $numeric = preg_replace('/[^\d]/', '', $value);
        if ($numeric === null || $numeric === '') {
            return null;
        }

        return (float) $numeric;
    }

    private function normalizeText(string $value): string
    {
        $value = preg_replace('/\x{00A0}/u', ' ', $value) ?? $value;
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return trim($value);
    }

    private function isEmptyRow(array $cells): bool
    {
        foreach ($cells as $cell) {
            if ($this->normalizeText((string) $cell) !== '') {
                return false;
            }
        }

        return true;
    }
}