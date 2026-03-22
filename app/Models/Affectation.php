<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use App\Traits\Syncable;

class Affectation extends Model
{
    use Syncable;

    protected $fillable = [
        'agent_id',
        'fonction_id',
        'niveau_administratif', // SEN | SEP | SEL
        'niveau',               // direction | service_rattache | département | section | cellule | province | local
        'department_id',
        'section_id',
        'cellule_id',
        'province_id',
        'localite_id',
        'date_debut',
        'date_fin',
        'actif',
        'remarque',
    ];

    protected $casts = [
        'date_debut' => 'date',
        'date_fin'   => 'date',
        'actif'      => 'boolean',
    ];

    /** Label lisible du niveau administratif */
    public function getNiveauAdministratifLabelAttribute(): string
    {
        return match($this->niveau_administratif) {
            'SEN' => 'Secrétariat Exécutif National',
            'SEP' => 'Secrétariat Exécutif Provincial',
            'SEL' => 'Secrétariat Exécutif Local',
            default => $this->niveau_administratif,
        };
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function fonction(): BelongsTo
    {
        return $this->belongsTo(Fonction::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function cellule(): BelongsTo
    {
        return $this->belongsTo(Cellule::class);
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }

    public function localite(): BelongsTo
    {
        return $this->belongsTo(Localite::class);
    }
}

