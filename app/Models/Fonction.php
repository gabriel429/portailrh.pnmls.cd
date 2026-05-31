<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Fonction extends Model
{
    protected $fillable = [
        'nom',
        'niveau_administratif', // SEN | SEP | SEL | TOUS
        'type_poste',           // direction | service_rattache | département | section | cellule | appui | province | local
        'description',
        'est_chef',
    ];

    protected $casts = ['est_chef' => 'boolean'];

    /** Labels lisibles par niveau administratif */
    public static function niveauAdministratifLabel(): array
    {
        return [
            'SEN'  => 'Secrétariat Exécutif National',
            'SEP'  => 'Secrétariat Exécutif Provincial',
            'SEL'  => 'Secrétariat Exécutif Local',
            'TOUS' => 'Tous niveaux',
        ];
    }

    public function affectations(): HasMany
    {
        return $this->hasMany(Affectation::class);
    }

    public function jobDescriptions(): HasMany
    {
        return $this->hasMany(JobDescription::class);
    }

    public function scopeOrderInstitutionally($query)
    {
        $nameExpression = "LOWER(REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(nom, ''), 'é', 'e'), 'è', 'e'), 'ê', 'e'), 'É', 'e'))";

        return $query
            ->orderByRaw("
                CASE
                    WHEN {$nameExpression} LIKE '%secretaire executif national%' AND {$nameExpression} NOT LIKE '%adjoint%' THEN 0
                    WHEN {$nameExpression} LIKE '%secretaire executif national%' THEN 1
                    WHEN niveau_administratif = 'SEN' THEN 2
                    WHEN niveau_administratif = 'SEP' THEN 3
                    WHEN niveau_administratif = 'SEL' THEN 4
                    WHEN niveau_administratif = 'TOUS' THEN 5
                    ELSE 6
                END
            ")
            ->orderByRaw("
                CASE
                    WHEN type_poste = 'direction' THEN 0
                    WHEN type_poste = 'service_rattache' THEN 1
                    WHEN type_poste = 'departement' THEN 2
                    WHEN type_poste = 'département' THEN 2
                    WHEN type_poste = 'section' THEN 3
                    WHEN type_poste = 'cellule' THEN 4
                    WHEN type_poste = 'appui' THEN 5
                    WHEN type_poste = 'province' THEN 6
                    WHEN type_poste = 'local' THEN 7
                    ELSE 8
                END
            ")
            ->orderBy('nom');
    }
}
