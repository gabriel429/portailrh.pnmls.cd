<?php

namespace App\Http\Controllers;

use App\Models\CategorieDocument;
use App\Models\DocumentTravail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentTravailController extends Controller
{
    public function index(Request $request): View
    {
        $categorie = $request->query('categorie');

        $categoriesDB = CategorieDocument::actives()->orderBy('nom')->get();

        // Count documents per category
        $categoryCounts = DocumentTravail::actifs()
            ->selectRaw('categorie, COUNT(*) as total')
            ->groupBy('categorie')
            ->pluck('total', 'categorie');

        $totalDocs = DocumentTravail::actifs()->count();

        // If a category is selected, filter documents
        $documentsQuery = DocumentTravail::actifs()->latest();
        if ($categorie) {
            $documentsQuery->where('categorie', $categorie);
        }
        $documents = $documentsQuery->paginate(20)->appends(['categorie' => $categorie]);

        return view('documents-travail.index', compact(
            'documents', 'categoriesDB', 'categoryCounts', 'totalDocs', 'categorie'
        ));
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
