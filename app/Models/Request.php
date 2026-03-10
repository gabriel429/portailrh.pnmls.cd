<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Request extends Model
{
    protected $table = 'requests';

    protected $fillable = [
        'agent_id',
        'type',
        'description',
        'date_debut',
        'date_fin',
        'statut',
        'remarques',
    ];

    protected $casts = [
        'date_debut' => 'date',
        'date_fin' => 'date',
    ];

    // Relations BelongsTo
    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    // Scopes
    public function scopeEnAttente($query)
    {
        return $query->where('statut', 'en_attente');
    }

    public function scopeApprouve($query)
    {
        return $query->where('statut', 'approuvé');
    }

    public function scopeRejete($query)
    {
        return $query->where('statut', 'rejeté');
    }

    public function scopeAnnule($query)
    {
        return $query->where('statut', 'annulé');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }
}

