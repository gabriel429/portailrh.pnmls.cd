<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Localite extends Model
{
    protected $fillable = ['code', 'nom', 'type', 'description', 'province_id'];

    public static function types(): array
    {
        return [
            'territoire'    => 'Territoire',
            'zone_de_sante' => 'Zone de Santé',
            'commune'       => 'Commune',
            'ville'         => 'Ville',
            'autre'         => 'Autre',
        ];
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }

    public function affectations(): HasMany
    {
        return $this->hasMany(Affectation::class);
    }

    /** Secrétaire Exécutif Local actif de cette localité */
    public function sel()
    {
        return $this->affectations()
            ->where('niveau', 'local')
            ->whereHas('fonction', fn($q) => $q->where('est_chef', true))
            ->where('actif', true)
            ->with('agent')
            ->first();
    }
}
