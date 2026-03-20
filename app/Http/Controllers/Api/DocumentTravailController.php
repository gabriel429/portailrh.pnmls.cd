<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CategorieDocument;
use App\Models\DocumentTravail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentTravailController extends Controller
{
    /**
     * Display a listing of active documents de travail.
     */
    public function index(Request $request): JsonResponse
    {
        $categorie = $request->query('categorie');

        $categoriesDB = CategorieDocument::actives()->orderBy('nom')->get();

        $categoryCounts = DocumentTravail::actifs()
            ->selectRaw('categorie, COUNT(*) as total')
            ->groupBy('categorie')
            ->pluck('total', 'categorie');

        $totalDocs = DocumentTravail::actifs()->count();

        $documentsQuery = DocumentTravail::actifs()->latest();
        if ($categorie) {
            $documentsQuery->where('categorie', $categorie);
        }
        $documents = $documentsQuery->paginate(20);

        return response()->json([
            'data' => $documents->items(),
            'meta' => [
                'current_page' => $documents->currentPage(),
                'last_page' => $documents->lastPage(),
                'per_page' => $documents->perPage(),
                'total' => $documents->total(),
                'from' => $documents->firstItem(),
                'to' => $documents->lastItem(),
            ],
            'categories' => $categoriesDB,
            'categoryCounts' => $categoryCounts,
            'totalDocs' => $totalDocs,
            'categorie' => $categorie,
        ]);
    }

    /**
     * Download a document de travail.
     */
    public function download(DocumentTravail $doc): StreamedResponse
    {
        if (!Storage::disk('public')->exists($doc->fichier)) {
            abort(404, 'Fichier introuvable');
        }

        $fileName = $doc->titre . '.' . $doc->type_fichier;

        return Storage::disk('public')->download($doc->fichier, $fileName);
    }
}
