<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TacheValidationStep extends Model
{
    protected $fillable = [
        'tache_id',
        'step_order',
        'step_code',
        'validator_agent_id',
        'structure_type',
        'structure_id',
        'statut',
        'acted_by',
        'acted_at',
        'commentaire',
        'resolution_metadata',
    ];

    protected $casts = [
        'step_order' => 'integer',
        'structure_id' => 'integer',
        'acted_at' => 'datetime',
        'resolution_metadata' => 'array',
    ];

    public function tache(): BelongsTo
    {
        return $this->belongsTo(Tache::class);
    }

    public function validator(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'validator_agent_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'acted_by');
    }
}