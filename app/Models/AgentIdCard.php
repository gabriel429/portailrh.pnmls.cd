<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentIdCard extends Model
{
    protected $fillable = [
        'agent_id',
        'issued_by',
        'serial',
        'token',
        'issued_at',
        'expires_at',
        'revoked_at',
    ];

    protected $casts = [
        'issued_at' => 'date',
        'expires_at' => 'date',
        'revoked_at' => 'datetime',
    ];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function getStatusAttribute(): string
    {
        if ($this->revoked_at) {
            return 'revoked';
        }

        if ($this->expires_at && $this->expires_at->toDateString() < now()->toDateString()) {
            return 'expired';
        }

        return 'valid';
    }
}
