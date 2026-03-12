<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Province extends Model
{
    protected $fillable = [
        'code',
        'nom',
        'description',
        'ville_secretariat',
        'adresse',
        'nom_gouverneur',
        'nom_secretariat_executif',
        'email_officiel',
        'telephone_officiel',
    ];

    // Relations HasMany
    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }

    public function agents(): HasMany
    {
        return $this->hasMany(Agent::class);
    }

    // Scopes
    public function scopeByCode($query, $code)
    {
        return $query->where('code', $code);
    }
}
