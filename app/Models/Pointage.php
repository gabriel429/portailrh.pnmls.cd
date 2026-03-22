<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use App\Traits\Syncable;

class Pointage extends Model
{
    use Syncable;

    protected $fillable = [
        'agent_id',
        'date_pointage',
        'heure_entree',
        'heure_sortie',
        'heures_travaillees',
        'observations',
    ];

    protected $casts = [
        'date_pointage' => 'date',
        'heure_entree' => 'datetime:H:i',
        'heure_sortie' => 'datetime:H:i',
    ];

    // Relations BelongsTo
    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    // Scopes
    public function scopeByDate($query, $date)
    {
        return $query->whereDate('date_pointage', $date);
    }

    public function scopeByAgent($query, $agentId)
    {
        return $query->where('agent_id', $agentId);
    }

    public function scopeBetweenDates($query, $dateDebut, $dateFin)
    {
        return $query->whereBetween('date_pointage', [$dateDebut, $dateFin]);
    }
}

