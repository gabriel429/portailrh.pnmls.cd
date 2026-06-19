<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\Province;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Department>
 */
class DepartmentFactory extends Factory
{
    protected $model = Department::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $departments = [
            'Direction Générale',
            'Ressources Humaines',
            'Finance et Comptabilité',
            'Logistique',
            'Communication',
            'Suivi et Évaluation',
            'Administration',
            'Informatique',
            'Juridique',
            'Audit Interne'
        ];

        return [
            'code' => strtoupper($this->faker->unique()->lexify('DEP-???')),
            'nom' => $this->faker->randomElement($departments) . ' ' . $this->faker->optional()->city(),
            'description' => $this->faker->optional()->paragraph(),
            'province_id' => Province::factory(),
            'chef_id' => null, // Will be set after creating agents
            'est_pris_en_charge' => $this->faker->boolean(80), // 80% probability of true
        ];
    }

    /**
     * Indicate that the department is pris en charge.
     */
    public function prisEnCharge(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'est_pris_en_charge' => true,
            ];
        });
    }

    /**
     * Indicate that the department is not pris en charge.
     */
    public function nonPrisEnCharge(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'est_pris_en_charge' => false,
            ];
        });
    }
}