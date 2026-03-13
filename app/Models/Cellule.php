<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cellule extends Model
{
    protected $fillable = ['code', 'nom', 'description', 'section_id'];

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function affectations(): HasMany
    {
        return $this->hasMany(Affectation::class);
    }
}
