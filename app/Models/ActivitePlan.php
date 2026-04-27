<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

use App\Traits\Syncable;

class ActivitePlan extends Model
{
    use Syncable;

    protected $table = 'activite_plans';

    protected $fillable = [
        'createur_id',
        'titre',
        'categorie',
        'objectif',
        'description',
        'resultat_attendu',
        'niveau_administratif',
        'validation_niveau',
        'responsable_code',
        'cout_cdf',
        'departement_id',
        'province_id',
        'localite_id',
        'annee',
        'trimestre',
        'trimestre_1',
        'trimestre_2',
        'trimestre_3',
        'trimestre_4',
        'statut',
        'date_debut',
        'date_fin',
        'pourcentage',
        'observations',
        'validated_by_section',
        'validated_at_section',
        'validated_by_cellule',
        'validated_at_cellule',
    ];

    protected $casts = [
        'date_debut' => 'date',
        'date_fin' => 'date',
        'annee' => 'integer',
        'cout_cdf' => 'decimal:2',
        'trimestre_1' => 'boolean',
        'trimestre_2' => 'boolean',
        'trimestre_3' => 'boolean',
        'trimestre_4' => 'boolean',
        'pourcentage' => 'integer',
        'validated_at_section' => 'datetime',
        'validated_at_cellule' => 'datetime',
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

    public function provinces(): BelongsToMany
    {
        return $this->belongsToMany(Province::class, 'activite_plan_province')
            ->withTimestamps();
    }

    public function localite(): BelongsTo
    {
        return $this->belongsTo(Localite::class, 'localite_id');
    }

    public function taches(): HasMany
    {
        return $this->hasMany(Tache::class, 'activite_plan_id');
    }

    public function agents(): BelongsToMany
    {
        return $this->belongsToMany(Agent::class, 'activite_plan_agent', 'activite_plan_id', 'agent_id')
            ->withTimestamps();
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
        $mapping = [
            'T1' => 'trimestre_1',
            'T2' => 'trimestre_2',
            'T3' => 'trimestre_3',
            'T4' => 'trimestre_4',
        ];

        if (!isset($mapping[$trimestre])) {
            return $query->where('trimestre', $trimestre);
        }

        return $query->where(function ($builder) use ($trimestre, $mapping) {
            $builder->where('trimestre', $trimestre)
                ->orWhere($mapping[$trimestre], true);
        });
    }
}
