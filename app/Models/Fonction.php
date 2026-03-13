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
}
