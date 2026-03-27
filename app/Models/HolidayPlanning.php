<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HolidayPlanning extends Model
{
    const TYPE_STRUCTURES = [
        'department' => 'Département',
        'sen' => 'SEN',
        'sena' => 'SENA',
        'sep' => 'SEP Provincial',
        'local' => 'Structure Locale'
    ];

    protected $fillable = [
        'annee',
        'type_structure',
        'structure_id',
        'nom_structure',
        'jours_conge_totaux',
        'jours_utilises',
        'periods_fermeture',
        'notes',
        'valide',
        'created_by',
        'validated_by',
        'validated_at'
    ];

    protected $casts = [
        'annee' => 'integer',
        'jours_conge_totaux' => 'integer',
        'jours_utilises' => 'integer',
        'periods_fermeture' => 'array',
        'valide' => 'boolean',
        'validated_at' => 'datetime'
    ];

    // Relations
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'created_by');
    }

    public function validatedBy(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'validated_by');
    }

    public function holidays(): HasMany
    {
        return $this->hasMany(Holiday::class);
    }

    // Accessors
    public function getJoursRestantsAttribute(): int
    {
        return $this->jours_conge_totaux - $this->jours_utilises;
    }

    public function getPourcentageUtilisationAttribute(): float
    {
        if ($this->jours_conge_totaux === 0) return 0;
        return round(($this->jours_utilises / $this->jours_conge_totaux) * 100, 1);
    }

    public function getTypeStructureLabelAttribute(): string
    {
        return self::TYPE_STRUCTURES[$this->type_structure] ?? $this->type_structure;
    }

    // Scopes
    public function scopeForYear($query, int $year)
    {
        return $query->where('annee', $year);
    }

    public function scopeForStructure($query, string $type, int $id)
    {
        return $query->where('type_structure', $type)
                    ->where('structure_id', $id);
    }

    public function scopeValidated($query)
    {
        return $query->where('valide', true);
    }

    // Méthodes
    public function validate(Agent $validator): bool
    {
        return $this->update([
            'valide' => true,
            'validated_by' => $validator->id,
            'validated_at' => now()
        ]);
    }

    public function incrementJoursUtilises(int $jours): void
    {
        $this->increment('jours_utilises', $jours);
    }

    public function decrementJoursUtilises(int $jours): void
    {
        $this->decrement('jours_utilises', $jours);
    }
}
