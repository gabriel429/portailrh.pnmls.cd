<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DocumentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'agent_id' => $this->agent_id,
            'type' => $this->type,
            'fichier' => $this->fichier,
            'description' => $this->description,
            'date_expiration' => optional($this->date_expiration)->toDateString(),
            'statut' => $this->statut,
            'created_at' => optional($this->created_at)->toISOString(),
            'updated_at' => optional($this->updated_at)->toISOString(),
            'agent' => $this->whenLoaded('agent', fn () => [
                'id' => $this->agent->id,
                'id_agent' => $this->agent->id_agent,
                'nom_complet' => $this->agent->nom_complet,
            ]),
        ];
    }
}