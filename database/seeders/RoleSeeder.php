<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'nom_role' => 'Agent',
                'description' => 'Agent PNMLS standard',
            ],
            [
                'nom_role' => 'Directeur',
                'description' => 'Directeur de département',
            ],
            [
                'nom_role' => 'RH Provincial',
                'description' => 'Cellule Administration et Finance (CAF)',
            ],
            [
                'nom_role' => 'RH National',
                'description' => 'Assistant RH au niveau national',
            ],
            [
                'nom_role' => 'Chef Section RH',
                'description' => 'Chef de Section RH',
            ],
            [
                'nom_role' => 'Chef Section Nouvelle Technologie',
                'description' => 'Chef de Section Nouvelle Technologie',
            ],
            [
                'nom_role' => 'SEP',
                'description' => 'Secrétaire Exécutif Provincial',
            ],
            [
                'nom_role' => 'SEN',
                'description' => 'Secrétaire Exécutif National',
            ],
        ];

        foreach ($roles as $role) {
            Role::create($role);
        }
    }
}
