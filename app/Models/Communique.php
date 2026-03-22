<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use App\Traits\Syncable;

class Communique extends Model
{
    use Syncable;

    protected $fillable = [
        'auteur_id',
        'titre',
        'contenu',
        'urgence',
        'signataire',
        'date_expiration',
        'actif',
    ];

    protected $casts = [
        'date_expiration' => 'date',
        'actif' => 'boolean',
    ];

    public function auteur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'auteur_id');
    }

    public function scopeVisibles($query)
    {
        return $query->where('actif', true)
            ->where(function ($q) {
                $q->whereNull('date_expiration')
                  ->orWhere('date_expiration', '>=', now()->toDateString());
            });
    }

    public function scopeUrgent($query)
    {
        return $query->where('urgence', 'urgent');
    }
}
