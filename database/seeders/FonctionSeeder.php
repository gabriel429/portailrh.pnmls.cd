<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Fonction;

class FonctionSeeder extends Seeder
{
    public function run(): void
    {
        $fonctions = [
            // Niveau département
            ['nom' => 'Directeur Chef de département',  'niveau' => 'département', 'est_chef' => true,  'description' => 'Responsable principal du département'],
            ['nom' => 'Assistant de département',        'niveau' => 'département', 'est_chef' => false, 'description' => 'Assistant(e) auprès du chef de département'],
            ['nom' => 'Secrétaire de département',       'niveau' => 'département', 'est_chef' => false, 'description' => 'Secrétaire administratif(ve) du département'],
            // Niveau section
            ['nom' => 'Chef de section',                 'niveau' => 'section',     'est_chef' => true,  'description' => 'Responsable d\'une section'],
            ['nom' => 'Assistant de section',            'niveau' => 'section',     'est_chef' => false, 'description' => 'Assistant(e) au niveau d\'une section'],
            // Niveau cellule
            ['nom' => 'Chef de cellule',                 'niveau' => 'cellule',     'est_chef' => true,  'description' => 'Responsable d\'une cellule'],
            // Transversal
            ['nom' => 'Agent',                           'niveau' => 'transversal', 'est_chef' => false, 'description' => 'Agent affecté sans responsabilité spécifique'],
        ];

        foreach ($fonctions as $f) {
            Fonction::firstOrCreate(['nom' => $f['nom']], $f);
        }
    }
}
