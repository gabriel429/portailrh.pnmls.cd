<?php

namespace Database\Factories;

use App\Models\Fonction;
use App\Models\Organe;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Fonction>
 */
class FonctionFactory extends Factory
{
    protected $model = Fonction::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $fonctions = [
            'Secrétaire Exécutif National',
            'Secrétaire Exécutif Provincial',
            'Directeur Administratif et Financier',
            'Directeur Technique',
            'Chef de Section',
            'Chef de Bureau',
            'Comptable',
            'Assistant Administratif',
            'Chargé de Communication',
            'Chargé de Suivi et Évaluation',
            'Gestionnaire de Données',
            'Chauffeur',
            'Agent de Sécurité',
            'Agent d\'Entretien'
        ];

        return [
            'nom' => $this->faker->randomElement($fonctions),
            'description' => $this->faker->optional()->sentence(),
            'organe_id' => Organe::factory(),
        ];
    }
}