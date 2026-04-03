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
            'description' => $this->description,
            'niveau_administratif' => $this->niveau_administratif,
            'annee' => $this->annee,
            'trimestre' => $this->trimestre,
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
            'localite' => $this->whenLoaded('localite', function () {
                return [
                    'id' => $this->localite->id,
                    'nom' => $this->localite->nom,
                ];
            }),
        ];
    }
}