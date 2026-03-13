<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Fonction extends Model
{
    protected $fillable = ['nom', 'niveau', 'description', 'est_chef'];

    protected $casts = ['est_chef' => 'boolean'];

    public function affectations(): HasMany
    {
        return $this->hasMany(Affectation::class);
    }
}
