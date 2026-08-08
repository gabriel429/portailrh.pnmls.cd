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
        $dateDebut = now()->addDays($this->faker->numberBetween(7, 60))->startOfDay();
        $dateFin = $dateDebut->copy()->addDays($this->faker->numberBetween(1, 10));

        return [
            'agent_id' => Agent::factory(),
            'date_debut' => $dateDebut,
            'date_fin' => $dateFin,
            'nombre_jours' => Holiday::calculateWorkingDays($dateDebut, $dateFin),
            'date_retour_prevu' => Holiday::nextWorkingDayAfter($dateFin),
            'type_conge' => $this->faker->randomElement(array_keys(Holiday::TYPES_CONGE)),
            'motif' => $this->faker->sentence(),
            'statut_demande' => 'en_attente',
            'demande_par' => Agent::factory(),
        ];
    }

    /**
     * Indicate that the holiday is pending.
     */
    public function pending(): Factory
    {
        return $this->state(fn() => [
            'statut_demande' => 'en_attente',
            'approuve_par' => null,
            'approuve_le' => null,
            'refuse_par' => null,
            'refuse_le' => null,
        ]);
    }

    /**
     * Indicate that the holiday is approved.
     */
    public function approved(): Factory
    {
        return $this->state(fn() => [
            'statut_demande' => 'approuve',
            'approuve_par' => Agent::factory(),
            'approuve_le' => now(),
        ]);
    }

    /**
     * Indicate that the holiday is refused.
     */
    public function refused(): Factory
    {
        return $this->state(fn() => [
            'statut_demande' => 'refuse',
            'refuse_par' => Agent::factory(),
            'refuse_le' => now(),
            'commentaire_refus' => $this->faker->sentence(),
        ]);
    }

    /**
     * Indicate that the holiday is cancelled.
     */
    public function cancelled(): Factory
    {
        return $this->state(fn() => ['statut_demande' => 'annule']);
    }
}