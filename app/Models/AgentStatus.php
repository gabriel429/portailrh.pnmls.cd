<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class AgentStatus extends Model
{
    const STATUTS = [
        'disponible' => 'Disponible',
        'en_conge' => 'En congé',
        'en_mission' => 'En mission',
        'suspendu' => 'Suspendu',
        'en_formation' => 'En formation'
    ];

    const STATUT_COLORS = [
        'disponible' => '#28a745',
        'en_conge' => '#007bff',
        'en_mission' => '#ffc107',
        'suspendu' => '#dc3545',
        'en_formation' => '#6f42c1'
    ];

    protected $fillable = [
        'agent_id',
        'statut',
        'date_debut',
        'date_fin',
        'motif',
        'commentaire',
        'document_joint',
        'actuel',
        'created_by',
        'approved_by',
        'approved_at'
    ];

    protected $casts = [
        'date_debut' => 'date',
        'date_fin' => 'date',
        'actuel' => 'boolean',
        'approved_at' => 'datetime'
    ];

    // Relations
    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'created_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'approved_by');
    }

    // Accessors
    public function getStatutLabelAttribute(): string
    {
        return self::STATUTS[$this->statut] ?? $this->statut;
    }

    public function getStatutColorAttribute(): string
    {
        return self::STATUT_COLORS[$this->statut] ?? '#6c757d';
    }

    public function getDureeAttribute(): ?int
    {
        if (!$this->date_fin) return null;
        return $this->date_debut->diffInDays($this->date_fin) + 1;
    }

    public function getIsActiveAttribute(): bool
    {
        $today = Carbon::today();
        return $this->actuel &&
               $this->date_debut <= $today &&
               (!$this->date_fin || $this->date_fin >= $today);
    }

    public function getIsExpiredAttribute(): bool
    {
        if (!$this->date_fin) return false;
        return Carbon::today() > $this->date_fin;
    }

    // Scopes
    public function scopeActuel($query)
    {
        return $query->where('actuel', true);
    }

    public function scopeForAgent($query, int $agentId)
    {
        return $query->where('agent_id', $agentId);
    }

    public function scopeByStatut($query, string $statut)
    {
        return $query->where('statut', $statut);
    }

    public function scopeActiveBetween($query, Carbon $start, Carbon $end)
    {
        return $query->where(function ($q) use ($start, $end) {
            $q->where('date_debut', '<=', $end)
              ->where(function ($subQ) use ($start) {
                  $subQ->whereNull('date_fin')
                       ->orWhere('date_fin', '>=', $start);
              });
        });
    }

    public function scopeApproved($query)
    {
        return $query->whereNotNull('approved_by');
    }

    // Méthodes statiques
    public static function getCurrentStatus(int $agentId): ?self
    {
        return self::where('agent_id', $agentId)
                   ->where('actuel', true)
                   ->orderBy('created_at', 'desc')
                   ->first();
    }

    public static function setNewStatus(int $agentId, array $statusData): self
    {
        // Marquer tous les anciens statuts comme non actuels
        self::where('agent_id', $agentId)->update(['actuel' => false]);

        // Créer le nouveau statut
        return self::create(array_merge($statusData, [
            'agent_id' => $agentId,
            'actuel' => true
        ]));
    }

    // Méthodes
    public function approve(Agent $approver): bool
    {
        return $this->update([
            'approved_by' => $approver->id,
            'approved_at' => now()
        ]);
    }

    public function extend(Carbon $newEndDate): bool
    {
        return $this->update(['date_fin' => $newEndDate]);
    }
}
