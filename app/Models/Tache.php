<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

use App\Traits\Syncable;

class Tache extends Model
{
    use Syncable;

    protected $fillable = [
        'createur_id',
        'agent_id',
        'titre',
        'description',
        'source_type',
        'source_emetteur',
        'niveau_gestion',
        'validation_responsable_role',
        'activite_plan_id',
        'priorite',
        'statut',
        'pourcentage',
        'validation_statut',
        'validation_commentaire',
        'soumise_validation_at',
        'validated_by',
        'validated_at',
        'rejected_by',
        'rejected_at',
        'blocked_by',
        'blocked_at',
        'blocking_reason',
        'date_echeance',
        'date_tache',
    ];

    protected $casts = [
        'pourcentage' => 'integer',
        'date_echeance' => 'date',
        'date_tache' => 'date',
        'soumise_validation_at' => 'datetime',
        'validated_at' => 'datetime',
        'rejected_at' => 'datetime',
        'blocked_at' => 'datetime',
    ];

    public function createur(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'createur_id');
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'agent_id');
    }

    public function activitePlan(): BelongsTo
    {
        return $this->belongsTo(ActivitePlan::class, 'activite_plan_id');
    }

    public function commentaires(): HasMany
    {
        return $this->hasMany(TacheCommentaire::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(TacheDocument::class)->latest();
    }

    public function histories(): HasMany
    {
        return $this->hasMany(TacheHistory::class)->latest();
    }

    public function validateur(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'validated_by');
    }

    public function rejecteur(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'rejected_by');
    }

    public function bloqueur(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'blocked_by');
    }

    public function scopeNouvelle($query)
    {
        return $query->where('statut', 'nouvelle');
    }

    public function scopeEnCours($query)
    {
        return $query->where('statut', 'en_cours');
    }

    public function scopeTerminee($query)
    {
        return $query->where('statut', 'terminee');
    }

    public function scopeParCreateur($query, $createurId)
    {
        return $query->where('createur_id', $createurId);
    }

    public function scopeParAgent($query, $agentId)
    {
        return $query->where('agent_id', $agentId);
    }
}
