<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class Holiday extends Model
{
    const TYPES_CONGE = [
        'annuel' => 'Congé annuel',
        'maladie' => 'Congé maladie',
        'maternite' => 'Congé maternité',
        'paternite' => 'Congé paternité',
        'urgence' => 'Congé d\'urgence',
        'special' => 'Congé spécial'
    ];

    const STATUTS_DEMANDE = [
        'en_attente' => 'En attente',
        'approuve' => 'Approuvé',
        'refuse' => 'Refusé',
        'annule' => 'Annulé'
    ];

    const STATUT_COLORS = [
        'en_attente' => '#ffc107',
        'approuve' => '#28a745',
        'refuse' => '#dc3545',
        'annule' => '#6c757d'
    ];

    protected $fillable = [
        'agent_id',
        'holiday_planning_id',
        'date_debut',
        'date_fin',
        'nombre_jours',
        'type_conge',
        'statut_demande',
        'motif',
        'observation',
        'interim_assure_par',
        'commentaire_refus',
        'document_medical',
        'lettre_demande',
        'report_possible',
        'date_retour_prevu',
        'date_retour_effectif',
        'demande_par',
        'approuve_par',
        'approuve_le',
        'refuse_par',
        'refuse_le'
    ];

    protected $casts = [
        'date_debut' => 'date',
        'date_fin' => 'date',
        'date_retour_prevu' => 'date',
        'date_retour_effectif' => 'date',
        'nombre_jours' => 'integer',
        'report_possible' => 'boolean',
        'approuve_le' => 'datetime',
        'refuse_le' => 'datetime'
    ];

    // Relations
    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function holidayPlanning(): BelongsTo
    {
        return $this->belongsTo(HolidayPlanning::class);
    }

    public function demandePar(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'demande_par');
    }

    public function approuvePar(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'approuve_par');
    }

    public function refusePar(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'refuse_par');
    }

    public function interimPar(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'interim_assure_par');
    }

    // Accessors
    public function getTypeCongeLabel(): string
    {
        return self::TYPES_CONGE[$this->type_conge] ?? $this->type_conge;
    }

    public function getStatutDemandeLabel(): string
    {
        return self::STATUTS_DEMANDE[$this->statut_demande] ?? $this->statut_demande;
    }

    public function getStatutColorAttribute(): string
    {
        return self::STATUT_COLORS[$this->statut_demande] ?? '#6c757d';
    }

    public function getIsActiveAttribute(): bool
    {
        $today = Carbon::today();
        return $this->statut_demande === 'approuve' &&
               $this->date_debut <= $today &&
               $this->date_fin >= $today;
    }

    public function getIsUpcomingAttribute(): bool
    {
        return $this->statut_demande === 'approuve' &&
               $this->date_debut > Carbon::today();
    }

    public function getIsPastAttribute(): bool
    {
        return $this->date_fin < Carbon::today();
    }

    public function getDureeReelleAttribute(): ?int
    {
        if (!$this->date_retour_effectif) return null;
        return $this->date_debut->diffInDays($this->date_retour_effectif) + 1;
    }

    // Scopes
    public function scopeByAgent($query, int $agentId)
    {
        return $query->where('agent_id', $agentId);
    }

    public function scopeByStatut($query, string $statut)
    {
        return $query->where('statut_demande', $statut);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type_conge', $type);
    }

    public function scopeForYear($query, int $year)
    {
        return $query->whereYear('date_debut', $year);
    }

    public function scopeActive($query, ?Carbon $date = null)
    {
        $date = $date ?: Carbon::today();
        return $query->where('statut_demande', 'approuve')
                     ->where('date_debut', '<=', $date)
                     ->where('date_fin', '>=', $date);
    }

    public function scopeBetween($query, Carbon $start, Carbon $end)
    {
        return $query->where(function ($q) use ($start, $end) {
            $q->whereBetween('date_debut', [$start, $end])
              ->orWhereBetween('date_fin', [$start, $end])
              ->orWhere(function ($subQ) use ($start, $end) {
                  $subQ->where('date_debut', '<=', $start)
                       ->where('date_fin', '>=', $end);
              });
        });
    }

    public function scopePending($query)
    {
        return $query->where('statut_demande', 'en_attente');
    }

    public function scopeApproved($query)
    {
        return $query->where('statut_demande', 'approuve');
    }

    // Méthodes
    public function approve(Agent $approver): bool
    {
        $result = $this->update([
            'statut_demande' => 'approuve',
            'approuve_par' => $approver->id,
            'approuve_le' => now(),
            'refuse_par' => null,
            'refuse_le' => null,
            'commentaire_refus' => null
        ]);

        if ($result && $this->holidayPlanning) {
            $this->holidayPlanning->incrementJoursUtilises($this->nombre_jours);
        }

        return $result;
    }

    public function refuse(Agent $refuser, string $motif): bool
    {
        return $this->update([
            'statut_demande' => 'refuse',
            'refuse_par' => $refuser->id,
            'refuse_le' => now(),
            'commentaire_refus' => $motif,
            'approuve_par' => null,
            'approuve_le' => null
        ]);
    }

    public function cancel(): bool
    {
        $wasApproved = $this->statut_demande === 'approuve';

        $result = $this->update(['statut_demande' => 'annule']);

        if ($result && $wasApproved && $this->holidayPlanning) {
            $this->holidayPlanning->decrementJoursUtilises($this->nombre_jours);
        }

        return $result;
    }

    public function markReturned(?Carbon $dateRetour = null): bool
    {
        return $this->update([
            'date_retour_effectif' => $dateRetour ?: Carbon::today()
        ]);
    }

    // Méthodes statiques
    public static function calculateWorkingDays(Carbon $start, Carbon $end): int
    {
        $days = 0;
        $current = $start->copy();

        while ($current <= $end) {
            if ($current->isWeekday()) {
                $days++;
            }
            $current->addDay();
        }

        return $days;
    }

    public static function hasConflict(int $agentId, Carbon $start, Carbon $end, ?int $excludeId = null): bool
    {
        $query = self::where('agent_id', $agentId)
                     ->where('statut_demande', 'approuve')
                     ->between($start, $end);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    // Boot method
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($holiday) {
            if (!$holiday->nombre_jours) {
                $holiday->nombre_jours = self::calculateWorkingDays(
                    $holiday->date_debut,
                    $holiday->date_fin
                );
            }

            if (!$holiday->date_retour_prevu) {
                $holiday->date_retour_prevu = $holiday->date_fin->copy()->addDay();
            }
        });

        static::updating(function ($holiday) {
            if ($holiday->isDirty(['date_debut', 'date_fin'])) {
                $holiday->nombre_jours = self::calculateWorkingDays(
                    $holiday->date_debut,
                    $holiday->date_fin
                );
                $holiday->date_retour_prevu = $holiday->date_fin->copy()->addDay();
            }
        });
    }
}
