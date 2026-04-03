<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PointageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'agent_id' => $this->agent_id,
            'date_pointage' => optional($this->date_pointage)?->toDateString(),
            'heure_entree' => $this->heure_entree,
            'heure_sortie' => $this->heure_sortie,
            'heures_travaillees' => $this->heures_travaillees,
            'observations' => $this->observations,
            'created_at' => optional($this->created_at)?->toIso8601String(),
            'updated_at' => optional($this->updated_at)?->toIso8601String(),
            'agent' => $this->whenLoaded('agent', function () use ($request) {
                return AgentResource::make($this->agent)->resolve($request);
            }),
        ];
    }
}