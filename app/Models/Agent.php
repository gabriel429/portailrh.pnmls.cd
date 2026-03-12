<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Agent extends Authenticatable
{
    protected $fillable = [
        'matricule_pnmls',
        'matricule_etat',
        'nom',
        'prenom',
        'email',
        'password',
        'photo',
        'date_naissance',
        'lieu_naissance',
        'situation_familiale',
        'nombre_enfants',
        'telephone',
        'adresse',
        'poste_actuel',
        'departement_id',
        'province_id',
        'role_id',
        'date_embauche',
        'statut',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'date_naissance' => 'date',
        'date_embauche' => 'date',
    ];

    // Relations BelongsTo
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }

    public function departement(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'departement_id');
    }

    // Relations HasMany
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function requests(): HasMany
    {
        return $this->hasMany(Request::class);
    }

    public function signalements(): HasMany
    {
        return $this->hasMany(Signalement::class);
    }

    public function pointages(): HasMany
    {
        return $this->hasMany(Pointage::class);
    }

    public function historique_modifications(): HasMany
    {
        return $this->hasMany(HistoriqueModification::class);
    }

    // Relations BelongsToMany
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'agent_permission')->withTimestamps();
    }

    // Scopes
    public function scopeActifs($query)
    {
        return $query->where('statut', 'actif');
    }

    public function scopeSuspendu($query)
    {
        return $query->where('statut', 'suspendu');
    }

    public function scopeAnciens($query)
    {
        return $query->where('statut', 'ancien');
    }

    // Accessors
    public function getNomCompletAttribute()
    {
        return "{$this->prenom} {$this->nom}";
    }

    /**
     * Check if user has role
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
            'Chef Section RH',
            'RH National',
            'RH Provincial',
            'Chef Section Nouvelle Technologie',
            'Chef de Section Nouvelle Technologie',
        ]);
    }

    /**
     * Assign role to user
     */
    public function assignRole($roleName)
    {
        $role = Role::where('nom_role', $roleName)->first();
        if ($role) {
            $this->update(['role_id' => $role->id]);
        }
    }

    /**
     * Get user's permissions
     */
    public function getPermissionsAttribute()
    {
        if ($this->role) {
            return $this->role->permissions;
        }
        return $this->permissions;
    }
}

