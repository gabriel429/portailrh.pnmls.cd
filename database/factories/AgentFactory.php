<?php

namespace Database\Factories;

use App\Models\Agent;
use App\Models\User;
use App\Models\Department;
use App\Models\Grade;
use App\Models\Fonction;
use App\Models\Province;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Agent>
 */
class AgentFactory extends Factory
{
    protected $model = Agent::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'matricule' => 'PNM-' . str_pad($this->faker->unique()->numberBetween(1, 99999), 6, '0', STR_PAD_LEFT),
            'nom' => $this->faker->lastName(),
            'prenom' => $this->faker->firstName(),
            'postnom' => $this->faker->optional()->lastName(),
            'sexe' => $this->faker->randomElement(['M', 'F']),
            'date_naissance' => $this->faker->dateTimeBetween('-60 years', '-20 years')->format('Y-m-d'),
            'lieu_naissance' => $this->faker->city(),
            'nationalite' => 'Congolaise',
            'etat_civil' => $this->faker->randomElement(['Célibataire', 'Marié(e)', 'Divorcé(e)', 'Veuf(ve)']),
            'nombre_enfants' => $this->faker->numberBetween(0, 6),
            'email' => $this->faker->unique()->safeEmail(),
            'telephone' => '+243' . $this->faker->numerify('#########'),
            'adresse' => $this->faker->address(),
            'date_engagement' => $this->faker->dateTimeBetween('-10 years', 'now')->format('Y-m-d'),
            'date_nomination' => $this->faker->optional()->dateTimeBetween('-5 years', 'now')->format('Y-m-d'),
            'statut' => $this->faker->randomElement(['Actif', 'Inactif', 'Suspendu']),
            'department_id' => Department::factory(),
            'grade_id' => Grade::factory(),
            'fonction_id' => Fonction::factory(),
            'province_id' => Province::factory(),
            'numero_compte' => $this->faker->optional()->bankAccountNumber(),
            'nom_banque' => $this->faker->optional()->company(),
            'photo' => null,
            'cv' => null,
            'contrat' => null,
            'acte_engagement' => null,
            'lettre_nomination' => null,
        ];
    }

    /**
     * Indicate that the agent is active.
     */
    public function active(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'statut' => 'Actif',
            ];
        });
    }

    /**
     * Indicate that the agent is inactive.
     */
    public function inactive(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'statut' => 'Inactif',
            ];
        });
    }

    /**
     * Indicate that the agent is suspended.
     */
    public function suspended(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'statut' => 'Suspendu',
            ];
        });
    }
}