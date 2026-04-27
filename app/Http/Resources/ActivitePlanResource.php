<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActivitePlanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'titre' => $this->titre,
            'categorie' => $this->categorie,
            'objectif' => $this->objectif,
            'description' => $this->description,
            'resultat_attendu' => $this->resultat_attendu,
            'niveau_administratif' => $this->niveau_administratif,
            'validation_niveau' => $this->validation_niveau,
            'responsable_code' => $this->responsable_code,
            'cout_cdf' => $this->cout_cdf,
            'departement_id' => $this->departement_id,
            'province_id' => $this->province_id,
            'localite_id' => $this->localite_id,
            'annee' => $this->annee,
            'trimestre' => $this->trimestre,
            'trimestre_1' => (bool) $this->trimestre_1,
            'trimestre_2' => (bool) $this->trimestre_2,
            'trimestre_3' => (bool) $this->trimestre_3,
            'trimestre_4' => (bool) $this->trimestre_4,
            'statut' => $this->statut,
            'date_debut' => optional($this->date_debut)?->toDateString(),
            'date_fin' => optional($this->date_fin)?->toDateString(),
            'pourcentage' => $this->pourcentage,
            'observations' => $this->observations,
            'created_at' => optional($this->created_at)?->toIso8601String(),
            'updated_at' => optional($this->updated_at)?->toIso8601String(),
            'createur' => $this->whenLoaded('createur', function () use ($request) {
                return AgentResource::make($this->createur)->resolve($request);
            }),
            'departement' => $this->whenLoaded('departement', function () {
                return [
                    'id' => $this->departement->id,
                    'nom' => $this->departement->nom,
                ];
            }),
            'province' => $this->whenLoaded('province', function () {
                return [
                    'id' => $this->province->id,
                    'nom' => $this->province->nom_province ?? $this->province->nom,
                ];
            }),
            'province_ids' => $this->whenLoaded('provinces', function () {
                return $this->provinces->pluck('id')->values()->all();
            }),
            'provinces' => $this->whenLoaded('provinces', function () {
                return $this->provinces->map(function ($province) {
                    return [
                        'id' => $province->id,
                        'nom' => $province->nom_province ?? $province->nom,
                    ];
                })->values()->all();
            }),
            'localite' => $this->whenLoaded('localite', function () {
                return [
                    'id' => $this->localite->id,
                    'nom' => $this->localite->nom,
                ];
            }),
            'taches' => $this->whenLoaded('taches', function () {
                return $this->taches->map(function ($tache) {
                    return [
                        'id' => $tache->id,
                        'titre' => $tache->titre,
                        'statut' => $tache->statut,
                        'priorite' => $tache->priorite,
                        'agent' => $tache->relationLoaded('agent') && $tache->agent ? [
                            'id' => $tache->agent->id,
                            'nom_complet' => $tache->agent->prenom . ' ' . $tache->agent->nom,
                        ] : null,
                    ];
                })->values()->all();
            }),
            'assigned_agents' => $this->whenLoaded('agents', function () {
                return $this->agents->map(fn ($a) => [
                    'id'         => $a->id,
                    'nom_complet' => trim($a->prenom . ' ' . $a->nom),
                    'photo'      => $a->photo,
                    'organe'     => $a->organe,
                    'fonction'   => $a->fonction,
                ])->values()->all();
            }),
            'assigned_agent_ids' => $this->whenLoaded('agents', function () {
                return $this->agents->pluck('id')->values()->all();
            }),
        ];
    }
}