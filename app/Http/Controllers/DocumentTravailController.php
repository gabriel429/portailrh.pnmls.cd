<?php

namespace App\Http\Controllers;

use App\Models\DocumentTravail;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class DocumentTravailController extends Controller
{
    public function view(DocumentTravail $documentTravail): Response
    {
        $file = $this->resolveFile($documentTravail);

        if (!$file) {
            abort(404, 'Fichier introuvable');
        }

        $fileName = $documentTravail->titre . '.' . $documentTravail->type_fichier;

        if ($file['disk']) {
            return Storage::disk('public')->response($file['path'], $fileName, [
                'Content-Disposition' => 'inline; filename="' . $fileName . '"',
            ]);
        }

        return response()->file($file['path'], [
            'Content-Disposition' => 'inline; filename="' . $fileName . '"',
        ]);
    }

    public function download(DocumentTravail $documentTravail): Response
    {
        $file = $this->resolveFile($documentTravail);

        if (!$file) {
            abort(404, 'Fichier introuvable');
        }

        $fileName = $documentTravail->titre . '.' . $documentTravail->type_fichier;

        return $file['disk']
            ? Storage::disk('public')->download($file['path'], $fileName)
            : response()->download($file['path'], $fileName);
    }

    private function resolveFile(DocumentTravail $documentTravail): ?array
    {
        $path = ltrim((string) $documentTravail->fichier, '/');
        $paths = array_values(array_unique([
            $path,
            str_replace('documents_travail/', 'documents-travail/', $path),
            str_replace('documents-travail/', 'documents_travail/', $path),
        ]));

        foreach ($paths as $candidate) {
            if (Storage::disk('public')->exists($candidate)) {
                return ['disk' => true, 'path' => $candidate];
            }
        }

        foreach ($paths as $candidate) {
            foreach ([public_path($candidate), public_path('storage/' . $candidate)] as $absolutePath) {
                if (is_file($absolutePath)) {
                    return ['disk' => false, 'path' => $absolutePath];
                }
            }
        }

        return null;
    }
}
