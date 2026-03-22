<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use App\Traits\Syncable;

class TacheCommentaire extends Model
{
    use Syncable;

    protected $table = 'tache_commentaires';

    protected $fillable = [
        'tache_id',
        'agent_id',
        'contenu',
        'ancien_statut',
        'nouveau_statut',
    ];

    public function tache(): BelongsTo
    {
        return $this->belongsTo(Tache::class);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }
}
