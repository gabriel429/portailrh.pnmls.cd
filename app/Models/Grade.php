<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Grade extends Model
{
    protected $fillable = [
        'categorie',
        'nom_categorie',
        'ordre',
        'libelle',
    ];

    protected $casts = [
        'ordre' => 'integer',
    ];

    public function agents(): HasMany
    {
        return $this->hasMany(Agent::class);
    }

    // Scope par catégorie
    public function scopeCategorie($query, string $categorie)
    {
        return $query->where('categorie', $categorie);
    }
}
