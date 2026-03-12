<?php

namespace Database\Seeders;

use App\Models\Grade;
use Illuminate\Database\Seeder;

class GradeSeeder extends Seeder
{
    public function run(): void
    {
        $grades = [
            // A – Haut cadre
            ['categorie' => 'A', 'nom_categorie' => 'Haut cadre',              'ordre' => 1,  'libelle' => 'Secrétaire général'],
            ['categorie' => 'A', 'nom_categorie' => 'Haut cadre',              'ordre' => 2,  'libelle' => 'Directeur'],
            ['categorie' => 'A', 'nom_categorie' => 'Haut cadre',              'ordre' => 3,  'libelle' => 'Chef de Division'],
            ['categorie' => 'A', 'nom_categorie' => 'Haut cadre',              'ordre' => 4,  'libelle' => 'Chef de Bureau'],

            // B – Agent de collaboration
            ['categorie' => 'B', 'nom_categorie' => 'Agent de collaboration',  'ordre' => 5,  'libelle' => "Attaché d'Administration de 1ère Classe"],
            ['categorie' => 'B', 'nom_categorie' => 'Agent de collaboration',  'ordre' => 6,  'libelle' => "Attaché d'Administration de 2ème Classe"],
            ['categorie' => 'B', 'nom_categorie' => 'Agent de collaboration',  'ordre' => 7,  'libelle' => "Agent d'Administration de 1ère Classe"],

            // C – Agents d'exécution
            ['categorie' => 'C', 'nom_categorie' => "Agents d'exécution",      'ordre' => 8,  'libelle' => "Agent d'Administration de 2ème Classe"],
            ['categorie' => 'C', 'nom_categorie' => "Agents d'exécution",      'ordre' => 9,  'libelle' => "Agent Auxiliaire de 1ère Classe"],
            ['categorie' => 'C', 'nom_categorie' => "Agents d'exécution",      'ordre' => 10, 'libelle' => "Agent Auxiliaire de 2ème Classe"],
            ['categorie' => 'C', 'nom_categorie' => "Agents d'exécution",      'ordre' => 11, 'libelle' => 'Huissier'],
        ];

        foreach ($grades as $grade) {
            Grade::firstOrCreate(
                ['ordre' => $grade['ordre']],
                $grade
            );
        }
    }
}
