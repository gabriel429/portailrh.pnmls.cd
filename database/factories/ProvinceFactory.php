<?php

namespace Database\Factories;

use App\Models\Province;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Province>
 */
class ProvinceFactory extends Factory
{
    protected $model = Province::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $provinces = [
            'Kinshasa',
            'Kongo-Central', 
            'Kwango',
            'Kwilu',
            'Mai-Ndombe',
            'Kasaï',
            'Kasaï-Central',
            'Kasaï-Oriental',
            'Lomami',
            'Sankuru',
            'Maniema',
            'Sud-Kivu',
            'Nord-Kivu',
            'Ituri',
            'Haut-Uele',
            'Tshopo',
            'Bas-Uele',
            'Nord-Ubangi',
            'Mongala',
            'Sud-Ubangi',
            'Équateur',
            'Tshuapa',
            'Tanganyika',
            'Haut-Lomami',
            'Lualaba',
            'Haut-Katanga'
        ];

        return [
            'nom' => $this->faker->unique()->randomElement($provinces),
            'code' => strtoupper($this->faker->unique()->lexify('??')),
            'chef_lieu' => $this->faker->city(),
        ];
    }
}