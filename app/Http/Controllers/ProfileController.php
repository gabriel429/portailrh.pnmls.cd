<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    /**
     * Display dashboard/index
     */
    public function index(): View
    {
        $agent = auth()->user();
        return view('dashboard', compact('agent'));
    }

    /**
     * Display the user's profile.
     */
    public function show(Agent $agent): View
    {
        return view('profile.show', compact('agent'));
    }

    /**
     * Show the form for editing the profile.
     */
    public function edit(Agent $agent): View
    {
        // Vérifier que l'agent édite son propre profil
        if (auth()->user()->id !== $agent->id && !auth()->user()->hasRole('Chef Section RH')) {
            abort(403);
        }
        return view('profile.edit', compact('agent'));
    }

    /**
     * Update the profile.
     */
    public function update(Request $request, Agent $agent): RedirectResponse
    {
        // Vérifier que l'agent édite son propre profil
        if (auth()->user()->id !== $agent->id && !auth()->user()->hasRole('Chef Section RH')) {
            abort(403);
        }

        $validated = $request->validate([
            'prenom' => 'required|string',
            'nom' => 'required|string',
            'email' => 'required|email|unique:agents,email,' . $agent->id,
            'telephone' => 'nullable|string',
            'adresse' => 'nullable|string',
            'photo' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('photo')) {
            $file = $request->file('photo');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('uploads/profiles'), $filename);
            $validated['photo'] = 'uploads/profiles/' . $filename;
        }

        $agent->update($validated);

        return redirect()->route('profile.show', $agent)
            ->with('success', 'Profil mis à jour avec succès');
    }

    /**
     * Show the change password form.
     */
    public function editPassword(): View
    {
        return view('profile.change-password');
    }

    /**
     * Update the password.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => 'required|current_password',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $agent = auth()->user();
        $agent->update([
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()->route('profile.show', $agent)
            ->with('success', 'Mot de passe modifié avec succès');
    }

    /**
     * Get profile via API
     */
    public function apiShow()
    {
        return response()->json([
            'user' => auth()->user(),
        ]);
    }
}
