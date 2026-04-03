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
            'priorite' => $this->priorite,
            'statut' => $this->statut,
            'date_echeance' => optional($this->date_echeance)?->toDateString(),
            'created_at' => optional($this->created_at)?->toIso8601String(),
            'updated_at' => optional($this->updated_at)?->toIso8601String(),
            'createur' => $this->whenLoaded('createur', function () use ($request) {
                return AgentResource::make($this->createur)->resolve($request);
            }),
            'agent' => $this->whenLoaded('agent', function () use ($request) {
                return AgentResource::make($this->agent)->resolve($request);
            }),
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
                    ];
                })->values()->all();
            }),
        ];
    }
}