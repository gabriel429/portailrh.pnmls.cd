<?php

namespace Database\Factories;

use App\Models\Organe;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Organe>
 */
class OrganeFactory extends Factory
{
    protected $model = Organe::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $organes = [
            ['code' => 'SEN', 'nom' => 'Secrétariat Exécutif National'],
            ['code' => 'SEP', 'nom' => 'Secrétariat Exécutif Provincial'],
            ['code' => 'CAF', 'nom' => 'Cellule Administrative et Financière'],
            ['code' => 'CTP', 'nom' => 'Cellule Technique et Programmatique'],
            ['code' => 'CPL', 'nom' => 'Cellule de Planification'],
            ['code' => 'CSE', 'nom' => 'Cellule de Suivi et Évaluation'],
        ];

        $organe = $this->faker->randomElement($organes);

        return [
            'code' => $organe['code'] . $this->faker->optional()->numerify('-##'),
            'nom' => $organe['nom'],
            'description' => $this->faker->optional()->paragraph(),
        ];
    }
}