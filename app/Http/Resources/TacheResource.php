<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TacheResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'titre' => $this->titre,
            'description' => $this->description,
            'source_type' => $this->source_type,
            'source_emetteur' => $this->source_emetteur,
            'priorite' => $this->priorite,
            'statut' => $this->statut,
            'pourcentage' => (int) ($this->pourcentage ?? 0),
            'date_echeance' => optional($this->date_echeance)?->toDateString(),
            'date_tache' => optional($this->date_tache)?->toDateString(),
            'created_at' => optional($this->created_at)?->toIso8601String(),
            'updated_at' => optional($this->updated_at)?->toIso8601String(),
            'activite_plan' => $this->whenLoaded('activitePlan', function () {
                if (!$this->activitePlan) {
                    return null;
                }

                return [
                    'id' => $this->activitePlan->id,
                    'titre' => $this->activitePlan->titre,
                    'annee' => $this->activitePlan->annee,
                    'trimestre' => $this->activitePlan->trimestre,
                ];
            }),
            'createur' => $this->whenLoaded('createur', fn () => $this->createur ? AgentResource::make($this->createur) : null),
            'agent' => $this->whenLoaded('agent', fn () => $this->agent ? AgentResource::make($this->agent) : null),
            'commentaires' => $this->whenLoaded('commentaires', function () use ($request) {
                return $this->commentaires->map(function ($commentaire) use ($request) {
                    return [
                        'id' => $commentaire->id,
                        'contenu' => $commentaire->contenu,
                        'ancien_statut' => $commentaire->ancien_statut,
                        'nouveau_statut' => $commentaire->nouveau_statut,
                        'created_at' => optional($commentaire->created_at)?->toIso8601String(),
                        'updated_at' => optional($commentaire->updated_at)?->toIso8601String(),
                        'agent' => $commentaire->relationLoaded('agent') && $commentaire->agent
                            ? AgentResource::make($commentaire->agent)->resolve($request)
                            : null,
                        'documents' => $commentaire->relationLoaded('documents')
                            ? TacheDocumentResource::collection($commentaire->documents)->resolve($request)
                            : [],
                    ];
                })->values()->all();
            }),
            'documents' => $this->whenLoaded('documents', function () use ($request) {
                return TacheDocumentResource::collection($this->documents)->resolve($request);
            }),
        ];
    }
}