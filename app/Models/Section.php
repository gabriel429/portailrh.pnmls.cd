<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Section extends Model
{
    protected $fillable = ['code', 'nom', 'description', 'department_id'];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function cellules(): HasMany
    {
        return $this->hasMany(Cellule::class);
    }

    public function affectations(): HasMany
    {
        return $this->hasMany(Affectation::class);
    }

    /** Chef de section actif */
    public function chef()
    {
        return $this->affectations()
            ->where('niveau', 'section')
            ->whereHas('fonction', fn($q) => $q->where('est_chef', true))
            ->where('actif', true)
            ->with('agent')
            ->first();
    }
}
