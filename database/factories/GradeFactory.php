<?php

namespace Database\Factories;

use App\Models\Grade;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Grade>
 */
class GradeFactory extends Factory
{
    protected $model = Grade::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $grades = [
            'Directeur',
            'Directeur Adjoint',
            'Chef de Division',
            'Chef de Bureau',
            'Attaché de Bureau 1ère Classe',
            'Attaché de Bureau 2ème Classe',
            'Agent de Bureau 1ère Classe',
            'Agent de Bureau 2ème Classe',
            'Agent Auxiliaire 1ère Classe',
            'Agent Auxiliaire 2ème Classe',
            'Huissier'
        ];

        return [
            'nom' => $this->faker->unique()->randomElement($grades),
            'niveau' => $this->faker->numberBetween(1, 11),
            'description' => $this->faker->optional()->sentence(),
        ];
    }
}