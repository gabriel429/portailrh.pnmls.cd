<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $departments = [
            ['code' => 'DAF', 'nom' => 'Département Administration et Finances', 'description' => 'Gestion administrative et financière du programme'],
            ['code' => 'DPP', 'nom' => 'Département Planification et Programmation', 'description' => 'Planification stratégique et programmation des activités'],
            ['code' => 'DSE', 'nom' => 'Département Suivi et Évaluation', 'description' => 'Suivi et évaluation des programmes et projets'],
            ['code' => 'DPC', 'nom' => 'Département Prévention et Communication', 'description' => 'Prévention du VIH/SIDA et communication'],
            ['code' => 'DPM', 'nom' => 'Département Prise en charge Médicale', 'description' => 'Prise en charge médicale des personnes vivant avec le VIH'],
            ['code' => 'DRH', 'nom' => 'Département Ressources Humaines', 'description' => 'Gestion des ressources humaines du programme'],
            ['code' => 'DPR', 'nom' => 'Département Passation des Marchés', 'description' => 'Passation des marchés et approvisionnement'],
            ['code' => 'DIR', 'nom' => 'Direction', 'description' => 'Direction générale du programme'],
            ['code' => 'SJU', 'nom' => 'Section Juridique', 'description' => 'Affaires juridiques et contentieux'],
            ['code' => 'SCO', 'nom' => 'Section Communication', 'description' => 'Communication institutionnelle'],
            ['code' => 'SNT', 'nom' => 'Section Nouvelle Technologie', 'description' => 'Nouvelles technologies et systèmes informatiques'],
            ['code' => 'SRH', 'nom' => 'Section Ressources Humaines', 'description' => 'Gestion opérationnelle des RH'],
            ['code' => 'AUD', 'nom' => 'Audit Interne', 'description' => 'Audit interne et contrôle'],
        ];

        foreach ($departments as $department) {
            Department::firstOrCreate(
                ['code' => $department['code']],
                $department
            );
        }
    }
}
