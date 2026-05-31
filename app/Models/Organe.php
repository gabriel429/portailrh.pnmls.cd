<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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

    public function scopeOrderInstitutionally($query)
    {
        return $query
            ->orderByRaw("
                CASE
                    WHEN code = 'SEN' THEN 0
                    WHEN code = 'SEP' THEN 1
                    WHEN code = 'SEL' THEN 2
                    ELSE 3
                END
            ")
            ->orderBy('nom');
    }
}
