<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use App\Traits\Syncable;

class DocumentTravail extends Model
{
    use Syncable;

    protected $fillable = [
        'titre',
        'description',
        'categorie',
        'fichier',
        'type_fichier',
        'taille',
        'uploaded_by',
        'actif',
    ];

    protected $casts = [
        'actif' => 'boolean',
    ];

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function scopeActifs($query)
    {
        return $query->where('actif', true);
    }
}
