<?php

namespace App\Models;

use App\Traits\Syncable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TacheDocument extends Model
{
    use Syncable;

    protected $fillable = [
        'tache_id',
        'agent_id',
        'tache_commentaire_id',
        'type_document',
        'titre',
        'fichier',
        'nom_original',
        'mime_type',
        'taille',
    ];

    protected $casts = [
        'taille' => 'integer',
    ];

    public function tache(): BelongsTo
    {
        return $this->belongsTo(Tache::class);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function commentaire(): BelongsTo
    {
        return $this->belongsTo(TacheCommentaire::class, 'tache_commentaire_id');
    }
}