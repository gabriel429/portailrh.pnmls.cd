<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Institution extends Model
{
    protected $fillable = [
        'code',
        'nom',
        'institution_categorie_id',
        'ordre',
        'description',
        'actif',
    ];

    protected $casts = [
        'ordre' => 'integer',
        'actif' => 'boolean',
    ];

    // Relations
    public function categorie(): BelongsTo
    {
        return $this->belongsTo(InstitutionCategorie::class, 'institution_categorie_id');
    }

    public function agents(): HasMany
    {
        return $this->hasMany(Agent::class);
    }

    // Scopes
    public function scopeByCategorie($query, $categorieId)
    {
        return $query->where('institution_categorie_id', $categorieId);
    }

    public function scopeActives($query)
    {
        return $query->where('actif', true);
    }

    public function scopeOrderByOrdre($query)
    {
        return $query->orderBy('ordre')->orderBy('nom');
    }
}
