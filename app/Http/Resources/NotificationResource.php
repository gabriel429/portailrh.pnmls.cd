<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'titre' => $this->titre,
            'message' => $this->message,
            'icone' => $this->icone,
            'couleur' => $this->couleur,
            'lien' => $this->lien,
            'lu' => (bool) $this->lu,
            'lu_at' => optional($this->lu_at)?->toIso8601String(),
            'created_at' => optional($this->created_at)?->toIso8601String(),
            'updated_at' => optional($this->updated_at)?->toIso8601String(),
            'emetteur' => $this->whenLoaded('emetteur', function () {
                return [
                    'id' => $this->emetteur->id,
                    'name' => $this->emetteur->name,
                    'email' => $this->emetteur->email,
                ];
            }),
        ];
    }
}