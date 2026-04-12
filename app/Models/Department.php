<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Department extends Model
{
    public const ACTIVE_NATIONAL_DEPARTMENT_KEYWORDS = [
        ['administration', 'finance'],
        ['coordination', 'multi'],
        ['documentation', 'renforcement'],
        ['partenariat', 'multilat'],
        ['planification', 'evaluation'],
    ];

    protected $fillable = [
        'code',
        'nom',
        'description',
        'province_id',
        'pris_en_charge',
    ];

    protected $casts = [
        'pris_en_charge' => 'boolean',
    ];

    // Relations BelongsTo
    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }

    // Relations HasMany
    public function agents(): HasMany
    {
        return $this->hasMany(Agent::class, 'departement_id');
    }

    public function sections(): HasMany
    {
        return $this->hasMany(Section::class);
    }

    public function affectations(): HasMany
    {
        return $this->hasMany(Affectation::class);
    }

    /** Agent portant le rôle Directeur dans ce département */
    public function directeur(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Agent::class, 'departement_id')
                    ->whereHas('role', fn($q) => $q->where('nom_role', 'Directeur'));
    }

    /** Chef de département actif */
    public function chef()
    {
        return $this->affectations()
            ->where('niveau', 'département')
            ->whereHas('fonction', fn($q) => $q->where('est_chef', true))
            ->where('actif', true)
            ->with('agent')
            ->first();
    }

    // Scopes

    /**
     * Départements fonctionnels pris en charge par le système.
     * Les autres sont conservés pour l'affectation et l'historisation.
     */
    public function scopeFonctionnel($query)
    {
        return $query->where('pris_en_charge', true);
    }

    public function scopeByCode($query, $code)
    {
        return $query->where('code', $code);
    }

    public function scopeByProvince($query, $provinceId)
    {
        return $query->where('province_id', $provinceId);
    }

    public function scopeOperational($query)
    {
        return $query->where(function ($departmentQuery) {
            $departmentQuery
                ->whereNotNull('province_id')
                ->orWhere(function ($nationalQuery) {
                    self::applyActiveNationalDepartmentConstraint($nationalQuery);
                });
        });
    }

    public function scopeOperationalNationalStructures($query)
    {
        return $query
            ->whereNull('province_id')
            ->where(function ($nationalQuery) {
                $nationalQuery
                    ->where('code', 'DIR')
                    ->orWhere(function ($activeDepartmentQuery) {
                        self::applyActiveNationalDepartmentConstraint($activeDepartmentQuery);
                    })
                    ->orWhere(function ($attachedQuery) {
                        $attachedQuery
                            ->whereNotNull('code')
                            ->where('code', 'not like', 'D%');
                    });
            });
    }

    public static function applyActiveNationalDepartmentConstraint(Builder $query): void
    {
        $query->whereNull('province_id')
            ->where(function ($keywordsQuery) {
                foreach (self::ACTIVE_NATIONAL_DEPARTMENT_KEYWORDS as $keywordGroup) {
                    $keywordsQuery->orWhere(function ($matchQuery) use ($keywordGroup) {
                        foreach ($keywordGroup as $keyword) {
                            $matchQuery->where('nom', 'like', '%' . $keyword . '%');
                        }
                    });
                }
            });
    }
}
