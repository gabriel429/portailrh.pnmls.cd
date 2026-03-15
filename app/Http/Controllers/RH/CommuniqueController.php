<?php

namespace App\Http\Controllers\RH;

use App\Http\Controllers\Controller;
use App\Models\Communique;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CommuniqueController extends Controller
{
    public function index(): View
    {
        $communiques = Communique::with('auteur')
            ->latest()
            ->paginate(15);

        return view('rh.communiques.index', compact('communiques'));
    }

    public function create(): View
    {
        return view('rh.communiques.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'titre' => 'required|string|max:255',
            'contenu' => 'required|string',
            'urgence' => 'required|in:normal,important,urgent',
            'signataire' => 'nullable|string|max:255',
            'date_expiration' => 'nullable|date|after_or_equal:today',
            'actif' => 'nullable|boolean',
        ]);

        $validated['auteur_id'] = auth()->id();
        $validated['actif'] = $request->has('actif');

        Communique::create($validated);

        return redirect()->route('rh.communiques.index')
            ->with('success', 'Communiqué publié avec succès');
    }

    public function show(Communique $communique): View
    {
        $communique->load('auteur');
        return view('rh.communiques.index', ['communiques' => Communique::with('auteur')->latest()->paginate(15)]);
    }

    public function edit(Communique $communique): View
    {
        return view('rh.communiques.create', compact('communique'));
    }

    public function update(Request $request, Communique $communique): RedirectResponse
    {
        $validated = $request->validate([
            'titre' => 'required|string|max:255',
            'contenu' => 'required|string',
            'urgence' => 'required|in:normal,important,urgent',
            'signataire' => 'nullable|string|max:255',
            'date_expiration' => 'nullable|date',
            'actif' => 'nullable|boolean',
        ]);

        $validated['actif'] = $request->has('actif');

        $communique->update($validated);

        return redirect()->route('rh.communiques.index')
            ->with('success', 'Communiqué mis à jour avec succès');
    }

    public function destroy(Communique $communique): RedirectResponse
    {
        $communique->delete();

        return redirect()->route('rh.communiques.index')
            ->with('success', 'Communiqué supprimé');
    }

    public function showPublic(Communique $communique): View
    {
        $communique->load('auteur');
        return view('communiques.show-public', compact('communique'));
    }
}
