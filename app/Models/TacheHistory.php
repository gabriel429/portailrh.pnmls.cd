<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TacheHistory extends Model
{
    protected $fillable = [
        'tache_id',
        'agent_id',
        'action',
        'action_label',
        'ancien_statut',
        'nouveau_statut',
        'ancien_validation_statut',
        'nouveau_validation_statut',
        'commentaire',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
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
