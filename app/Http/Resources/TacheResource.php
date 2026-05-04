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
            'niveau_gestion' => $this->niveau_gestion,
            'priorite' => $this->priorite,
            'statut' => $this->statut,
            'pourcentage' => (int) ($this->pourcentage ?? 0),
            'validation_responsable_role' => $this->validation_responsable_role,
            'validation_statut' => $this->validation_statut,
            'validation_commentaire' => $this->validation_commentaire,
            'soumise_validation_at' => optional($this->soumise_validation_at)?->toIso8601String(),
            'validated_at' => optional($this->validated_at)?->toIso8601String(),
            'rejected_at' => optional($this->rejected_at)?->toIso8601String(),
            'blocked_at' => optional($this->blocked_at)?->toIso8601String(),
            'blocking_reason' => $this->blocking_reason,
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
            'createur' => $this->whenLoaded('createur', function () {
                return $this->createur ? AgentResource::make($this->createur)->toArray(request()) : null;
            }),
            'agent' => $this->whenLoaded('agent', function () {
                return $this->agent ? AgentResource::make($this->agent)->toArray(request()) : null;
            }),
            'commentaires' => $this->whenLoaded('commentaires', function () use ($request) {
                return $this->commentaires->map(function ($commentaire) use ($request) {
                    return [
                        'id' => $commentaire->id,
                        'contenu' => $commentaire->contenu,
                        'type_commentaire' => $commentaire->type_commentaire,
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
            'histories' => $this->whenLoaded('histories', function () use ($request) {
                return $this->histories->map(function ($history) use ($request) {
                    return [
                        'id' => $history->id,
                        'action' => $history->action,
                        'action_label' => $history->action_label,
                        'ancien_statut' => $history->ancien_statut,
                        'nouveau_statut' => $history->nouveau_statut,
                        'ancien_validation_statut' => $history->ancien_validation_statut,
                        'nouveau_validation_statut' => $history->nouveau_validation_statut,
                        'commentaire' => $history->commentaire,
                        'meta' => $history->meta,
                        'created_at' => optional($history->created_at)?->toIso8601String(),
                        'agent' => $history->relationLoaded('agent') && $history->agent
                            ? AgentResource::make($history->agent)->resolve($request)
                            : null,
                    ];
                })->values()->all();
            }),
        ];
    }
}
