<?php

namespace App\Http\Controllers\Api;

use App\Models\Agent;
use App\Services\UserDataScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AddressBookController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = Agent::query()
            ->with(['province:id,nom', 'departement:id,nom,code'])
            ->actifs();

        app(UserDataScope::class)->applyAgentScope($query, $request->user());

        if ($search = trim((string) $request->query('search', ''))) {
            $term = '%' . $search . '%';
            $query->where(function ($q) use ($term) {
                $q->where('nom', 'like', $term)
                    ->orWhere('postnom', 'like', $term)
                    ->orWhere('prenom', 'like', $term)
                    ->orWhere('poste_actuel', 'like', $term)
                    ->orWhere('fonction', 'like', $term)
                    ->orWhere('telephone', 'like', $term)
                    ->orWhere('telephone_professionnel', 'like', $term)
                    ->orWhere('telephone_prive', 'like', $term)
                    ->orWhere('email_professionnel', 'like', $term)
                    ->orWhere('email_prive', 'like', $term);
            });
        }

        $agents = $query
            ->orderBy('nom')
            ->orderBy('postnom')
            ->orderBy('prenom')
            ->get([
                'id',
                'nom',
                'postnom',
                'prenom',
                'poste_actuel',
                'fonction',
                'telephone',
                'telephone_professionnel',
                'telephone_prive',
                'email',
                'email_professionnel',
                'email_prive',
                'organe',
                'province_id',
                'departement_id',
            ])
            ->map(function (Agent $agent) {
                $telephones = collect([
                    $agent->telephone_professionnel,
                    $agent->telephone,
                    $agent->telephone_prive,
                ])->filter()->unique()->values();

                $emails = collect([
                    $agent->email_professionnel,
                    $agent->email,
                    $agent->email_prive,
                ])->filter()->unique()->values();

                return [
                    'id' => $agent->id,
                    'nom' => $agent->nom,
                    'postnom' => $agent->postnom,
                    'prenom' => $agent->prenom,
                    'nom_complet' => trim(collect([$agent->prenom, $agent->nom, $agent->postnom])->filter()->join(' ')),
                    'poste' => $agent->poste_actuel ?: $agent->fonction ?: 'Poste non renseigné',
                    'contact' => $telephones->first() ?: 'N/A',
                    'telephones' => $telephones,
                    'emails' => $emails,
                    'structure' => $agent->departement?->nom
                        ?: $agent->province?->nom
                        ?: $agent->organe
                        ?: null,
                ];
            })
            ->sortBy(fn ($agent) => strtolower($agent['nom'] . ' ' . ($agent['postnom'] ?? '') . ' ' . ($agent['prenom'] ?? '')))
            ->values();

        $groups = $agents
            ->groupBy('poste')
            ->map(fn ($items, $poste) => [
                'poste' => $poste,
                'count' => $items->count(),
                'agents' => $items->values(),
            ])
            ->sortBy(fn ($group) => strtolower($group['poste']))
            ->values();

        return $this->success([
            'groups' => $groups,
            'total' => $agents->count(),
        ]);
    }
}
