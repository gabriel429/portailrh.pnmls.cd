<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pointage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    /**
     * Return the authenticated user's agent profile with all relations.
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $agent = $user->agent;

        if (!$agent) {
            return response()->json([
                'message' => 'Aucun agent lié à votre compte.',
            ], 404);
        }

        $agent->load([
            'role',
            'province',
            'departement',
            'grade',
            'institution',
            'documents',
            'affectations.fonction',
            'affectations.department',
            'affectations.province',
        ]);

        // Build counts for stats
        $stats = [
            'documents'   => $agent->documents->count(),
            'affectations' => $agent->affectations->count(),
            'pointages'   => $agent->pointages()->count(),
            'demandes'    => $agent->requests()->count(),
        ];

        return response()->json([
            'agent' => $agent,
            'stats' => $stats,
        ]);
    }

    /**
     * Update editable profile fields (telephone, adresse, email_prive, photo).
     */
    public function update(Request $request): JsonResponse
    {
        $user = $request->user();
        $agent = $user->agent;

        if (!$agent) {
            return response()->json([
                'message' => 'Aucun agent lié à votre compte.',
            ], 404);
        }

        $validated = $request->validate([
            'telephone'    => 'nullable|string|max:30',
            'adresse'      => 'nullable|string|max:255',
            'email_prive'  => 'nullable|email|max:255',
            'photo'        => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('photo')) {
            $file = $request->file('photo');
            $extension = $file->getClientOriginalExtension();
            $filename = Str::uuid() . '.' . $extension;
            $file->move(public_path('uploads/profiles'), $filename);
            $validated['photo'] = 'uploads/profiles/' . $filename;
        }

        $agent->update($validated);

        // Reload relations for the response
        $agent->load([
            'role',
            'province',
            'departement',
            'grade',
            'institution',
        ]);

        return response()->json([
            'message' => 'Profil mis à jour avec succès.',
            'agent'   => $agent,
        ]);
    }

    /**
     * Change the authenticated user's password.
     */
    public function updatePassword(Request $request): JsonResponse
    {
        $user = $request->user();
        $agent = $user->agent;

        // The Agent model extends Authenticatable, so password lives on the agent
        $authModel = $agent ?? $user;

        $request->validate([
            'current_password' => 'required|string',
            'password'         => 'required|string|min:8|confirmed',
        ]);

        if (!Hash::check($request->current_password, $authModel->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Le mot de passe actuel est incorrect.'],
            ]);
        }

        $authModel->update([
            'password' => Hash::make($request->password),
        ]);

        return response()->json([
            'message' => 'Mot de passe modifié avec succès.',
        ]);
    }

    /**
     * Return the authenticated user's absences (pointages with no entry time).
     */
    public function mesAbsences(Request $request): JsonResponse
    {
        $agent = $request->user()->agent;
        $absences = collect();
        $totalAbsences = 0;

        if ($agent && Schema::hasTable('pointages')) {
            $query = Pointage::where('agent_id', $agent->id)
                ->whereNull('heure_entree');

            if ($request->filled('mois')) {
                $query->whereMonth('date_pointage', $request->input('mois'));
            }

            $annee = $request->input('annee', now()->year);
            $query->whereYear('date_pointage', $annee);

            $totalAbsences = $query->count();
            $absencesPaginated = $query->orderByDesc('date_pointage')->paginate(20);

            return response()->json([
                'data' => $absencesPaginated->items(),
                'totalAbsences' => $totalAbsences,
                'meta' => [
                    'current_page' => $absencesPaginated->currentPage(),
                    'last_page' => $absencesPaginated->lastPage(),
                    'per_page' => $absencesPaginated->perPage(),
                    'total' => $absencesPaginated->total(),
                    'from' => $absencesPaginated->firstItem(),
                    'to' => $absencesPaginated->lastItem(),
                ],
            ]);
        }

        return response()->json([
            'data' => [],
            'totalAbsences' => 0,
            'meta' => [
                'current_page' => 1,
                'last_page' => 1,
                'per_page' => 20,
                'total' => 0,
                'from' => null,
                'to' => null,
            ],
        ]);
    }
}
