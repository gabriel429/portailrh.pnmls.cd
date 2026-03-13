<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    protected $fillable = [
        'code',
        'nom',
        'description',
        'province_id',
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
    public function scopeByCode($query, $code)
    {
        return $query->where('code', $code);
    }

    public function scopeByProvince($query, $provinceId)
    {
        return $query->where('province_id', $provinceId);
    }
}
