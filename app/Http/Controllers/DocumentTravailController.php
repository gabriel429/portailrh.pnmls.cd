<?php

namespace App\Http\Controllers;

use App\Models\DocumentTravail;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentTravailController extends Controller
{
    public function view(DocumentTravail $documentTravail): StreamedResponse
    {
        if (!Storage::disk('public')->exists($documentTravail->fichier)) {
            abort(404, 'Fichier introuvable');
        }

        $fileName = $documentTravail->titre . '.' . $documentTravail->type_fichier;

        return Storage::disk('public')->response($documentTravail->fichier, $fileName, [
            'Content-Disposition' => 'inline; filename="' . $fileName . '"',
        ]);
    }

    public function download(DocumentTravail $documentTravail): StreamedResponse
    {
        if (!Storage::disk('public')->exists($documentTravail->fichier)) {
            abort(404, 'Fichier introuvable');
        }

        $fileName = $documentTravail->titre . '.' . $documentTravail->type_fichier;

        return Storage::disk('public')->download($documentTravail->fichier, $fileName);
    }
}
