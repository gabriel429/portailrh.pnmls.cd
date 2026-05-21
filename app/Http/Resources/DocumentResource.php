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
            'nom_document' => trim(explode(' | ', (string) $this->description)[0] ?? '') ?: 'Document ' . $this->id,
            'description' => $this->description,
            'description_detail' => str_contains((string) $this->description, ' | ')
                ? trim(implode(' | ', array_slice(explode(' | ', (string) $this->description), 1)))
                : '',
            'date_expiration' => optional($this->date_expiration)->toDateString(),
            'statut' => $this->statut,
            'created_at' => optional($this->created_at)->toISOString(),
            'updated_at' => optional($this->updated_at)->toISOString(),
            'download_url' => url('/api/documents/' . $this->id . '/download'),
            'agent' => $this->whenLoaded('agent', fn () => [
                'id' => $this->agent->id,
                'id_agent' => $this->agent->id_agent,
                'matricule_etat' => $this->agent->matricule_etat,
                'nom_complet' => $this->agent->nom_complet,
            ]),
        ];
    }
}
