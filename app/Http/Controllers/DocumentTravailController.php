<?php

namespace App\Http\Controllers;

use App\Models\DocumentTravail;
use Illuminate\View\View;

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
}
