<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommuniqueResource extends JsonResource
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
            'contenu' => $this->contenu,
            'urgence' => $this->urgence,
            'signataire' => $this->signataire,
            'date_expiration' => optional($this->date_expiration)?->toDateString(),
            'actif' => (bool) $this->actif,
            'created_at' => optional($this->created_at)?->toIso8601String(),
            'updated_at' => optional($this->updated_at)?->toIso8601String(),
            'auteur' => $this->whenLoaded('auteur', function () {
                return [
                    'id' => $this->auteur->id,
                    'name' => $this->auteur->name,
                    'email' => $this->auteur->email,
                ];
            }),
        ];
    }
}