<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class CommuniqueResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $attachments = $this->relationLoaded('attachments')
            ? $this->attachments->map(function ($attachment) {
                $url = $attachment->path
                    ? Storage::disk($attachment->disk ?: 'public')->url($attachment->path)
                    : null;

                return [
                    'id' => $attachment->id,
                    'name' => $attachment->original_name,
                    'original_name' => $attachment->original_name,
                    'mime_type' => $attachment->mime_type,
                    'size' => $attachment->size,
                    'url' => $url,
                    'download_url' => $url,
                    'created_at' => optional($attachment->created_at)?->toIso8601String(),
                ];
            })->values()
            : collect();
        $firstAttachment = $attachments->first();

        return [
            'id' => $this->id,
            'titre' => $this->titre,
            'contenu' => $this->contenu,
            'urgence' => $this->urgence,
            'signataire' => $this->signataire,
            'date_expiration' => optional($this->date_expiration)?->toDateString(),
            'actif' => (bool) $this->actif,
            'read_count' => (int) ($this->reads_count ?? 0),
            'has_read' => $request->user()
                ? $this->hasBeenReadBy((int) $request->user()->id)
                : false,
            'attachments' => $attachments->all(),
            'piece_jointe_url' => $firstAttachment['url'] ?? null,
            'piece_jointe_name' => $firstAttachment['name'] ?? null,
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

    private function hasBeenReadBy(int $userId): bool
    {
        if ($this->relationLoaded('reads')) {
            return $this->reads->contains('user_id', $userId);
        }

        return $this->reads()->where('user_id', $userId)->exists();
    }
}
