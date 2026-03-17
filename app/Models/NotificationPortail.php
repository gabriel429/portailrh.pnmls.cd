<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationPortail extends Model
{
    protected $table = 'notifications_portail';

    protected $fillable = [
        'user_id', 'type', 'titre', 'message', 'icone', 'couleur',
        'lien', 'emetteur_id', 'lu', 'lu_at',
    ];

    protected $casts = [
        'lu' => 'boolean',
        'lu_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function emetteur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'emetteur_id');
    }

    public function scopeNonLues($query)
    {
        return $query->where('lu', false);
    }

    public function scopePourUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function marquerCommeLue(): void
    {
        $this->update(['lu' => true, 'lu_at' => now()]);
    }
}
