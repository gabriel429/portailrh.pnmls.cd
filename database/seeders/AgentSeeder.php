<?php

namespace Database\Seeders;

use App\Models\Agent;
use App\Models\Role;
use App\Models\Department;
use App\Models\Province;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AgentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Récupérer les données de référence
        $kinshasa = Province::where('code', 'KIN')->first();
        $gombe = Department::where('code', 'GOM')->first();
        $chefRhRole = Role::where('nom_role', 'Section ressources humaines')->first()
            ?? Role::where('nom_role', 'Chef Section RH')->first();
        $rhNationalRole = Role::where('nom_role', 'RH National')->first();
        $agentRole = Role::where('nom_role', 'Agent')->first();

        $agents = [
            [
                'nom' => 'Kabamba',
                'prenom' => 'Jean-Pierre',
                'password' => Hash::make('password'),
                'date_naissance' => '1980-01-15',
                'lieu_naissance' => 'Kinshasa',
                'telephone' => '+243812345678',
                'adresse' => 'Kinshasa, Gombe',
                'poste_actuel' => 'Agent PNMLS',
                'departement_id' => $gombe?->id,
                'province_id' => $kinshasa?->id,
                'role_id' => $agentRole?->id,
                'date_embauche' => '2020-01-15',
                'statut' => 'actif',
            ],
            [
                'nom' => 'Mutua',
                'prenom' => 'Marie',
                'password' => Hash::make('password'),
                'date_naissance' => '1982-03-20',
                'lieu_naissance' => 'Kinshasa',
                'telephone' => '+243812345679',
                'adresse' => 'Kinshasa, Gombe',
                'poste_actuel' => 'Chef de Section RH',
                'departement_id' => $gombe?->id,
                'province_id' => $kinshasa?->id,
                'role_id' => $chefRhRole?->id,
                'date_embauche' => '2019-06-01',
                'statut' => 'actif',
            ],
            [
                'nom' => 'Malu',
                'prenom' => 'Simon',
                'password' => Hash::make('password'),
                'date_naissance' => '1985-07-10',
                'lieu_naissance' => 'Kinshasa',
                'telephone' => '+243812345680',
                'adresse' => 'Kinshasa, Kalamu',
                'poste_actuel' => 'Assistant RH',
                'departement_id' => $gombe?->id,
                'province_id' => $kinshasa?->id,
                'role_id' => $rhNationalRole?->id,
                'date_embauche' => '2021-02-15',
                'statut' => 'actif',
            ],
        ];

        foreach ($agents as $agent) {
            Agent::firstOrCreate(
                ['nom' => $agent['nom'], 'prenom' => $agent['prenom']],
                $agent
            );
        }
    }
}
