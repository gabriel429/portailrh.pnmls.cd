<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class ForumPost extends Model
{
    use SoftDeletes;

    public const LIFETIME_DAYS = 14;

    protected $fillable = [
        'user_id',
        'agent_id',
        'titre',
        'contenu',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(ForumComment::class);
    }

    public function scopeActive($query)
    {
        return $query->where('created_at', '>=', now()->subDays(self::LIFETIME_DAYS));
    }

    public function expiresAt(): ?Carbon
    {
        return $this->created_at?->copy()->addDays(self::LIFETIME_DAYS);
    }

    public function isExpired(): bool
    {
        $expiresAt = $this->expiresAt();

        return $expiresAt !== null && $expiresAt->isPast();
    }
}
