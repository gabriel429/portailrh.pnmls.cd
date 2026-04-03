<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'agent_id' => $this->agent_id,
            'type' => $this->type,
            'description' => $this->description,
            'motivation' => $this->motivation,
            'date_debut' => optional($this->date_debut)->toDateString(),
            'date_fin' => optional($this->date_fin)->toDateString(),
            'statut' => $this->statut,
            'remarques' => $this->remarques,
            'lettre_demande' => $this->lettre_demande,
            'created_at' => optional($this->created_at)->toISOString(),
            'updated_at' => optional($this->updated_at)->toISOString(),
            'agent' => $this->whenLoaded('agent', fn () => [
                'id' => $this->agent->id,
                'id_agent' => $this->agent->id_agent,
                'nom_complet' => $this->agent->nom_complet,
                'email_professionnel' => $this->agent->email_professionnel,
            ]),
        ];
    }
}