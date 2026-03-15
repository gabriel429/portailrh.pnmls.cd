<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'agent_id',
        'role_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the agent associated with this user
     */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    /**
     * Get the role associated with this user
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Check if user has a specific role
     */
    public function hasRole($roles): bool
    {
        if (is_string($roles)) {
            $roles = [$roles];
        }

        if (!$this->role || !$this->role->nom_role) {
            return false;
        }

        $userRole = strtolower(trim((string) $this->role->nom_role));
        $normalizedRoles = array_map(static fn($role) => strtolower(trim((string) $role)), $roles);

        return in_array($userRole, $normalizedRoles, true);
    }

    /**
     * Check if user has full admin access to RH settings.
     */
    public function hasAdminAccess(): bool
    {
        return $this->hasRole([
            'Section ressources humaines',
            'Chef Section RH',
            'RH National',
            'RH Provincial',
        ]);
    }

    /**
     * Check if user is Section Nouvelle Technologie (admin panel access).
     */
    public function isAdminNT(): bool
    {
        return $this->hasRole([
            'Section Nouvelle Technologie',
            'Chef Section Nouvelle Technologie',
            'Chef de Section Nouvelle Technologie',
        ]);
    }
}

