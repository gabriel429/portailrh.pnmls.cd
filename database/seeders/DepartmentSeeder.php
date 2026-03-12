<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Province;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtenir les provinces
        $kinshasa = Province::where('code', 'KIN')->first();
        $katanga = Province::where('code', 'HAU')->first();
        $kasai = Province::where('code', 'KCE')->first();

        $departments = [
            // Départements de Kinshasa
            [
                'code' => 'GOM',
                'nom' => 'Gombe',
                'description' => 'Département de Gombe - Siège social',
                'province_id' => $kinshasa?->id,
            ],
            [
                'code' => 'LEA',
                'nom' => 'Léopoldville',
                'description' => 'Département de Léopoldville',
                'province_id' => $kinshasa?->id,
            ],
            [
                'code' => 'LAD',
                'nom' => 'Ladière',
                'description' => 'Département de Ladière',
                'province_id' => $kinshasa?->id,
            ],
            // Départements de Katanga
            [
                'code' => 'LUM',
                'nom' => 'Lumumbashi',
                'description' => 'Département de Lumumbashi',
                'province_id' => $katanga?->id,
            ],
            [
                'code' => 'KOL',
                'nom' => 'Kolwezi',
                'description' => 'Département de Kolwezi',
                'province_id' => $katanga?->id,
            ],
            // Départements de Kasai Central
            [
                'code' => 'THS',
                'nom' => 'Tshikapa',
                'description' => 'Département de Tshikapa',
                'province_id' => $kasai?->id,
            ],
        ];

        foreach ($departments as $department) {
            if ($department['province_id']) {
                Department::firstOrCreate(
                    ['code' => $department['code']],
                    $department
                );
            }
        }
    }
}
