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
                'photo',
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
                $telephoneProfessionnel = $agent->telephone_professionnel ?: $agent->telephone;
                $telephonePrive = $agent->telephone_prive;
                $emailProfessionnel = $agent->email_professionnel ?: $agent->email;
                $emailPrive = $agent->email_prive;

                $telephones = collect([$telephoneProfessionnel, $telephonePrive])->filter()->unique()->values();
                $emails = collect([$emailProfessionnel, $emailPrive])->filter()->unique()->values();

                return [
                    'id' => $agent->id,
                    'nom' => $agent->nom,
                    'postnom' => $agent->postnom,
                    'prenom' => $agent->prenom,
                    'nom_complet' => trim(collect([$agent->prenom, $agent->nom, $agent->postnom])->filter()->join(' ')),
                    'photo' => $agent->photo,
                    'poste' => $agent->poste_actuel ?: $agent->fonction ?: 'Poste non renseigné',
                    'contact' => $telephones->first() ?: 'N/A',
                    'telephones' => $telephones,
                    'telephone_professionnel' => $telephoneProfessionnel,
                    'telephone_prive' => $telephonePrive,
                    'emails' => $emails,
                    'email_professionnel' => $emailProfessionnel,
                    'email_prive' => $emailPrive,
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
                'order' => $this->institutionalPostOrder((string) $poste),
            ])
            ->sortBy(fn ($group) => sprintf('%02d-%s', $group['order'], strtolower($group['poste'])))
            ->map(fn ($group) => collect($group)->except('order')->all())
            ->values();

        return $this->success([
            'groups' => $groups,
            'total' => $agents->count(),
        ]);
    }

    private function institutionalPostOrder(string $poste): int
    {
        $label = strtolower($poste);

        return match (true) {
            str_contains($label, 'secr') && str_contains($label, 'ex') && str_contains($label, 'cutif') => 1,
            str_contains($label, 'directeur') => 2,
            str_contains($label, 'chef') && str_contains($label, 'section') => 3,
            str_contains($label, 'chef') && str_contains($label, 'cellule') => 4,
            str_contains($label, 'assistant') => 5,
            str_contains($label, 'secr') && str_contains($label, 'taire') => 5,
            default => 6,
        };
    }
}
