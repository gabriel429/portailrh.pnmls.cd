<?php

namespace Database\Factories;

use App\Models\Holiday;
use App\Models\Agent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Holiday>
 */
class HolidayFactory extends Factory
{
    protected $model = Holiday::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $dateDebut = $this->faker->dateTimeBetween('now', '+3 months');
        $dateFin = $this->faker->dateTimeBetween($dateDebut, '+30 days');
        $nombreJours = $dateDebut->diff($dateFin)->days + 1;

        return [
            'agent_id' => Agent::factory(),
            'type' => $this->faker->randomElement(['Congé annuel', 'Congé maladie', 'Congé maternité', 'Congé paternité', 'Permission exceptionnelle']),
            'date_debut' => $dateDebut->format('Y-m-d'),
            'date_fin' => $dateFin->format('Y-m-d'),
            'nombre_jours' => $nombreJours,
            'motif' => $this->faker->sentence(),
            'statut' => $this->faker->randomElement(['En attente', 'Approuvé', 'Refusé', 'Annulé']),
            'validated_by' => null,
            'validated_at' => null,
            'raison_refus' => null,
            'date_retour_effectif' => null,
            'commentaire_retour' => null,
        ];
    }

    /**
     * Indicate that the holiday is pending.
     */
    public function pending(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'statut' => 'En attente',
                'validated_by' => null,
                'validated_at' => null,
            ];
        });
    }

    /**
     * Indicate that the holiday is approved.
     */
    public function approved(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'statut' => 'Approuvé',
                'validated_by' => Agent::factory(),
                'validated_at' => $this->faker->dateTimeBetween('-7 days', 'now'),
            ];
        });
    }

    /**
     * Indicate that the holiday is refused.
     */
    public function refused(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'statut' => 'Refusé',
                'validated_by' => Agent::factory(),
                'validated_at' => $this->faker->dateTimeBetween('-7 days', 'now'),
                'raison_refus' => $this->faker->sentence(),
            ];
        });
    }

    /**
     * Indicate that the holiday is cancelled.
     */
    public function cancelled(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'statut' => 'Annulé',
            ];
        });
    }
}