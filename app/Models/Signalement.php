<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use App\Traits\Syncable;

class Signalement extends Model
{
    use Syncable;

    protected $fillable = [
        'agent_id',
        'is_anonymous',
        'type',
        'description',
        'observations',
        'severite',
        'statut',
        'traite_par',
        'traite_le',
    ];

    protected $casts = [
        'is_anonymous' => 'boolean',
        'traite_le'    => 'datetime',
    ];

    // Relations BelongsTo
    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function traitePar(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'traite_par');
    }

    // Scopes
    public function scopeOuvert($query)
    {
        return $query->where('statut', 'ouvert');
    }

    public function scopeEnCours($query)
    {
        return $query->where('statut', 'en_cours');
    }

    public function scopeResolu($query)
    {
        return $query->where('statut', 'résolu');
    }

    public function scopeFerme($query)
    {
        return $query->where('statut', 'fermé');
    }

    public function scopeBasseSeverite($query)
    {
        return $query->where('severite', 'basse');
    }

    public function scopeMoyenneSeverite($query)
    {
        return $query->where('severite', 'moyenne');
    }

    public function scopeHauteSeverite($query)
    {
        return $query->where('severite', 'haute');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }
}

