<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CongeRegleDepartement extends Model
{
    protected $table = 'conges_regles_departement';

    protected $fillable = [
        'departement_id',
        'max_consecutif',
        'taux_absent_max',
        'jours_annuels',
    ];

    protected $casts = [
        'taux_absent_max' => 'decimal:2',
    ];

    public function departement(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'departement_id');
    }
}
