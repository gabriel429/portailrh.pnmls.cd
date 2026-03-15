<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\Message;
use App\Models\Pointage;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

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
    public function show(?Agent $agent = null): View
    {
        // If no agent passed via route, use the authenticated user's agent
        if (!$agent || !$agent->exists) {
            $user = auth()->user();
            $agent = $user->agent ?? null;

            if (!$agent) {
                abort(404, 'Aucun agent lié à votre compte.');
            }
        }

        return view('profile.show', compact('agent'));
    }

    /**
     * Show the form for editing the profile.
     */
    public function edit(?Agent $agent = null): View
    {
        $user = auth()->user();

        // If no agent passed, use the authenticated user's agent
        if (!$agent || !$agent->exists) {
            $agent = $user->agent ?? null;

            if (!$agent) {
                abort(404, 'Aucun agent lié à votre compte.');
            }
        }

        // Vérifier que l'agent édite son propre profil
        if ($user->agent && $user->agent->id !== $agent->id && !$user->hasAdminAccess()) {
            abort(403);
        }

        return view('profile.edit', compact('agent'));
    }

    /**
     * Update the profile.
     */
    public function update(Request $request, ?Agent $agent = null): RedirectResponse
    {
        $user = auth()->user();

        // If no agent passed, use the authenticated user's agent
        if (!$agent || !$agent->exists) {
            $agent = $user->agent ?? null;

            if (!$agent) {
                abort(404, 'Aucun agent lié à votre compte.');
            }
        }

        // Vérifier que l'agent édite son propre profil
        if ($user->agent && $user->agent->id !== $agent->id && !$user->hasAdminAccess()) {
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

        return redirect()->route('profile.show')
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

        return redirect()->route('profile.show')
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

    /**
     * Show a single message and mark it as read.
     */
    public function showMessage(Message $message): View
    {
        $agent = auth()->user()->agent;

        // Only the recipient agent can view the message
        if (!$agent || $message->agent_id !== $agent->id) {
            abort(403);
        }

        // Mark as read
        if (!$message->lu) {
            $message->update(['lu' => true]);
        }

        $message->load('sender');

        return view('messages.show', compact('message', 'agent'));
    }

    /**
     * Affiche la liste des jours d'absence de l'agent connecte.
     */
    public function mesAbsences(Request $request): View
    {
        $agent = auth()->user()->agent;
        $absences = collect();
        $totalAbsences = 0;

        if ($agent && Schema::hasTable('pointages')) {
            $query = Pointage::where('agent_id', $agent->id)
                ->whereNull('heure_entree');

            // Filtre par mois
            if ($request->filled('mois')) {
                $query->whereMonth('date_pointage', $request->input('mois'));
            }

            // Filtre par annee
            $annee = $request->input('annee', now()->year);
            $query->whereYear('date_pointage', $annee);

            $totalAbsences = $query->count();
            $absences = $query->orderByDesc('date_pointage')->paginate(20);
        }

        $annee = $request->input('annee', now()->year);

        return view('absences.index', compact('agent', 'absences', 'totalAbsences', 'annee'));
    }
}
