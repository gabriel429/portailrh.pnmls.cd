<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Agent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    /**
     * Display list of documents
     */
    public function index()
    {
        $agent = auth()->user()->agent;

        if (!$agent) {
            return view('documents.index', ['documents' => collect(), 'agent' => null]);
        }

        $documents = $agent->documents()->paginate(10);
        return view('documents.index', compact('documents', 'agent'));
    }

    /**
     * Show upload form
     */
    public function create(Request $request)
    {
        $agent_id = $request->query('agent_id');
        $agent = null;

        if ($agent_id) {
            $agent = Agent::find($agent_id);
        }

        return view('documents.create', compact('agent'));
    }

    /**
     * Store uploaded document
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nom_document' => 'required|string|max:255',
            'type' => 'required|in:identite,parcours,carriere,mission',
            'description' => 'nullable|string',
            'agent_id' => 'nullable|exists:agents,id',
            'fichier' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        // Determine which agent the document belongs to
        $agent_id = $validated['agent_id'] ?? auth()->user()->agent?->id;

        // Only allow RH staff or the agent themselves to upload
        if ($agent_id !== auth()->user()->agent?->id && !auth()->user()->hasAdminAccess()) {
            abort(403, 'Vous n\'avez pas les droits pour créer ce document');
        }

        if ($request->hasFile('fichier')) {
            $file = $request->file('fichier');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('uploads/documents'), $filename);

            $document = Document::create([
                'agent_id' => $agent_id,
                'type' => $validated['type'],
                'fichier' => 'uploads/documents/' . $filename,
                'description' => ($validated['nom_document'] ?? '') . (!empty($validated['description']) ? ' | ' . $validated['description'] : ''),
                'statut' => 'valide',
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
        if ($document->agent_id !== auth()->user()->agent?->id && !auth()->user()->hasAdminAccess()) {
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
        if ($document->agent_id !== auth()->user()->agent?->id && !auth()->user()->hasAdminAccess()) {
            abort(403);
        }

        $filePath = public_path($document->fichier);
        if (file_exists($filePath)) {
            return response()->download($filePath);
        }
        return back()->with('error', 'Fichier introuvable');
    }

    /**
     * Delete document
     */
    public function destroy(Document $document)
    {
        // Seulement les profils admin RH peuvent supprimer
        if (!auth()->user()->hasAdminAccess()) {
            abort(403);
        }

        $filePath = public_path($document->fichier);
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        $document->delete();

        return redirect()->route('documents.index')
            ->with('success', 'Document supprimé');
    }
}
