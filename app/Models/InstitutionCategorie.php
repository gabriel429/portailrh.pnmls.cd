<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InstitutionCategorie extends Model
{
    protected $table = 'institution_categories';

    protected $fillable = [
        'code',
        'nom',
        'ordre',
        'description',
    ];

    protected $casts = [
        'ordre' => 'integer',
    ];

    // Relations
    public function institutions(): HasMany
    {
        return $this->hasMany(Institution::class);
    }

    // Scopes
    public function scopeOrderByOrdre($query)
    {
        return $query->orderBy('ordre')->orderBy('nom');
    }

    public function scopeActives($query)
    {
        return $query->where('actif', true);
    }
}
