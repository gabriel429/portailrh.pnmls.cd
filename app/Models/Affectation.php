<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Affectation extends Model
{
    protected $fillable = [
        'agent_id',
        'fonction_id',
        'niveau',
        'department_id',
        'section_id',
        'cellule_id',
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
}
