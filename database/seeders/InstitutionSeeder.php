<?php

namespace Database\Seeders;

use App\Models\InstitutionCategorie;
use App\Models\Institution;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class InstitutionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Institutions politiques et constitutionnelles
        $cat1 = InstitutionCategorie::firstOrCreate(
            ['code' => 'POL_CONST'],
            ['nom' => '1. Institutions politiques et constitutionnelles', 'ordre' => 1]
        );
        $institutions1 = [
            ['code' => 'PRES_REP', 'nom' => 'Présidence de la République'],
            ['code' => 'PRIM', 'nom' => 'Primature'],
            ['code' => 'ASSEM_NAT', 'nom' => 'Assemblée Nationale'],
            ['code' => 'SENAT', 'nom' => 'Sénat'],
            ['code' => 'COUR_CONST', 'nom' => 'Cour Constitutionnelle'],
            ['code' => 'COUR_CASS', 'nom' => 'Cour de Cassation'],
            ['code' => 'CONSEIL_ETAT', 'nom' => 'Conseil d\'État'],
            ['code' => 'CONSEIL_ECON', 'nom' => 'Conseil Économique et Social'],
            ['code' => 'JOURN_OFF', 'nom' => 'Journal Officiel'],
            ['code' => 'CHANCE_ORD', 'nom' => 'Chancellerie des Ordres Nationaux'],
        ];
        foreach ($institutions1 as $inst) {
            Institution::firstOrCreate(
                ['code' => $inst['code']],
                array_merge($inst, ['institution_categorie_id' => $cat1->id])
            );
        }

        // 2. Institutions de contrôle et d'inspection
        $cat2 = InstitutionCategorie::firstOrCreate(
            ['code' => 'CONTR_INSP'],
            ['nom' => '2. Institutions de contrôle et d\'inspection', 'ordre' => 2]
        );
        $institutions2 = [
            ['code' => 'IGF', 'nom' => 'Inspection Générale des Finances (IGF)'],
            ['code' => 'IGAP', 'nom' => 'Inspection Générale de l\'Administration Publique (IGAP)'],
            ['code' => 'IGPN', 'nom' => 'Inspection Générale de la Police Nationale Congolaise'],
            ['code' => 'IGT', 'nom' => 'Inspection Générale de la Territoriale'],
            ['code' => 'IGEFTP', 'nom' => 'Inspection Générale de l\'Enseignement et Formation Techniques et Professionnels'],
            ['code' => 'IGT_TRAV', 'nom' => 'Inspection Générale du Travail'],
            ['code' => 'IGS', 'nom' => 'Inspection Générale à la Santé'],
        ];
        foreach ($institutions2 as $inst) {
            Institution::firstOrCreate(
                ['code' => $inst['code']],
                array_merge($inst, ['institution_categorie_id' => $cat2->id])
            );
        }

        // 3. Régies financières de l'État
        $cat3 = InstitutionCategorie::firstOrCreate(
            ['code' => 'REG_FIN'],
            ['nom' => '3. Régies financières de l\'État', 'ordre' => 3]
        );
        $institutions3 = [
            ['code' => 'DGI', 'nom' => 'Direction Générale des Impôts (DGI)'],
            ['code' => 'DGDA', 'nom' => 'Direction Générale des Douanes et Accises (DGDA)'],
            ['code' => 'DGRAD', 'nom' => 'Direction Générale des Recettes Administratives, Judiciaires, Domaniales et de Participations (DGRAD)'],
        ];
        foreach ($institutions3 as $inst) {
            Institution::firstOrCreate(
                ['code' => $inst['code']],
                array_merge($inst, ['institution_categorie_id' => $cat3->id])
            );
        }

        // 4. Ministères de souveraineté
        $cat4 = InstitutionCategorie::firstOrCreate(
            ['code' => 'MIN_SOUV'],
            ['nom' => '4. Ministères de souveraineté', 'ordre' => 4]
        );
        $institutions4 = [
            ['code' => 'MIN_INT', 'nom' => 'Intérieur, Sécurité et Affaires Coutumières'],
            ['code' => 'MIN_JUST', 'nom' => 'Justice et Garde des Sceaux'],
            ['code' => 'MIN_DEF', 'nom' => 'Défense Nationale et Anciens Combattants'],
            ['code' => 'MIN_AE', 'nom' => 'Affaires Étrangères et Coopération Internationale'],
            ['code' => 'MIN_DH', 'nom' => 'Droits Humains'],
        ];
        foreach ($institutions4 as $inst) {
            Institution::firstOrCreate(
                ['code' => $inst['code']],
                array_merge($inst, ['institution_categorie_id' => $cat4->id])
            );
        }

        // 5. Ministères économiques et financiers
        $cat5 = InstitutionCategorie::firstOrCreate(
            ['code' => 'MIN_ECON'],
            ['nom' => '5. Ministères économiques et financiers', 'ordre' => 5]
        );
        $institutions5 = [
            ['code' => 'MIN_FIN', 'nom' => 'Finances'],
            ['code' => 'MIN_BUD', 'nom' => 'Budget'],
            ['code' => 'MIN_ECO', 'nom' => 'Économie Nationale'],
            ['code' => 'MIN_PLAN', 'nom' => 'Plan'],
            ['code' => 'MIN_COM_EXT', 'nom' => 'Commerce Extérieur'],
            ['code' => 'MIN_IND', 'nom' => 'Industrie'],
            ['code' => 'MIN_PORT', 'nom' => 'Portefeuille'],
            ['code' => 'MIN_PME', 'nom' => 'Classes Moyennes, PME et Artisanat'],
        ];
        foreach ($institutions5 as $inst) {
            Institution::firstOrCreate(
                ['code' => $inst['code']],
                array_merge($inst, ['institution_categorie_id' => $cat5->id])
            );
        }

        // 6. Ressources naturelles et environnement
        $cat6 = InstitutionCategorie::firstOrCreate(
            ['code' => 'RES_NAT'],
            ['nom' => '6. Ressources naturelles et environnement', 'ordre' => 6]
        );
        $institutions6 = [
            ['code' => 'MIN_MIN', 'nom' => 'Mines'],
            ['code' => 'MIN_HC', 'nom' => 'Hydrocarbures'],
            ['code' => 'MIN_ENV', 'nom' => 'Environnement et Développement Durable'],
            ['code' => 'MIN_AMN', 'nom' => 'Aménagement du Territoire'],
            ['code' => 'MIN_AF', 'nom' => 'Affaires Foncières'],
        ];
        foreach ($institutions6 as $inst) {
            Institution::firstOrCreate(
                ['code' => $inst['code']],
                array_merge($inst, ['institution_categorie_id' => $cat6->id])
            );
        }

        // 7. Infrastructures et développement
        $cat7 = InstitutionCategorie::firstOrCreate(
            ['code' => 'INFRA_DEV'],
            ['nom' => '7. Infrastructures et développement', 'ordre' => 7]
        );
        $institutions7 = [
            ['code' => 'MIN_INF', 'nom' => 'Infrastructures et Travaux Publics'],
            ['code' => 'MIN_TRANS', 'nom' => 'Transports et Voies de Communication'],
            ['code' => 'MIN_URB', 'nom' => 'Urbanisme et Habitat'],
            ['code' => 'MIN_DR', 'nom' => 'Développement Rural'],
            ['code' => 'MIN_AGR', 'nom' => 'Agriculture et Sécurité Alimentaire'],
        ];
        foreach ($institutions7 as $inst) {
            Institution::firstOrCreate(
                ['code' => $inst['code']],
                array_merge($inst, ['institution_categorie_id' => $cat7->id])
            );
        }

        // 8. Éducation et formation
        $cat8 = InstitutionCategorie::firstOrCreate(
            ['code' => 'EDUC_FORM'],
            ['nom' => '8. Éducation et formation', 'ordre' => 8]
        );
        $institutions8 = [
            ['code' => 'MIN_EPST', 'nom' => 'Enseignement Primaire, Secondaire et Technique (EPST)'],
            ['code' => 'MIN_ESU', 'nom' => 'Enseignement Supérieur et Universitaire (ESU)'],
            ['code' => 'MIN_FP', 'nom' => 'Formation Professionnelle, Arts et Métiers'],
            ['code' => 'MIN_RSI', 'nom' => 'Recherche Scientifique et Innovation'],
        ];
        foreach ($institutions8 as $inst) {
            Institution::firstOrCreate(
                ['code' => $inst['code']],
                array_merge($inst, ['institution_categorie_id' => $cat8->id])
            );
        }

        // 9. Secteur social
        $cat9 = InstitutionCategorie::firstOrCreate(
            ['code' => 'SECT_SOC'],
            ['nom' => '9. Secteur social', 'ordre' => 9]
        );
        $institutions9 = [
            ['code' => 'MIN_SPH', 'nom' => 'Santé Publique, Hygiène et Prévention'],
            ['code' => 'MIN_ASA', 'nom' => 'Affaires Sociales et Actions Humanitaires'],
            ['code' => 'MIN_EMP', 'nom' => 'Emploi et Travail'],
            ['code' => 'MIN_GFE', 'nom' => 'Genre, Famille et Enfant'],
            ['code' => 'MIN_JEN', 'nom' => 'Jeunesse et Initiation à la Nouvelle Citoyenneté'],
            ['code' => 'MIN_SL', 'nom' => 'Sports et Loisirs'],
        ];
        foreach ($institutions9 as $inst) {
            Institution::firstOrCreate(
                ['code' => $inst['code']],
                array_merge($inst, ['institution_categorie_id' => $cat9->id])
            );
        }

        // 10. Culture, médias et numérique
        $cat10 = InstitutionCategorie::firstOrCreate(
            ['code' => 'CULT_MED'],
            ['nom' => '10. Culture, médias et numérique', 'ordre' => 10]
        );
        $institutions10 = [
            ['code' => 'MIN_CA', 'nom' => 'Culture et Arts'],
            ['code' => 'MIN_CM', 'nom' => 'Communication et Médias'],
            ['code' => 'MIN_NUM', 'nom' => 'Numérique / Technologies de l\'Information'],
        ];
        foreach ($institutions10 as $inst) {
            Institution::firstOrCreate(
                ['code' => $inst['code']],
                array_merge($inst, ['institution_categorie_id' => $cat10->id])
            );
        }

        // 11. Administration publique
        $cat11 = InstitutionCategorie::firstOrCreate(
            ['code' => 'ADMIN_PUB'],
            ['nom' => '11. Administration publique', 'ordre' => 11]
        );
        $institutions11 = [
            ['code' => 'MIN_FPA', 'nom' => 'Fonction Publique (Actifs)'],
            ['code' => 'MIN_FPR', 'nom' => 'Fonction Publique (Retraités et Rentiers)'],
            ['code' => 'MIN_DEC', 'nom' => 'Décentralisation'],
            ['code' => 'MIN_IR', 'nom' => 'Intégration Régionale'],
        ];
        foreach ($institutions11 as $inst) {
            Institution::firstOrCreate(
                ['code' => $inst['code']],
                array_merge($inst, ['institution_categorie_id' => $cat11->id])
            );
        }
    }
}
