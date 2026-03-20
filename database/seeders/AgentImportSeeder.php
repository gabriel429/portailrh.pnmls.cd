<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Agent;
use App\Models\Department;
use App\Models\Grade;
use Illuminate\Support\Facades\Schema;

class AgentImportSeeder extends Seeder
{
    public function run(): void
    {
        // Load department mapping
        $departments = Department::pluck('id', 'nom')->toArray();

        // Build a loose department map (lowercase, trimmed)
        $deptMap = [];
        foreach ($departments as $nom => $id) {
            $deptMap[mb_strtolower(trim($nom))] = $id;
        }

        // Load grade mapping
        $gradeMap = [];
        if (Schema::hasTable('grades')) {
            $grades = Grade::pluck('id', 'nom')->toArray();
            foreach ($grades as $nom => $id) {
                $gradeMap[mb_strtolower(trim($nom))] = $id;
            }
        }

        // CSV data - agents_SEN_2026-03-20.csv
        // Order: AGT-0000 first (SEN), AGT-0001 second (SEN Adjoint)
        $agents = [
            ['AGT-0000', '790.462 X', 'KAPEND\' A', 'KALALA', 'LIEVIN', 'M', 1947, '994001111', 'kapendlievin@yahoo.fr', 'lievin.kapend@pnmls.cd', 'Secrétariat Exécutif National', 'Sécretaire Executif National', 'Sécretaire Executif National', '', 'Secrétaire général', 'Enseignement Supérieur et Universitaires', 'Docteur', 'Medecine', 2005, '2005', 'actif'],
            ['AGT-0001', '416.759', 'BOSSIKY', 'NGOY', 'BELLY  BERNARD', 'M', 1951, '998236098', 'bernardbossiky@yahoo.fr', 'bernard.bossiky@pnmls.cd', 'Secrétariat Exécutif National', 'Sécretaire Executif National Adjoint', 'Sécretaire Executif National Adjoint', '', 'Secrétaire général', 'Santé Publique, Hygiène et Prévention', 'Docteur', 'Medecine', 2010, '2010', 'actif'],
            ['AGT-0003', '1.493.473', 'NTUMBA', 'WA NTUMBA', 'GEORGE', 'M', 1946, '816119079', 'georges_ntumba@yahoo.fr', 'georges.ntumba@pnmls.cd', 'Secrétariat Exécutif National', 'Directeur', 'Directeur', 'Planification et Renforcement des Capacites', 'Directeur', 'Santé Publique, Hygiène et Prévention', 'Master', 'administration publique', 2014, '2014', 'actif'],
            ['AGT-0006', '266.102', 'KAWUNDA', 'KATAPINDU', 'JONATHAN', 'M', 1954, '998330544', 'jkaki17@gmail.com', 'jonathan.kawunda@pnmls.cd', 'Secrétariat Exécutif National', 'Directeur', 'Directeur', 'Coordination des Secteurs cooperation et partariat', 'Directeur', 'Santé Publique, Hygiène et Prévention', 'Doctorat', 'Medecine', 2005, '2005', 'actif'],
            ['AGT-0007', '1.493.485', 'LONZOLO', 'IMBANGA', 'FELLY', 'M', 1967, '998450800', 'fellydan@gmail.com', 'felly.lonzolo@pnmls.cd', 'Secrétariat Exécutif National', 'Directeur', 'Directeur', 'suivi et évaluation', 'Directeur', 'Santé Publique, Hygiène et Prévention', 'Licence', 'Psychologie d\'Orientation Scolaire et Preffeionnele', 2018, '2018', 'actif'],
            ['AGT-0008', '502.949', 'TAILA', 'NAGE', 'JOICHIN', 'M', 1962, '85505050', 'joachim.tailanage@gmail.com', 'joachim.taila@pnmls.cd', 'Secrétariat Exécutif National', 'Directeur', 'Directeur', 'Administration et Finances', 'Directeur', 'Santé Publique, Hygiène et Prévention', 'Licence', 'Gestion des Institution des santé', 2004, '2004', 'actif'],
            ['AGT-0009', '1.493.479', 'KABENGELE', 'NKOLE', 'AIME', 'M', 1971, '999952358', 'kabengeleaime@gmail.com', 'aime.kabengele@pnmls.cd', 'Secrétariat Exécutif National', 'chef de section juridique', 'chef de section juridique', '', 'Chef de Division', 'Santé Publique, Hygiène et Prévention', 'Licence', 'Droit', 2012, '2012', 'actif'],
            ['AGT-0010', '1.493.468', 'BOSSIKY', 'NKANGA', 'MELIA', 'F', 1979, '999944405', 'meliabossiky@gmail,com', 'melia.bossiky@pnmls.cd', 'Secrétariat Exécutif National', 'chef de section Coopération et partenariat', '', 'Coordination des Secteurs cooperation et partariat', 'Chef de Division', 'Santé Publique, Hygiène et Prévention', 'Master', 'Santé publique', 2012, '2012', 'actif'],
            ['AGT-0011', '527.739', 'MUAMBA', 'KABENGELE', 'DAVID', 'M', 1969, '815023726', 'kabdave@gmail.cd', 'david.muamba@pnmls.cd', 'Secrétariat Exécutif National', 'chef de section Appui aux Institutions Publiques', 'chef de section Appui aux Institutions Publiques', 'Coordination des Secteurs cooperation et partariat', 'Chef de Division', 'Commerce Extérieur', 'Licence', 'economie math', 2012, '2012', 'actif'],
            ['AGT-0067', '1.493.472', 'OBOTELA', 'N\'SARHAZA', 'YVES  GEDEON', 'M', 1980, '822252385', 'obotelayves@gmail.com', 'yves.obotela@pnmls.cd', 'Secrétariat Exécutif National', 'chef de section gestion des données  Programmatiques Non Santé', '', 'suivi et évaluation', 'Chef de Division', 'Santé Publique, Hygiène et Prévention', 'Master', 'Droit', 2012, '2012', 'actif'],
            ['AGT-0013', '863.019', 'NTUMBA', 'TSHISAU', 'LISA', 'F', 1981, '817853870', 'lisantshisau@gmail.com', 'lisa.ntumba@pnmls.cd', 'Secrétariat Exécutif National', 'chef de section gestion des données  Programmatiques  Santé et Recherche', '', 'suivi et évaluation', 'Chef de Division', 'Santé Publique, Hygiène et Prévention', 'Master', 'Santé publique', 2014, '2014', 'actif'],
            ['AGT-0014', '1.493.504', 'MUMBA', 'LUIMBA', 'PELAGIE', 'F', 1965, '906256674', 'pelamul@yahoo.fr', 'pelagie.mumba@pnmls.cd', 'Secrétariat Exécutif National', 'chef de section ressources humaines', 'chef de section ressources humaines', 'Administration et Finances', 'Chef de Division', 'Santé Publique, Hygiène et Prévention', 'Graduat', 'Agro alimentaire', 2009, '2009', 'actif'],
            ['AGT-0015', '1.493.499', 'KASSONGO', 'MUKOMA', 'PIERROT', 'M', 1962, '815025708', 'kassongomukoma@gmail.cd', 'pierrot.kassongo@pnmls.cd', 'Secrétariat Exécutif National', 'chef de section logistique', 'chef de section logistique', 'Administration et Finances', 'Chef de Division', 'Santé Publique, Hygiène et Prévention', 'Graduat', 'Programmeur de Gestion', 2005, '2005', 'actif'],
            ['AGT-0016', '1.493.471', 'MPALE', 'RAMAZANI', 'RAMS', 'M', 1982, '814708455', 'dr.ramsmpale@gmail.com', 'rams.ramazani@pnmls.cd', 'Secrétariat Exécutif National', 'chef de section rforcemt de capacités', 'chef de section rforcemt de capacités', 'Planification et Renforcement des Capacites', 'Chef de Division', 'Santé Publique, Hygiène et Prévention', 'Doctorat', 'Medecine', 2012, '2012', 'actif'],
            ['AGT-0017', '1.493.493', 'NZAMBE', 'MONIAMPALE', 'MATTHIEU', 'M', 1965, '816164649', 'nzambematthieu@yahoo.fr', 'mathieu.nzambe@pnmls.cd', 'Secrétariat Exécutif National', 'chef de section Budget et Contrôle', 'chef de section Budget et Contrôle', '', 'Chef de Division', 'Santé Publique, Hygiène et Prévention', 'Licence', 'gest des inst de santé', 2007, '2007', 'actif'],
            ['AGT-0018', '1.493.469', 'KAWATA', 'DAGUMBU', 'PAPY', 'M', 1975, '810658025', 'piekawata8@gmail.com', 'papy.kawata@pnmls.cd', 'Secrétariat Exécutif National', 'chef de section planification', 'chef de section planification', 'Planification et Renforcement des Capacites', 'Chef de Division', 'Santé Publique, Hygiène et Prévention', "Diplôme d'Etudes Superieures", 'Environnement', 2012, '2012', 'actif'],
            ['AGT-0019', '1.493.489', 'MOTALIMBO', 'LINZETSE', 'BIJOU', 'F', 1979, '818131801', 'bijoumotalimbo@yahoo.fr', 'bijou.motalimbo@pnmls.cd', 'Secrétariat Exécutif National', 'chef de Section  Communication', 'chef de Section  Communication', '', 'Chef de Division', 'Santé Publique, Hygiène et Prévention', 'Licence', 'communication', 2012, '2012', 'actif'],
            ['AGT-0020', '1.493.605 (285.076)', 'FELEX', 'NYOKA', 'KASATUKA', 'M', 1959, '815040360', 'felixndibu59@gmail.com', 'felix.ndibu@pnmls.cd', 'Secrétariat Exécutif National', 'Chef de Section Appui aux Organisations de la Société Civile', 'Chef de Section Appui aux Organisations de la Société Civile', 'Coordination des Secteurs cooperation et partariat', 'Chef de Division', 'Santé Publique, Hygiène et Prévention', 'Licence', 'Developpement rural, lanifiation et Etudescommunication', 2018, '2018', 'actif'],
            ['AGT-0021', '1.493.490', 'MUPAYA', 'AFIS', 'ROLIENNE', 'F', 1971, '906430261', 'rolienneya@yahoo.fr', 'rolienne.mupaya@pnmls.cd', 'Secrétariat Exécutif National', 'chef de Section Suivi des Dépenses', 'chef de Section Suivi des Dépenses', 'suivi et évaluation', 'Chef de Division', 'Santé Publique, Hygiène et Prévention', 'Licence', 'sciences commerciales', 2006, '2006', 'actif'],
            ['AGT-0022', '1.493.586', 'MPINDA', 'BADIBANGA', 'MEDARD', 'M', 1955, '814270205', 'medmpinda@yahoo.fr', 'medard.mpinda@pnmls.cd', 'Secrétariat Exécutif National', 'Chef de Section Documentation', 'Chef de Section Documentation', 'suivi et évaluation', 'Chef de Division', 'Santé Publique, Hygiène et Prévention', 'Graduat', 'Français', 2017, '2017', 'actif'],
            ['AGT-0023', '526.979', 'MUMBALA', 'NGONGO', 'ANICET', 'M', 1961, '998319095', 'mumbalanicet1200@gmail.com', 'aniciet.mumbala@pnmls.cd', 'Secrétariat Exécutif National', 'Assitant Comptable', 'Assitant Comptable', 'Administration et Finances', 'Chef de Division', 'Intérieur, Sécurité et Affaires Coutumieres', 'Licence', 'sciences commerciales', 2011, '2011', 'actif'],
            ['AGT-0024', '1.493.488', 'MOMBUNZA', 'AZUBA', 'CESAR', 'M', 1973, '812727731', 'cmombunza@gmail.com', 'cesar.mombunza@pnmls.cd', 'Secrétariat Exécutif National', 'Chef de Section Auditeur Interne', 'Chef de Section Auditeur Interne', '', 'Chef de Division', 'Santé Publique, Hygiène et Prévention', 'Licence', 'sciences commerciales', 2012, '2012', 'actif'],
            ['AGT-0025', '1.493.478', 'INYONGO', 'ILOISUMO', 'SERGE', 'M', 1980, '990029677', 'sergeinyongo@yahoo.fr', 'serge.inyongo@pnmls.cd', 'Secrétariat Exécutif National', 'chef de Section Trésorerie', 'chef de Section Trésorerie', 'Administration et Finances', 'Chef de Division', 'Santé Publique, Hygiène et Prévention', 'Licence', 'economie', 2012, '2012', 'actif'],
            ['AGT-0026', '1.493.480', 'KANGI', 'BATESA', 'REAGAN', 'M', 1986, '812875743', 'reagankangi@gmail.com', 'reagan.kangi@pnmls.cd', 'Secrétariat Exécutif National', 'Chef Comptable', 'Chef Comptable', 'Administration et Finances', 'Chef de Division', 'Santé Publique, Hygiène et Prévention', 'Licence', 'gest des institut des santé', 2015, '2015', 'actif'],
            ['AGT-0027', '1.493.484', 'KINTALA', 'NGABILILI', 'MERLINE', 'F', 1978, '815188747', 'merkintala@gmail.cd', 'merline.kintala@pnmls.cd', 'Secrétariat Exécutif National', 'Chef de cellule/Auditeur Interne', 'Chef de cellule/Auditeur Interne', '', 'Chef de Bureau', 'Santé Publique, Hygiène et Prévention', 'Licence', 'fiscalité', 2012, '2012', 'actif'],
            ['AGT-0028', '1.493.510', 'BOTSHIMA', 'BAKOTELE', 'JANVIER', 'M', 1960, '855000351', 'jvbotshima@gmail.com', 'janvier.botshima@pnmls.cd', 'Secrétariat Exécutif National', 'chef de cellule Parc Auto, Mag, Gar et Chauf', 'chef de cellule Parc Auto, Mag, Gar et Chauf', 'Administration et Finances', 'Chef de Bureau', 'Santé Publique, Hygiène et Prévention', 'D4', 'Mécanique auto', 2005, '2005', 'actif'],
            ['AGT-0029', '1.493.517', 'TSENTERY', 'KAKULU', 'ANGELA', 'F', 1979, '854310199', 'atsentery@gmail.com', 'angela.tsentsery@pnmls.cd', 'Secrétariat Exécutif National', 'Caisiere', 'Caisiere', 'Administration et Finances', 'Chef de Bureau', 'Santé Publique, Hygiène et Prévention', 'Licence', 'sciences commerciales', 2005, '2005', 'actif'],
            ['AGT-0030', '1.493.494', 'POSHO', 'BOKPAMU', 'THEOPHILE', 'M', 1966, '816564257', 'theoposho@gmail.com', 'theo.posho@pnmls.cd', 'Secrétariat Exécutif National', 'che de cellule entretien batiment', 'che de cellule entretien batiment', 'Administration et Finances', 'Chef de Bureau', 'Santé Publique, Hygiène et Prévention', 'Licence', 'Psychologie', 2012, '2012', 'actif'],
            ['AGT-0031', '1.493.474', 'ALEKO', 'LALIWA', 'LYLIE', 'F', 1967, '907237815', 'alekolili@yahoo.fr', 'lylie.aleko@pnmls.cd', 'Secrétariat Exécutif National', 'chef de cellule  aux RH', 'chef de cellule  aux RH', 'Administration et Finances', 'Chef de Bureau', 'Santé Publique, Hygiène et Prévention', 'Licence', 'sciences commerciales', 2005, '2005', 'actif'],
            ['AGT-0032', '1.493.482', 'KIBANGULA', 'FATOUMA', 'NATHALIE', 'F', 1983, '815706766', 'nathkibangala@yahoo?fr', 'nathalie.kibangula@pnmls.cd', 'Secrétariat Exécutif National', 'chef de cellule partenariat', 'chef de cellule partenariat', 'Coordination des Secteurs cooperation et partariat', 'Chef de Bureau', 'Santé Publique, Hygiène et Prévention', 'Licence', 'relations internationales', 2012, '2012', 'actif'],
            ['AGT-0033', '1.493.476', 'DUA', 'LISONGO', 'LYS', 'F', 1986, '810196363', 'lysedua@gmail.com', 'lys.dua@pnmls.cd', 'Secrétariat Exécutif National', 'chef de cellule cordination des seps et sels', 'chef de cellule cordination des seps et sels', 'Coordination des Secteurs cooperation et partariat', 'Chef de Bureau', 'Santé Publique, Hygiène et Prévention', 'Licence', 'economie', 2016, '2016', 'actif'],
            ['AGT-0034', '1.493.470', 'KIGWEZYA', 'ILUNGA', 'HERVE', 'M', 1986, '815902073', 'hervekigwezya@gmail.com', 'herve.kigwezya@pnmls.cd', 'Secrétariat Exécutif National', 'chef de cellule planification', 'chef de cellule planification', 'Planification et Renforcement des Capacites', 'Chef de Bureau', 'Santé Publique, Hygiène et Prévention', 'Doctorat', 'Medecine', 2018, '2018', 'actif'],
            ['AGT-0035', '1.493.481', 'KAVIRA', 'NDAHINDWA', 'JINETTE', 'F', 1986, '819198569', 'jinettekavira@yahoo.fr', 'ginette.kavira@pnmls.cd', 'Secrétariat Exécutif National', 'Chef de Cellule Suivi des Dépenses', 'Chef de Cellule Suivi des Dépenses', 'suivi et évaluation', 'Chef de Bureau', 'Santé Publique, Hygiène et Prévention', 'Licence', 'santé communautaire', 2015, '2015', 'actif'],
            ['AGT-0036', '1.420.687', 'BAPEBABU', 'KAPUKU', 'BERTHE', 'F', 1988, '829651280', 'berthebapebabu22@gmail.com', 'berthe.bapebabu@pnmls.cd', 'Secrétariat Exécutif National', 'Chef de Cellule Renforcement des Capacités', 'Chef de Cellule Renforcement des Capacités', 'Planification et Renforcement des Capacites', 'Chef de Bureau', 'Santé Publique, Hygiène et Prévention', 'Licence', 'Gestion des Inst Sanitaire', 2018, '2018', 'actif'],
            ['AGT-0037', '1.493.518', 'MBUYI', 'BUILA', 'JOSEPH', 'M', 1970, '814544185', 'jmbuyibuila@gmail.com', 'joseph.mbuyi@pnmls.cd', 'Secrétariat Exécutif National', 'Chef de Cellule Gestion des Données  Programmatiques non santé', 'Chef de Cellule Gestion des Données  Programmatiques non santé', 'suivi et évaluation', 'Chef de Bureau', 'Santé Publique, Hygiène et Prévention', 'Licence', 'science commerciale et fin', 2008, '2008', 'actif'],
            ['AGT-0038', '1.493.487', 'MAKABI', 'WUTUKIDI', 'GAYLORD', 'M', 1988, '822244914', 'spikegaylord@gmail.com', 'gaylord.makabi@pnmls.cd', 'Secrétariat Exécutif National', 'Assistant communication', 'Assistant communication', '', "Attaché d'Administration de 1ère Classe", 'Santé Publique, Hygiène et Prévention', 'Licence', 'communication', 2018, '2018', 'actif'],
            ['AGT-0039', '1.493.500', 'KIVANGA', 'LUZOLO', 'NATHALIE', 'F', 1980, '815434385', 'nathaliluzolo3@gmail.com', 'nathalie.luzolo@pnmls.cd', 'Secrétariat Exécutif National', 'assistante de departement', 'assistante de departement', 'Coordination des Secteurs cooperation et partariat', "Attaché d'Administration de 1ère Classe", 'Santé Publique, Hygiène et Prévention', 'Graduat', 'sciences infirmieres', 2013, '2013', 'actif'],
            ['AGT-0040', '1.493.475', 'BELANGANI', 'BELLA', 'PATRICK', 'M', 1995, '817711903', 'patrickbelangani@gmail.com', 'belangani.bella@pnmls.cd', 'Secrétariat Exécutif National', 'Chargé de la  documentation/Assistant Archivage', 'Chargé de la  documentation/Assistant Archivage', 'suivi et évaluation', "Attaché d'Administration de 1ère Classe", 'Santé Publique, Hygiène et Prévention', 'Licence', 'informatique', 2020, '2020', 'actif'],
            ['AGT-0041', '545.632', 'NDOMBELE', 'MATONDO', 'JEAN', 'M', 1962, '999914452', '', 'jean.ndombele@pnmls.cd', 'Secrétariat Exécutif National', 'mecanicien  en chef', 'mecanicien  en chef', 'Administration et Finances', "Attaché d'Administration de 1ère Classe", 'Santé Publique, Hygiène et Prévention', 'PP3', 'primaire', 2005, '2005', 'actif'],
            ['AGT-0042', '1.493.496', 'FEZA', 'KIRONGOZI', 'MYMIE', 'F', 1986, '824400531', 'mymifeza@yahoo.fr', 'feza.kirongozi@pnmls.cd', 'Secrétariat Exécutif National', 'Assistant RH', 'Assistant RH', 'Administration et Finances', "Attaché d'Administration de 1ère Classe", 'Santé Publique, Hygiène et Prévention', 'PP4', 'informatique', 2012, '2012', 'actif'],
            ['AGT-0043', '1.493.501', 'LWAMBA', 'KAWUNDA', 'LYDIA', 'F', 1984, '995143913', 'lydiakawun@yahoo.fr', 'lydia.kaunda@pnmls.cd', 'Secrétariat Exécutif National', 'commis logistique batiment', 'commis logistique batiment', 'Administration et Finances', "Attaché d'Administration de 1ère Classe", 'Santé Publique, Hygiène et Prévention', 'PP5', 'Informatique', 2012, '2012', 'actif'],
            ['AGT-0044', '1.493.503', 'MULAJ', 'KONA', 'JACQUELINE', 'F', 1973, '997026317', 'itchomulaj@yahoo.fr', 'jacqueline.mulaj@pnmls.cd', 'Secrétariat Exécutif National', 'Chargée de Relation Publique', 'Chargée de Relation Publique', '', "Attaché d'Administration de 1ère Classe", 'Santé Publique, Hygiène et Prévention', 'Graduat', 'sciences commerciales', 2013, '2013', 'actif'],
            ['AGT-0045', '1.493.506', 'OLOWA', 'SAIDI', 'PIERROT', 'M', 1970, '900194156', '', 'pierrot.saidi@pnmls.cd', 'Secrétariat Exécutif National', 'Secrétaire de Direction', 'Secrétaire de Direction', '', "Attaché d'Administration de 1ère Classe", 'Santé Publique, Hygiène et Prévention', 'D6', 'sciences commerciales', 2010, '2010', 'actif'],
            ['AGT-0046', '1.493.508', 'MAWETE', 'SEBOL', 'SAFI', 'F', 1985, '893009758', 'tryliannesebol@gmail.com', 'safi.mawete@pnmls.cd', 'Secrétariat Exécutif National', 'Assistant du Département Planification et Renforcement des Capacités', 'Assistant du Département Planification et Renforcement des Capacités', 'Planification et Renforcement des Capacites', "Attaché d'Administration de 1ère Classe", 'Santé Publique, Hygiène et Prévention', 'Graduat', 'en Informatique de Gestion', 2005, '2005', 'actif'],
            ['AGT-0047', '1.493.509', 'GBUDU', 'YONGO', 'ALPHA', 'M', 1972, '900938811', 'alphonyongo@yahoo.fr', 'alpha.yongo@pnmls.cd', 'Secrétariat Exécutif National', 'charge du protocole', 'charge du protocole', '', "Attaché d'Administration de 1ère Classe", 'Santé Publique, Hygiène et Prévention', 'D6', 'mecanique', 2008, '2008', 'actif'],
            ['AGT-0048', 'NU', 'HIOMBO', 'DJONGANDEKE', 'JEREMIE', 'M', 1997, '812598904', 'jeremiehiombo@gmail.com', '', 'Secrétariat Exécutif National', 'Assistant à la Composante Privée', 'Assistant à la Composante Privée', 'Coordination des Secteurs cooperation et partariat', "Attaché d'Administration de 1ère Classe", '', 'Licence', 'Sciences Economiqus et des Gestions', 2023, '2023', 'actif'],
            ['AGT-0049', 'NU', 'KAHILU', 'MANDJATA', 'JUNIOR', 'M', 1985, '991697893', 'juniorkahilu13@gmail.com', 'junior.kahilu@pnmls.cd', 'Secrétariat Exécutif National', 'Assistant en Communication', 'Assistant en Communication', '', "Attaché d'Administration de 1ère Classe", '', 'Licence', 'Communication des Organisations', 2023, '2023', 'actif'],
            ['AGT-0050', '1.493.502', 'MODE', 'MAYELE', 'NOEL', 'M', 1969, '813518013', 'mayemo2007@yahoo.fr', 'mode.mayele@pnmls.cd', 'Secrétariat Exécutif National', 'Commi Magasin', 'Commi Magasin', 'Administration et Finances', "Attaché d'Administration de 1ère Classe", 'Santé Publique, Hygiène et Prévention', 'Graduat', 'infirmieres', 2005, '2005', 'actif'],
            ['AGT-0051', '653.127', 'IKOLI', 'LOLONGO', 'ALAIN', 'M', 1969, '818512797', '', 'alain.ikoli@pnmls.cd', 'Secrétariat Exécutif National', 'chauffeur', 'chauffeur', 'Administration et Finances', "Attaché d'Administration de 2ème Classe", 'Économie Nationale', 'D6', 'mecanique', 2008, '2008', 'actif'],
            ['AGT-0052', '646.569', 'ZABUSU', 'YAAWA', 'GABRIEL', 'M', 1973, '815170198', '', 'gabriel.zabusu@pnmls.cd', 'Secrétariat Exécutif National', 'chauffeur', 'chauffeur', 'Administration et Finances', "Attaché d'Administration de 2ème Classe", 'Santé Publique, Hygiène et Prévention', 'PP4', 'primaire', 2005, '2005', 'actif'],
            ['AGT-0053', '1.493.514', 'NETO', 'KIAKUMESO', 'NESTOR', 'M', 1969, '893651510', '', 'neto.kiakumeso@pnmls.cd', 'Secrétariat Exécutif National', 'chauffeur', 'chauffeur', 'Administration et Finances', "Attaché d'Administration de 2ème Classe", 'Santé Publique, Hygiène et Prévention', 'PP4', 'Mecanique', 2005, '2005', 'actif'],
            ['AGT-0054', '1.493.515', 'NSIKU', 'MAVINGA', 'JOSEPH', 'M', 1975, '998286135', '', 'nsiku.mavinga@pnmls.cd', 'Secrétariat Exécutif National', 'chauffeur', 'chauffeur', 'Administration et Finances', "Attaché d'Administration de 2ème Classe", 'Santé Publique, Hygiène et Prévention', 'PP5', 'primaire', 2012, '2012', 'actif'],
            ['AGT-0055', '1.493.507', 'PEMBELE', 'BAMIKINA', 'PATOU', 'M', 1974, '810888989', '', 'patou.pembele@pnmls.cd', 'Secrétariat Exécutif National', 'chauffeur', 'chauffeur', 'Administration et Finances', "Attaché d'Administration de 2ème Classe", 'Santé Publique, Hygiène et Prévention', 'D6', 'mecanique', 2005, '2005', 'actif'],
            ['AGT-0056', '1.493.512', 'MWENZENZA', 'FELA', 'NORBERT', 'M', 1958, '814202460', '', 'norbert.mwenzenza@pnmls.cd', 'Secrétariat Exécutif National', 'chauffeur', 'chauffeur', 'Administration et Finances', "Attaché d'Administration de 2ème Classe", 'Santé Publique, Hygiène et Prévention', 'Brevet', 'Mecanique', 2012, '2012', 'actif'],
            ['AGT-0057', '1.493.498', 'MUHAMIRIZA', 'ZIHALIRWA', 'JEAN PIERRE', 'M', 1963, '812943226', 'muhamirizajp@yahoo.fr', 'jean.pierre@pnmls.cd', 'Secrétariat Exécutif National', 'chauffeur', 'chauffeur', 'Administration et Finances', "Attaché d'Administration de 2ème Classe", 'Santé Publique, Hygiène et Prévention', 'Graduat', 'Mecanique', 2015, '2015', 'actif'],
            ['AGT-0058', '1.493.513', 'NDOMBASI', 'BONDO', 'SEBASTIEN', 'M', 1968, '998771076', '', 'sebastien.ndombasi@pnmls.cd', 'Secrétariat Exécutif National', 'chauffeur', 'chauffeur', 'Administration et Finances', "Attaché d'Administration de 2ème Classe", 'Santé Publique, Hygiène et Prévention', 'Brevet', 'Mecanique', 2005, '2005', 'actif'],
            ['AGT-0059', '1.493.511', 'BUSHIRI', 'KASINDI', 'PAULIN', 'M', 1962, '858007243', '', 'paulin.bushiri@pnmls.cd', 'Secrétariat Exécutif National', 'chauffeur', 'chauffeur', 'Administration et Finances', "Attaché d'Administration de 2ème Classe", 'Santé Publique, Hygiène et Prévention', 'Brevet', 'Mecanique', 2005, '2005', 'actif'],
            ['AGT-0060', '546.171', 'YONGOLO', 'TANGILA', 'ADRIEN', 'M', 1967, '895200046', '', 'adrien.yongolo@pnmls.cd', 'Secrétariat Exécutif National', 'technicien de surface', 'technicien de surface', 'Administration et Finances', "Agent d'Administration de 1ère Classe", 'Santé Publique, Hygiène et Prévention', 'Brevet', 'Mecanique', 2005, '2005', 'actif'],
            ['AGT-0061', 'NU', 'MOSE', 'TUDILA', 'MOISE', 'M', 1969, '826497770', '', 'moise.tudila@pnmls.cd', 'Secrétariat Exécutif National', 'technicien de surface', 'technicien de surface', 'Administration et Finances', "Agent d'Administration de 1ère Classe", '', 'Brevet', 'Maçonnerie', 2022, '2022', 'actif'],
            ['AGT-0062', '1.493.505', 'MALUFA', 'KAYOMBA', 'ROMBAUT', 'M', 1970, '814022488', '', 'malufa.kayomba@pnmls.cd', 'Secrétariat Exécutif National', 'technicien de surface', 'technicien de surface', 'Administration et Finances', "Agent d'Administration de 1ère Classe", 'Santé Publique, Hygiène et Prévention', 'D6', 'pedagogie', 2020, '2020', 'actif'],
            ['AGT-0063', 'NU', 'THASUR', 'FRANKIE', 'OLSEN', 'M', 1989, '812907696', 'olsenthas@gmail.com', 'olsen.thasur@pnmls.cd', 'Secrétariat Exécutif National', 'Assistant  en Informatique', 'Assistant  en Informatique', 'suivi et évaluation', "Attaché d'Administration de 1ère Classe", '', 'Licence', 'Admin des Reseau et Gestion des Bases des Données', 2023, '2023', 'actif'],
            ['AGT-0064', '1.493.577', 'MUYA', 'MBUYAMBA', 'GABRIEL', 'M', 1991, '821550225', 'igabrielmuya@gmail.com', 'gabriel.muya@pnmls.cd', 'Secrétariat Exécutif National', 'Assistant en Numérique', 'Assistant en Numérique', 'suivi et évaluation', "Attaché d'Administration de 1ère Classe", 'Santé Publique, Hygiène et Prévention', 'Graduat', 'Informatique', 2018, '2018', 'actif'],
            ['AGT-0065', 'NU', 'KOTO', 'BALAFUNDI', 'BLAISE', 'M', 1978, '847547711', '', '', 'Secrétariat Exécutif National', 'Chauffeur', 'Chauffeur', 'Administration et Finances', "Attaché d'Administration de 2ème Classe", '', 'D6', 'Mécanique', 2023, '2023', 'actif'],
            ['AGT-0066', 'NU', 'BILONGA', 'MARIE', 'THERESE', 'F', 1992, '899651162', '', '', 'Secrétariat Exécutif National', 'Technicien de  Surface', 'Technicien de  Surface', 'Administration et Finances', "Agent d'Administration de 1ère Classe", '', 'Graduat', 'Marketing', 2023, '2023', 'actif'],
        ];

        $imported = 0;
        $skipped = 0;

        foreach ($agents as $row) {
            [$idAgent, $matriculeEtat, $nom, $postnom, $prenom, $sexe, $anneeNaissance, $telephone, $emailPrive, $emailPro, $organe, $fonction, $posteActuel, $departement, $gradeEtat, $institutionOrigine, $niveauEtudes, $domaineEtudes, $anneeEngagement, $dateEmbauche, $statut] = $row;

            // Skip if agent with this matricule_pnmls already exists
            if (Agent::where('matricule_pnmls', $idAgent)->exists()) {
                $skipped++;
                continue;
            }

            // Find department ID
            $departementId = null;
            if (!empty($departement)) {
                $deptKey = mb_strtolower(trim($departement));
                $departementId = $deptMap[$deptKey] ?? null;
                // Try partial match if exact not found
                if (!$departementId) {
                    foreach ($deptMap as $key => $id) {
                        if (str_contains($key, $deptKey) || str_contains($deptKey, $key)) {
                            $departementId = $id;
                            break;
                        }
                    }
                }
            }

            // Normalize niveau_etudes to match NIVEAUX_ETUDES constant
            $niveauNormalized = $this->normalizeNiveauEtudes(trim($niveauEtudes));

            // Clean matricule_etat
            $matriculeEtatClean = trim($matriculeEtat);
            if ($matriculeEtatClean === 'NU' || $matriculeEtatClean === '') {
                $matriculeEtatClean = null;
            }

            // Parse date_embauche (year only → 01-01)
            $dateEmbaucheFormatted = null;
            if (!empty($dateEmbauche) && is_numeric(trim($dateEmbauche))) {
                $dateEmbaucheFormatted = trim($dateEmbauche) . '-01-01';
            }

            Agent::create([
                'matricule_pnmls'           => $idAgent,
                'matricule_etat'            => $matriculeEtatClean,
                'nom'                       => trim($nom),
                'postnom'                   => trim($postnom) ?: null,
                'prenom'                    => trim($prenom),
                'sexe'                      => $sexe === 'M' ? 'Masculin' : 'Feminin',
                'annee_naissance'           => (int) $anneeNaissance,
                'telephone'                 => trim($telephone) ?: null,
                'email_prive'               => (!empty(trim($emailPrive)) && str_contains($emailPrive, '@')) ? trim($emailPrive) : null,
                'email_professionnel'       => (!empty(trim($emailPro)) && str_contains($emailPro, '@')) ? trim($emailPro) : null,
                'organe'                    => trim($organe) ?: 'Secrétariat Exécutif National',
                'fonction'                  => trim($fonction),
                'poste_actuel'              => trim($posteActuel) ?: trim($fonction),
                'departement_id'            => $departementId,
                'grade_etat'                => !empty(trim($gradeEtat)) ? trim($gradeEtat) : null,
                'provenance_matricule'      => !empty(trim($institutionOrigine)) ? trim($institutionOrigine) : null,
                'niveau_etudes'             => $niveauNormalized,
                'domaine_etudes'            => !empty(trim($domaineEtudes)) ? trim($domaineEtudes) : null,
                'annee_engagement_programme' => (int) $anneeEngagement,
                'date_embauche'             => $dateEmbaucheFormatted,
                'statut'                    => $statut ?: 'actif',
            ]);

            $imported++;
        }

        $this->command->info("Import terminé : {$imported} agents importés, {$skipped} ignorés (déjà existants).");
    }

    /**
     * Normalize niveau d'études from CSV to match Agent::NIVEAUX_ETUDES.
     */
    private function normalizeNiveauEtudes(string $niveau): ?string
    {
        $map = [
            'pp3' => 'PP3',
            'pp4' => 'PP4',
            'pp5' => 'PP5',
            'brevet' => 'Brevet',
            'd4' => 'D4',
            'd5' => 'D5',
            'd6' => 'D6',
            'graduat' => 'Graduat',
            'licence' => 'Licence',
            'master' => 'Master',
            'doctorat' => 'Doctorat (PhD)',
            'docteur' => 'Doctorat (PhD)',
            "diplôme d'etudes superieures" => "Diplôme d'Études Supérieures (DES)",
            "diplôme d'études superieures" => "Diplôme d'Études Supérieures (DES)",
        ];

        $key = mb_strtolower(trim($niveau));
        return $map[$key] ?? (empty($key) ? null : $niveau);
    }
}
