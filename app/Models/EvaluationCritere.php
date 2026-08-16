<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EvaluationCritere extends Model
{
    protected $fillable = [
        'code',
        'libelle',
        'description',
        'categorie',
        'poids',
        'ordre',
        'actif',
    ];

    protected $casts = [
        'poids' => 'float',
        'actif' => 'boolean',
    ];

    public function details(): HasMany
    {
        return $this->hasMany(EvaluationDetail::class);
    }

    public function scopeActifs($query)
    {
        return $query->where('actif', true)->orderBy('ordre');
    }
}
