<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\Affectation;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Models\Grade;
use App\Traits\Syncable;
use App\Support\RoleNames;

class Agent extends Authenticatable
{
    use Syncable;

    const NIVEAUX_ETUDES = [
        'PP2',
        'PP3',
        'PP4',
        'PP5',
        'Certificat d\'Études Primaires (CEP)',
        '1ère secondaire',
        '2e secondaire',
        'Brevet',
        'D4',
        'D5',
        'D6',
        'Diplôme d\'État',
        'Graduat',
        'Licence',
        'Diplôme d\'Études Supérieures (DES)',
        'Master',
        'Doctorat (PhD)',
        'Postdoctorat',
        'Professeur d\'université',
    ];

    const ORGANES = [
        'Secrétariat Exécutif National',
        'Secrétariat Exécutif Provincial',
        'Secrétariat Exécutif Local',
    ];

    protected $fillable = [
        'matricule_etat',
        'provenance_matricule',
        'nom',
        'postnom',
        'prenom',
        'email',
        'email_prive',
        'email_professionnel',
        'password',
        'photo',
        'date_naissance',
        'annee_naissance',
        'lieu_naissance',
        'sexe',
        'situation_familiale',
        'nombre_enfants',
        'telephone',
        'telephone_professionnel',
        'telephone_prive',
        'adresse',
        'organe',
        'poste_actuel',
        'fonction',
        'grade_etat',
        'niveau_etudes',
        'domaine_etudes',
        'annee_engagement_programme',
        'departement_id',
        'province_id',
        'localite_id',
        'role_id',
        'grade_id',
        'institution_id',
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
        'annee_naissance' => 'integer',
        'annee_engagement_programme' => 'integer',
    ];

    /**
     * Formatted agent ID (e.g. AGT-0001)
     */
    public function getIdAgentAttribute(): string
    {
        return 'AGT-' . str_pad($this->id, 4, '0', STR_PAD_LEFT);
    }

    // Relations BelongsTo
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function grade(): BelongsTo
    {
        return $this->belongsTo(Grade::class);
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }

    public function localite(): BelongsTo
    {
        return $this->belongsTo(Localite::class);
    }

    public function departement(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'departement_id');
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
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

    public function affectations(): HasMany
    {
        return $this->hasMany(Affectation::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function forumPosts(): HasMany
    {
        return $this->hasMany(ForumPost::class);
    }

    public function forumComments(): HasMany
    {
        return $this->hasMany(ForumComment::class);
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    public function tachesCreees(): HasMany
    {
        return $this->hasMany(Tache::class, 'createur_id');
    }

    public function tachesAssignees(): HasMany
    {
        return $this->hasMany(Tache::class, 'agent_id');
    }

    public function activitesPlans(): HasMany
    {
        return $this->hasMany(ActivitePlan::class, 'createur_id');
    }

    public function holidays(): HasMany
    {
        return $this->hasMany(Holiday::class);
    }

    public function agentStatuses(): HasMany
    {
        return $this->hasMany(AgentStatus::class);
    }

    public function idCards(): HasMany
    {
        return $this->hasMany(AgentIdCard::class);
    }

    public function activeIdCard(): HasOne
    {
        return $this->hasOne(AgentIdCard::class)
            ->whereNull('revoked_at')
            ->whereDate('expires_at', '>=', now()->toDateString())
            ->latestOfMany();
    }

    public function currentStatus(): ?AgentStatus
    {
        return $this->agentStatuses()
                    ->where('actuel', true)
                    ->orderBy('created_at', 'desc')
                    ->first();
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

    public function scopeOrderInstitutionally($query)
    {
        $roleExpression = "LOWER(REPLACE(REPLACE(REPLACE(REPLACE(CONCAT_WS(' ', COALESCE(poste_actuel, ''), COALESCE(fonction, '')), 'é', 'e'), 'è', 'e'), 'ê', 'e'), 'É', 'e'))";
        $structureExpression = "LOWER(REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(organe, ''), 'é', 'e'), 'è', 'e'), 'ê', 'e'), 'É', 'e'))";

        return $query
            ->orderByRaw("
                CASE
                    WHEN {$roleExpression} LIKE '%secretaire executif national%' AND {$roleExpression} NOT LIKE '%adjoint%' THEN 0
                    WHEN {$structureExpression} LIKE '%national%' THEN 1
                    WHEN {$structureExpression} LIKE '%provincial%' THEN 2
                    WHEN {$structureExpression} LIKE '%local%' THEN 3
                    ELSE 4
                END
            ")
            ->orderByRaw("
                CASE
                    WHEN {$roleExpression} LIKE '%secretaire executif national%' AND {$roleExpression} NOT LIKE '%adjoint%' THEN 0
                    WHEN {$roleExpression} LIKE '%secretaire executif%' THEN 1
                    WHEN {$roleExpression} LIKE '%directeur%' THEN 2
                    WHEN {$roleExpression} LIKE '%chef%section%' THEN 3
                    WHEN {$roleExpression} LIKE '%chef%cellule%' THEN 4
                    WHEN {$roleExpression} LIKE '%assistant%' THEN 5
                    WHEN {$roleExpression} LIKE '%secretaire%' THEN 5
                    ELSE 6
                END
            ")
            ->orderBy('nom')
            ->orderBy('postnom')
            ->orderBy('prenom');
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

        return RoleNames::matches($this->role->nom_role, $roles);
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
            'SEN',
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
        return $this->permissions()->get();
    }
}
