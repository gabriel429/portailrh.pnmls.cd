<?php

namespace App\Http\Controllers\RH;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\Agent;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class DocumentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $documents = Document::with(['agent'])
            ->paginate(15);

        return view('rh.documents.index', compact('documents'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $agents = Agent::all();

        return view('rh.documents.create', compact('agents'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'agent_id' => 'required|exists:agents,id',
            'type' => 'required|string',
            'fichier' => 'required|file|max:5120',
            'description' => 'nullable|string',
            'date_expiration' => 'nullable|date',
        ]);

        if ($request->hasFile('fichier')) {
            $path = $request->file('fichier')->store('documents', 'public');
            $validated['fichier'] = $path;
        }

        Document::create($validated);

        return redirect()->route('rh.documents.index')
            ->with('success', 'Document créé avec succès');
    }

    /**
     * Display the specified resource.
     */
    public function show(Document $document): View
    {
        return view('rh.documents.show', compact('document'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Document $document): View
    {
        $agents = Agent::all();

        return view('rh.documents.edit', compact('document', 'agents'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Document $document): RedirectResponse
    {
        $validated = $request->validate([
            'type' => 'required|string',
            'description' => 'nullable|string',
            'date_expiration' => 'nullable|date',
            'statut' => 'required|in:valide,expiré,rejeté',
        ]);

        $document->update($validated);

        return redirect()->route('rh.documents.show', $document)
            ->with('success', 'Document modifié avec succès');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Document $document): RedirectResponse
    {
        $document->delete();

        return redirect()->route('rh.documents.index')
            ->with('success', 'Document supprimé avec succès');
    }
}
