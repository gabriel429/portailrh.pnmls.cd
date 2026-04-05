<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Formation extends Model
{
    protected $fillable = [
        'titre',
        'description',
        'request_id',
        'objectif',
        'lieu',
        'formateur',
        'date_debut',
        'date_fin',
        'statut',
        'created_by',
        'validated_by',
        'validated_at',
        'budget',
    ];

    protected $casts = [
        'date_debut'   => 'date',
        'date_fin'     => 'date',
        'validated_at' => 'datetime',
        'budget'       => 'decimal:2',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(Request::class);
    }

    public function createur(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'created_by');
    }

    public function validateur(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'validated_by');
    }

    public function beneficiaires(): HasMany
    {
        return $this->hasMany(FormationBeneficiaire::class);
    }

    // Scopes
    public function scopePlanifiee($query)
    {
        return $query->where('statut', 'planifiee');
    }

    public function scopeEnCours($query)
    {
        return $query->where('statut', 'en_cours');
    }

    public function scopeTerminee($query)
    {
        return $query->where('statut', 'terminee');
    }
}
