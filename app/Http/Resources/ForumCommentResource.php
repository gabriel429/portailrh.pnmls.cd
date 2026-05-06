<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ForumCommentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $request->user();
        $authorAgent = $this->agent ?: $this->user?->agent;
        $postOwnerId = $this->forumPost?->user_id;

        return [
            'id' => $this->id,
            'contenu' => $this->contenu,
            'created_at' => optional($this->created_at)?->toIso8601String(),
            'updated_at' => optional($this->updated_at)?->toIso8601String(),
            'can_delete' => (bool) ($user && (
                $user->id === $this->user_id
                || $user->id === $postOwnerId
                || $user->isSuperAdmin()
                || $user->hasAdminAccess()
            )),
            'auteur' => [
                'user_id' => $this->user?->id,
                'name' => $authorAgent?->nom_complet ?: $this->user?->name,
                'email' => $this->user?->email,
                'photo' => $authorAgent?->photo,
                'poste' => $authorAgent?->poste_actuel,
                'role' => $authorAgent?->role?->nom_role ?: $this->user?->role?->nom_role,
                'departement' => $authorAgent?->departement?->nom,
            ],
        ];
    }
}
