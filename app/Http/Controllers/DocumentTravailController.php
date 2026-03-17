<?php

namespace App\Http\Controllers;

use App\Models\DocumentTravail;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentTravailController extends Controller
{
    public function index(): View
    {
        $documents = DocumentTravail::actifs()
            ->latest()
            ->paginate(20);

        $categories = DocumentTravail::actifs()
            ->select('categorie')
            ->distinct()
            ->pluck('categorie');

        return view('documents-travail.index', compact('documents', 'categories'));
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
