<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Evaluation extends Model
{
    const STATUTS = [
        'brouillon' => 'Brouillon',
        'soumise' => 'Soumise',
        'validee' => 'Validée',
        'rejetee' => 'Rejetée',
    ];

    protected $fillable = [
        'agent_id',
        'evaluateur_id',
        'organe',
        'province_id',
        'departement_id',
        'periode_type',
        'periode_annee',
        'periode_trimestre',
        'statut',
        'taux_completion_taches',
        'taux_assiduite',
        'score_auto',
        'score_manuel',
        'score_global',
        'commentaire_general',
        'soumise_par_id',
        'soumise_le',
        'validee_par_id',
        'validee_le',
        'motif_rejet',
    ];

    protected $casts = [
        'taux_completion_taches' => 'float',
        'taux_assiduite' => 'float',
        'score_auto' => 'float',
        'score_manuel' => 'float',
        'score_global' => 'float',
        'soumise_le' => 'datetime',
        'validee_le' => 'datetime',
    ];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function evaluateur(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'evaluateur_id');
    }

    public function soumisePar(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'soumise_par_id');
    }

    public function valideePar(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'validee_par_id');
    }

    public function details(): HasMany
    {
        return $this->hasMany(EvaluationDetail::class);
    }

    public function getStatutLabelAttribute(): string
    {
        return self::STATUTS[$this->statut] ?? $this->statut;
    }

    public function scopePeriode($query, int $annee, ?int $trimestre = null)
    {
        $query->where('periode_annee', $annee);

        if ($trimestre !== null) {
            $query->where('periode_trimestre', $trimestre);
        }

        return $query;
    }
}
