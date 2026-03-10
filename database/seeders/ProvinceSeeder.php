<?php

namespace Database\Seeders;

use App\Models\Province;
use Illuminate\Database\Seeder;

class ProvinceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $provinces = [
            [
                'code' => 'KIN',
                'nom' => 'Kinshasa',
                'description' => 'Province de Kinshasa - Capitale',
            ],
            [
                'code' => 'KAS',
                'nom' => 'Kasai',
                'description' => 'Province du Kasai',
            ],
            [
                'code' => 'KCE',
                'nom' => 'Kasai Central',
                'description' => 'Province du Kasai Central',
            ],
            [
                'code' => 'KOR',
                'nom' => 'Kasai Oriental',
                'description' => 'Province du Kasai Oriental',
            ],
            [
                'code' => 'LOF',
                'nom' => 'Lualaba',
                'description' => 'Province de Lualaba',
            ],
            [
                'code' => 'HAU',
                'nom' => 'Haut-Katanga',
                'description' => 'Province du Haut-Katanga',
            ],
            [
                'code' => 'TAN',
                'nom' => 'Tanganyika',
                'description' => 'Province de Tanganyika',
            ],
            [
                'code' => 'SUD',
                'nom' => 'Sud-Kivu',
                'description' => 'Province du Sud-Kivu',
            ],
            [
                'code' => 'NOR',
                'nom' => 'Nord-Kivu',
                'description' => 'Province du Nord-Kivu',
            ],
            [
                'code' => 'MAN',
                'nom' => 'Maniema',
                'description' => 'Province de Maniema',
            ],
        ];

        foreach ($provinces as $province) {
            Province::create($province);
        }
    }
}
