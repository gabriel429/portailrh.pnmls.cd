<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Section extends Model
{
    protected $fillable = ['code', 'nom', 'description', 'department_id', 'type'];

    // section | service_rattache
    const TYPE_SECTION  = 'section';
    const TYPE_SERVICE  = 'service_rattache';

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function cellules(): HasMany
    {
        return $this->hasMany(Cellule::class);
    }

    public function affectations(): HasMany
    {
        return $this->hasMany(Affectation::class);
    }

    /**
     * Get agents assigned to this section (via active affectations)
     */
    public function agents()
    {
        return $this->hasManyThrough(
            Agent::class,
            Affectation::class,
            'section_id',   // Foreign key on affectations table
            'id',           // Foreign key on agents table
            'id',           // Local key on sections table
            'agent_id'      // Local key on affectations table
        )->where('affectations.actif', true);
    }

    /** Uniquement les sections de département */
    public function scopeSections($query)
    {
        return $query->where('type', self::TYPE_SECTION);
    }

    /** Uniquement les services rattachés (sous SEN/SENA) */
    public function scopeServicesRattaches($query)
    {
        return $query->where('type', self::TYPE_SERVICE);
    }

    /** Chef actif de cette section ou service rattaché */
    public function chef()
    {
        return $this->affectations()
            ->where('niveau', 'section')
            ->whereHas('fonction', fn($q) => $q->where('est_chef', true))
            ->where('actif', true)
            ->with('agent')
            ->first();
    }
}
