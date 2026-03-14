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
}
