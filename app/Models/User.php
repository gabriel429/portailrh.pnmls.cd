<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Traits\Syncable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, Syncable;

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
        'is_super_admin',
        'is_frozen',
        'last_login_ip',
        'last_login_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'is_super_admin',
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
            'is_super_admin' => 'boolean',
            'is_frozen' => 'boolean',
            'last_login_at' => 'datetime',
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
     * Check if user is a SuperAdmin.
     */
    public function isSuperAdmin(): bool
    {
        return (bool) $this->is_super_admin;
    }

    /**
     * Check if user has full admin access to RH settings.
     */
    public function hasAdminAccess(): bool
    {
        if ($this->isSuperAdmin()) return true;

        return $this->hasRole([
            'Section ressources humaines',
            'Chef Section RH',
            'RH National',
            'RH Provincial',
            'SEN',
        ]);
    }

    /**
     * Check if user is Section Nouvelle Technologie (admin panel access).
     */
    public function isAdminNT(): bool
    {
        if ($this->isSuperAdmin()) return true;

        return $this->hasRole([
            'Section Nouvelle Technologie',
            'Chef Section Nouvelle Technologie',
            'Chef de Section Nouvelle Technologie',
        ]);
    }

    /**
     * Check if user has a specific permission (via role or direct agent assignment).
     */
    public function hasPermission(string $code): bool
    {
        if ($this->isSuperAdmin()) return true;

        // Check role permissions
        if ($this->role && $this->role->permissions()->where('code', $code)->exists()) {
            return true;
        }

        // Check direct agent permissions
        if ($this->agent && $this->agent->permissions()->where('code', $code)->exists()) {
            return true;
        }

        return false;
    }

    /**
     * Check if user has any of the given permissions.
     */
    public function hasAnyPermission(array $codes): bool
    {
        if ($this->isSuperAdmin()) return true;

        foreach ($codes as $code) {
            if ($this->hasPermission($code)) return true;
        }
        return false;
    }
}

