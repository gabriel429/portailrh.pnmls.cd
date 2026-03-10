<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    /**
     * Display list of documents
     */
    public function index()
    {
        $agent = auth()->user();
        $documents = $agent->documents()->paginate(10);
        return view('documents.index', compact('documents', 'agent'));
    }

    /**
     * Show upload form
     */
    public function create()
    {
        return view('documents.create');
    }

    /**
     * Store uploaded document
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nom_fichier' => 'required|string|max:255',
            'categorie' => 'required|in:identite,parcours,carriere,mission',
            'description' => 'nullable|string',
            'fichier' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        if ($request->hasFile('fichier')) {
            $file = $request->file('fichier');
            $path = $file->store('documents', 'public');

            $document = Document::create([
                'agent_id' => auth()->user()->id,
                'nom_fichier' => $validated['nom_fichier'],
                'categorie' => $validated['categorie'],
                'description' => $validated['description'] ?? null,
                'chemin_fichier' => $path,
                'type_fichier' => $file->extension(),
                'taille' => $file->getSize(),
                'uploaded_by' => auth()->user()->id,
            ]);

            return redirect()->route('documents.index')
                ->with('success', 'Document uploadé avec succès');
        }

        return back()->with('error', 'Erreur lors de l\'upload');
    }

    /**
     * Show document details
     */
    public function show(Document $document)
    {
        // Vérifier les droits d'accès
        if ($document->agent_id !== auth()->user()->id && !auth()->user()->hasRole('Chef Section RH')) {
            abort(403);
        }

        return view('documents.show', compact('document'));
    }

    /**
     * Download document
     */
    public function download(Document $document)
    {
        // Vérifier les droits d'accès
        if ($document->agent_id !== auth()->user()->id && !auth()->user()->hasRole('Chef Section RH')) {
            abort(403);
        }

        return Storage::disk('public')->download($document->chemin_fichier, $document->nom_fichier . '.' . $document->type_fichier);
    }

    /**
     * Delete document
     */
    public function destroy(Document $document)
    {
        // Seulement Chef Section RH peut supprimer
        if (!auth()->user()->hasRole('Chef Section RH')) {
            abort(403);
        }

        Storage::disk('public')->delete($document->chemin_fichier);
        $document->delete();

        return redirect()->route('documents.index')
            ->with('success', 'Document supprimé');
    }
}
