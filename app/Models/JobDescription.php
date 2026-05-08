<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class JobDescription extends Model
{
    protected $fillable = [
        'fonction_id',
        'titre',
        'mission_principale',
        'responsabilites_principales',
        'taches_specifiques',
        'competences_attendues',
        'service_section_departement',
        'actif',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'actif' => 'boolean',
    ];

    public function fonction(): BelongsTo
    {
        return $this->belongsTo(Fonction::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public static function forAgent(Agent $agent): EloquentCollection
    {
        if (!Schema::hasTable('job_descriptions') || !Schema::hasTable('fonctions')) {
            return new EloquentCollection();
        }

        $fonctionIds = self::fonctionIdsForAgent($agent);

        if ($fonctionIds->isEmpty()) {
            return new EloquentCollection();
        }

        return self::with('fonction')
            ->whereIn('fonction_id', $fonctionIds->all())
            ->where('actif', true)
            ->orderBy('titre')
            ->get();
    }

    private static function fonctionIdsForAgent(Agent $agent): Collection
    {
        $agent->loadMissing('affectations');

        $ids = $agent->affectations
            ->where('actif', true)
            ->pluck('fonction_id')
            ->filter()
            ->unique()
            ->values();

        if ($ids->isNotEmpty()) {
            return $ids;
        }

        $names = collect([$agent->fonction, $agent->poste_actuel])
            ->filter()
            ->map(fn ($name) => trim((string) $name))
            ->filter()
            ->unique()
            ->values();

        if ($names->isEmpty()) {
            return collect();
        }

        return Fonction::whereIn('nom', $names->all())->pluck('id');
    }

    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'fonction_id' => $this->fonction_id,
            'titre' => $this->titre,
            'mission_principale' => $this->mission_principale,
            'responsabilites_principales' => $this->responsabilites_principales,
            'taches_specifiques' => $this->taches_specifiques,
            'competences_attendues' => $this->competences_attendues,
            'service_section_departement' => $this->service_section_departement,
            'actif' => (bool) $this->actif,
            'fonction' => $this->relationLoaded('fonction') && $this->fonction ? [
                'id' => $this->fonction->id,
                'nom' => $this->fonction->nom,
                'niveau_administratif' => $this->fonction->niveau_administratif,
                'type_poste' => $this->fonction->type_poste,
            ] : null,
            'created_at' => optional($this->created_at)->toIso8601String(),
            'updated_at' => optional($this->updated_at)->toIso8601String(),
        ];
    }
}
