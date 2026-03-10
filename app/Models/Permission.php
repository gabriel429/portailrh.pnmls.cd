<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    protected $fillable = [
        'nom',
        'code',
        'description',
    ];

    // Relations BelongsToMany
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_permission')->withTimestamps();
    }

    public function agents(): BelongsToMany
    {
        return $this->belongsToMany(Agent::class, 'agent_permission')->withTimestamps();
    }

    // Scopes
    public function scopeByCode($query, $code)
    {
        return $query->where('code', $code);
    }
}

