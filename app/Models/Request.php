<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use App\Traits\Syncable;

class Request extends Model
{
    use Syncable;

    protected $table = 'requests';

    protected $fillable = [
        'agent_id',
        'type',
        'description',
        'motivation',
        'date_debut',
        'date_fin',
        'statut',
        'remarques',
        'lettre_demande',
        'current_step',
        'validated_by_director',
        'validated_at_director',
        'validated_by_rh',
        'validated_at_rh',
        'validated_by_sep',
        'validated_at_sep',
        'validated_by_sen',
        'validated_at_sen',
    ];

    protected $casts = [
        'date_debut'           => 'date',
        'date_fin'             => 'date',
        'validated_at_director' => 'datetime',
        'validated_at_rh'      => 'datetime',
        'validated_at_sep'     => 'datetime',
        'validated_at_sen'     => 'datetime',
    ];

    // Relations BelongsTo
    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function validatedByDirector(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'validated_by_director');
    }

    public function validatedByRh(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'validated_by_rh');
    }

    public function validatedBySep(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'validated_by_sep');
    }

    public function validatedBySen(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'validated_by_sen');
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

