<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    protected $fillable = [
        'user_id',
        'user_name',
        'table_name',
        'record_id',
        'action',
        'donnees_avant',
        'donnees_apres',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'donnees_avant' => 'json',
        'donnees_apres' => 'json',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
