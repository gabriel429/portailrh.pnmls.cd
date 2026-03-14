<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organe extends Model
{
    protected $fillable = [
        'code',
        'nom',
        'sigle',
        'description',
        'actif',
    ];

    protected $casts = [
        'actif' => 'boolean',
    ];

    public function affectations(): HasMany
    {
        return $this->hasMany(Affectation::class);
    }
}
