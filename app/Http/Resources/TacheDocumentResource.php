<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TacheDocumentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type_document' => $this->type_document,
            'titre' => $this->titre,
            'fichier' => $this->fichier,
            'nom_original' => $this->nom_original,
            'mime_type' => $this->mime_type,
            'taille' => $this->taille,
            'download_url' => url('/api/taches/' . $this->tache_id . '/documents/' . $this->id . '/download'),
            'created_at' => optional($this->created_at)?->toIso8601String(),
            'agent' => $this->whenLoaded('agent', function () use ($request) {
                return $this->agent ? AgentResource::make($this->agent)->resolve($request) : null;
            }),
        ];
    }
}