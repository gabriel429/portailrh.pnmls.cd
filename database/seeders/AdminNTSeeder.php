<?php

namespace Database\Seeders;

use App\Models\Agent;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminNTSeeder extends Seeder
{
    /**
     * Crée le compte par défaut de la Section Nouvelle Technologie.
     * Identifiants : admin.nt@pnmls.cd / Admin@2026
     */
    public function run(): void
    {
        $role = Role::firstOrCreate(
            ['nom_role' => 'Section Nouvelle Technologie'],
            ['description' => 'Section Nouvelle Technologie – accès paramètres système']
        );

        Agent::updateOrCreate(
            ['email' => 'admin.nt@pnmls.cd'],
            [
                'matricule_pnmls'           => 'PNM-NT-001',
                'nom'                       => 'ADMIN',
                'postnom'                   => 'NT',
                'prenom'                    => 'Système',
                'email'                     => 'admin.nt@pnmls.cd',
                'password'                  => Hash::make('Admin@2026'),
                'date_naissance'            => '1990-01-01',
                'annee_naissance'           => 1990,
                'lieu_naissance'            => 'Kinshasa',
                'sexe'                      => 'M',
                'situation_familiale'       => 'célibataire',
                'nombre_enfants'            => 0,
                'telephone'                 => '+243000000000',
                'adresse'                   => 'Kinshasa, Gombe – Secrétariat Exécutif National',
                'organe'                    => 'Secrétariat Exécutif National',
                'poste_actuel'              => 'Section Nouvelle Technologie',
                'fonction'                  => 'Section Nouvelle Technologie',
                'niveau_etudes'             => 'Licence',
                'annee_engagement_programme'=> 2024,
                'date_embauche'             => '2024-01-01',
                'statut'                    => 'actif',
                'role_id'                   => $role->id,
                'province_id'               => null,
                'departement_id'            => null,
            ]
        );
    }
}
