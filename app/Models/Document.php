<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use App\Traits\Syncable;

class Document extends Model
{
    use Syncable;

    protected $fillable = [
        'agent_id',
        'name',
        'type',
        'fichier',
        'description',
        'date_expiration',
        'statut',
    ];

    protected $casts = [
        'date_expiration' => 'date',
    ];

    // Relations BelongsTo
    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    // Scopes
    public function scopeValides($query)
    {
        return $query->where('statut', 'valide');
    }

    public function scopeExpires($query)
    {
        return $query->where('statut', 'expiré');
    }

    public function scopeRejetes($query)
    {
        return $query->where('statut', 'rejeté');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }
}

