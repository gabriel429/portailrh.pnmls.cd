<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommuniqueRead extends Model
{
    protected $fillable = [
        'communique_id',
        'user_id',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function communique(): BelongsTo
    {
        return $this->belongsTo(Communique::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
