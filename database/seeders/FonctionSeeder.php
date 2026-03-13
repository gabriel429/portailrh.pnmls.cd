<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Fonction;

class FonctionSeeder extends Seeder
{
    public function run(): void
    {
        $fonctions = [
            // ═══════════════════════════════════════════════════════
            // NIVEAU SEN — Direction du Secrétariat Exécutif National
            // ═══════════════════════════════════════════════════════
            [
                'nom'                 => 'Secrétaire Exécutif National (SEN)',
                'niveau_administratif'=> 'SEN',
                'type_poste'          => 'direction',
                'est_chef'            => true,
                'description'         => "Responsable suprême du programme au niveau national. Coordonne l'ensemble des activités.",
            ],
            [
                'nom'                 => 'Secrétaire Exécutif National Adjoint (SENA)',
                'niveau_administratif'=> 'SEN',
                'type_poste'          => 'direction',
                'est_chef'            => true,
                'description'         => "Adjoint du SEN. Assure l'intérim en l'absence du SEN.",
            ],

            // ═══════════════════════════════════════════════════════
            // NIVEAU SEN — Services rattachés (Secrétariat de Direction)
            // ═══════════════════════════════════════════════════════
            [
                'nom'                 => 'Assistant(e) de Direction (ADIR)',
                'niveau_administratif'=> 'SEN',
                'type_poste'          => 'service_rattache',
                'est_chef'            => false,
                'description'         => 'Assiste le SEN/SENA dans la gestion administrative et organisationnelle.',
            ],
            [
                'nom'                 => 'Secrétaire de Direction',
                'niveau_administratif'=> 'SEN',
                'type_poste'          => 'service_rattache',
                'est_chef'            => false,
                'description'         => 'Gestion du secrétariat de la direction nationale.',
            ],
            [
                'nom'                 => 'Chef de Cellule — Protocole, Courriers et Relations Publiques',
                'niveau_administratif'=> 'SEN',
                'type_poste'          => 'service_rattache',
                'est_chef'            => true,
                'description'         => 'Responsable du protocole, de la gestion des courriers et des relations publiques.',
            ],
            [
                'nom'                 => "Chef d'Équipe — Relations Publiques",
                'niveau_administratif'=> 'SEN',
                'type_poste'          => 'service_rattache',
                'est_chef'            => false,
                'description'         => "Chargé des relations publiques au sein du Secrétariat de Direction.",
            ],
            [
                'nom'                 => "Chef d'Équipe — Réception",
                'niveau_administratif'=> 'SEN',
                'type_poste'          => 'service_rattache',
                'est_chef'            => false,
                'description'         => "Responsable de la réception et de l'accueil.",
            ],

            // ═══════════════════════════════════════════════════════
            // NIVEAU SEN — Service rattaché : Section Juridique
            // ═══════════════════════════════════════════════════════
            [
                'nom'                 => 'Chef de Section Juridique',
                'niveau_administratif'=> 'SEN',
                'type_poste'          => 'service_rattache',
                'est_chef'            => true,
                'description'         => "Responsable des affaires juridiques directement rattaché à la Direction.",
            ],

            // ═══════════════════════════════════════════════════════
            // NIVEAU SEN — Service rattaché : Section Communication
            // ═══════════════════════════════════════════════════════
            [
                'nom'                 => 'Chef de Section Communication',
                'niveau_administratif'=> 'SEN',
                'type_poste'          => 'service_rattache',
                'est_chef'            => true,
                'description'         => "Responsable de la section communication, directement rattachée à la Direction.",
            ],
            [
                'nom'                 => 'Chef de Cellule Communication',
                'niveau_administratif'=> 'SEN',
                'type_poste'          => 'service_rattache',
                'est_chef'            => true,
                'description'         => "Chef de la cellule communication au sein de la section communication.",
            ],
            [
                'nom'                 => "Chef d'Équipe Communication",
                'niveau_administratif'=> 'SEN',
                'type_poste'          => 'service_rattache',
                'est_chef'            => false,
                'description'         => "Coordinateur d'équipe dans la section communication.",
            ],

            // ═══════════════════════════════════════════════════════
            // NIVEAU SEN — Service rattaché : Section Audit Interne
            // ═══════════════════════════════════════════════════════
            [
                'nom'                 => "Chef de Section — Audit Interne",
                'niveau_administratif'=> 'SEN',
                'type_poste'          => 'service_rattache',
                'est_chef'            => true,
                'description'         => "Responsable de la section d'audit interne, rattachée directement à la Direction.",
            ],
            [
                'nom'                 => "Chef de Cellule — Audit Interne",
                'niveau_administratif'=> 'SEN',
                'type_poste'          => 'service_rattache',
                'est_chef'            => true,
                'description'         => "Responsable de la cellule d'audit interne.",
            ],

            // ═══════════════════════════════════════════════════════
            // NIVEAU SEN — Postes de département
            // ═══════════════════════════════════════════════════════
            [
                'nom'                 => 'Directeur / Chef de Département',
                'niveau_administratif'=> 'SEN',
                'type_poste'          => 'département',
                'est_chef'            => true,
                'description'         => "Responsable principal d'un département au niveau national.",
            ],
            [
                'nom'                 => 'Assistant de Département',
                'niveau_administratif'=> 'SEN',
                'type_poste'          => 'département',
                'est_chef'            => false,
                'description'         => "Assistant(e) auprès du directeur/chef de département.",
            ],
            [
                'nom'                 => 'Secrétaire de Département',
                'niveau_administratif'=> 'SEN',
                'type_poste'          => 'département',
                'est_chef'            => false,
                'description'         => "Secrétaire administratif(ve) rattaché(e) au département.",
            ],

            // ═══════════════════════════════════════════════════════
            // NIVEAU SEN — Postes de section
            // ═══════════════════════════════════════════════════════
            [
                'nom'                 => 'Chef de Section',
                'niveau_administratif'=> 'SEN',
                'type_poste'          => 'section',
                'est_chef'            => true,
                'description'         => "Responsable d'une section au sein d'un département.",
            ],
            [
                'nom'                 => 'Assistant de Section',
                'niveau_administratif'=> 'SEN',
                'type_poste'          => 'section',
                'est_chef'            => false,
                'description'         => "Assistant(e) au niveau d'une section.",
            ],

            // ═══════════════════════════════════════════════════════
            // NIVEAU SEN — Postes de cellule
            // ═══════════════════════════════════════════════════════
            [
                'nom'                 => 'Chef de Cellule',
                'niveau_administratif'=> 'SEN',
                'type_poste'          => 'cellule',
                'est_chef'            => true,
                'description'         => "Responsable d'une cellule au sein d'une section.",
            ],

            // ═══════════════════════════════════════════════════════
            // NIVEAU SEN — Postes d'appui / support (département)
            // ═══════════════════════════════════════════════════════
            [
                'nom'                 => 'Chauffeur',
                'niveau_administratif'=> 'TOUS',
                'type_poste'          => 'appui',
                'est_chef'            => false,
                'description'         => "Conduite des véhicules de service.",
            ],
            [
                'nom'                 => 'Commis',
                'niveau_administratif'=> 'SEN',
                'type_poste'          => 'appui',
                'est_chef'            => false,
                'description'         => "Agent d'appui administratif au niveau du département.",
            ],
            [
                'nom'                 => 'Technicien de Surface',
                'niveau_administratif'=> 'TOUS',
                'type_poste'          => 'appui',
                'est_chef'            => false,
                'description'         => "Entretien des locaux et espaces de travail.",
            ],
            [
                'nom'                 => 'Huissier',
                'niveau_administratif'=> 'TOUS',
                'type_poste'          => 'appui',
                'est_chef'            => false,
                'description'         => "Affectation des documents, assure les liaisons internes.",
            ],
            [
                'nom'                 => 'Chargé de Magasin',
                'niveau_administratif'=> 'SEN',
                'type_poste'          => 'appui',
                'est_chef'            => false,
                'description'         => "Gestion du magasin et du stock de fournitures.",
            ],

            // ═══════════════════════════════════════════════════════
            // NIVEAU SEP — Secrétariat Exécutif Provincial
            // ═══════════════════════════════════════════════════════
            [
                'nom'                 => 'Secrétaire Exécutif Provincial (SEP)',
                'niveau_administratif'=> 'SEP',
                'type_poste'          => 'province',
                'est_chef'            => true,
                'description'         => "Responsable du programme au niveau provincial.",
            ],
            [
                'nom'                 => 'Chef de Cellule Administration et Finances (CAF)',
                'niveau_administratif'=> 'SEP',
                'type_poste'          => 'province',
                'est_chef'            => true,
                'description'         => "Responsable des finances et de l'administration au niveau provincial.",
            ],
            [
                'nom'                 => 'Chef de Cellule Partenariat et Appui aux Secteurs',
                'niveau_administratif'=> 'SEP',
                'type_poste'          => 'province',
                'est_chef'            => true,
                'description'         => "Responsable du partenariat et de l'appui aux secteurs au niveau provincial.",
            ],
            [
                'nom'                 => 'Chef de Cellule — Planification, Suivi-Évaluation et Renforcement des Capacités',
                'niveau_administratif'=> 'SEP',
                'type_poste'          => 'province',
                'est_chef'            => true,
                'description'         => "Responsable de la planification, du suivi-évaluation et du renforcement des capacités au niveau provincial.",
            ],
            [
                'nom'                 => "Chef d'Équipe Logistique (SEP)",
                'niveau_administratif'=> 'SEP',
                'type_poste'          => 'province',
                'est_chef'            => false,
                'description'         => "Coordinateur de l'équipe logistique provinciale.",
            ],
            [
                'nom'                 => 'Secrétaire Caissier',
                'niveau_administratif'=> 'SEP',
                'type_poste'          => 'province',
                'est_chef'            => false,
                'description'         => "Gestion de la caisse et du secrétariat provincial.",
            ],
            [
                'nom'                 => 'Sentinelle (Gardien)',
                'niveau_administratif'=> 'TOUS',
                'type_poste'          => 'appui',
                'est_chef'            => false,
                'description'         => "Surveillance et gardiennage des locaux.",
            ],

            // ═══════════════════════════════════════════════════════
            // NIVEAU SEL — Secrétariat Exécutif Local
            // ═══════════════════════════════════════════════════════
            [
                'nom'                 => 'Secrétaire Exécutif Local (SEL)',
                'niveau_administratif'=> 'SEL',
                'type_poste'          => 'local',
                'est_chef'            => true,
                'description'         => "Responsable du programme au niveau local (territoire, zone de santé, commune).",
            ],
            [
                'nom'                 => 'Assistant Technique (SEL)',
                'niveau_administratif'=> 'SEL',
                'type_poste'          => 'local',
                'est_chef'            => false,
                'description'         => "Assistant technique au niveau local.",
            ],
            [
                'nom'                 => 'Assistant Administratif et Financier (SEL)',
                'niveau_administratif'=> 'SEL',
                'type_poste'          => 'local',
                'est_chef'            => false,
                'description'         => "Gestion administrative et financière au niveau local.",
            ],
            [
                'nom'                 => 'Chauffeur-Logisticien (SEL)',
                'niveau_administratif'=> 'SEL',
                'type_poste'          => 'local',
                'est_chef'            => false,
                'description'         => "Conduite et gestion logistique au niveau local.",
            ],
        ];

        foreach ($fonctions as $f) {
            Fonction::firstOrCreate(['nom' => $f['nom']], $f);
        }
    }
}
