<?php

namespace App\Http\Resources;

use App\Models\Agent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

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
            'likes_count' => (int) ($this->likes_count ?? 0),
            'dislikes_count' => (int) ($this->dislikes_count ?? 0),
            'user_reaction' => $this->whenLoaded('reactions', function () {
                return $this->reactions->first()?->reaction;
            }),
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
                'fonction' => $authorAgent?->fonction ?: $authorAgent?->poste_actuel,
                'role' => $authorAgent?->role?->nom_role ?: $this->user?->role?->nom_role,
                'departement' => $authorAgent?->departement?->nom,
                'province' => $authorAgent?->province?->nom ?: $authorAgent?->departement?->province?->nom,
                'rattachement' => $this->resolveAgentRattachement($authorAgent),
            ],
        ];
    }

    private function resolveAgentRattachement(?Agent $agent): ?string
    {
        if (! $agent) {
            return null;
        }

        if ($agent->departement?->nom) {
            return $agent->departement->nom;
        }

        if ($this->isDirectSenAgent($agent)) {
            return 'Attache au SEN';
        }

        if ($agent->province?->nom) {
            return $agent->province->nom;
        }

        return $agent->organe ?: null;
    }

    private function isDirectSenAgent(Agent $agent): bool
    {
        $organe = Str::lower(Str::ascii((string) $agent->organe));

        return $organe === 'sen' || str_contains($organe, 'national');
    }
}
