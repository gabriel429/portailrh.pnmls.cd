<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CategorieDocument extends Model
{
    protected $fillable = ['nom', 'icone', 'actif'];

    protected $casts = ['actif' => 'boolean'];

    public function scopeActives($query)
    {
        return $query->where('actif', true);
    }
}
