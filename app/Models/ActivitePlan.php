<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivitePlan extends Model
{
    protected $table = 'activite_plans';

    protected $fillable = [
        'createur_id',
        'titre',
        'description',
        'niveau_administratif',
        'departement_id',
        'province_id',
        'localite_id',
        'annee',
        'trimestre',
        'statut',
        'date_debut',
        'date_fin',
        'pourcentage',
        'observations',
    ];

    protected $casts = [
        'date_debut' => 'date',
        'date_fin' => 'date',
        'annee' => 'integer',
        'pourcentage' => 'integer',
    ];

    public function createur(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'createur_id');
    }

    public function departement(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'departement_id');
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class, 'province_id');
    }

    public function localite(): BelongsTo
    {
        return $this->belongsTo(Localite::class, 'localite_id');
    }

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

    public function scopeParAnnee($query, $annee)
    {
        return $query->where('annee', $annee);
    }

    public function scopeParNiveau($query, $niveau)
    {
        return $query->where('niveau_administratif', $niveau);
    }

    public function scopeParTrimestre($query, $trimestre)
    {
        return $query->where('trimestre', $trimestre);
    }
}
