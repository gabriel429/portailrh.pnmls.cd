<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            // Permissions pour les Agents
            [
                'nom' => 'Voir profil personnel',
                'code' => 'view_own_profile',
                'description' => 'Voir son propre profil',
            ],
            [
                'nom' => 'Modifier profil personnel',
                'code' => 'edit_own_profile',
                'description' => 'Modifier son propre profil',
            ],
            [
                'nom' => 'Consulter pointages personnels',
                'code' => 'view_own_pointages',
                'description' => 'Consulter ses pointages personnels',
            ],
            [
                'nom' => 'Consulter documents personnels',
                'code' => 'view_own_documents',
                'description' => 'Consulter ses documents personnels',
            ],
            [
                'nom' => 'Soumettre demandes',
                'code' => 'create_requests',
                'description' => 'Soumettre des demandes',
            ],

            // Permissions pour la Gestion des Agents
            [
                'nom' => 'Lister agents',
                'code' => 'view_agents',
                'description' => 'Voir la liste des agents',
            ],
            [
                'nom' => 'Voir détails agent',
                'code' => 'view_agent_detail',
                'description' => 'Voir les détails d\'un agent',
            ],
            [
                'nom' => 'Créer agent',
                'code' => 'create_agent',
                'description' => 'Créer un nouvel agent',
            ],
            [
                'nom' => 'Modifier agent',
                'code' => 'edit_agent',
                'description' => 'Modifier les données d\'un agent',
            ],
            [
                'nom' => 'Supprimer agent',
                'code' => 'delete_agent',
                'description' => 'Supprimer un agent',
            ],

            // Permissions pour les Documents
            [
                'nom' => 'Lister documents',
                'code' => 'view_documents',
                'description' => 'Voir la liste des documents',
            ],
            [
                'nom' => 'Créer document',
                'code' => 'create_document',
                'description' => 'Créer un document',
            ],
            [
                'nom' => 'Modifier document',
                'code' => 'edit_document',
                'description' => 'Modifier un document',
            ],
            [
                'nom' => 'Valider document',
                'code' => 'validate_document',
                'description' => 'Valider un document',
            ],
            [
                'nom' => 'Supprimer document',
                'code' => 'delete_document',
                'description' => 'Supprimer un document',
            ],

            // Permissions pour les Demandes
            [
                'nom' => 'Lister demandes',
                'code' => 'view_requests',
                'description' => 'Voir la liste des demandes',
            ],
            [
                'nom' => 'Approuver demandes',
                'code' => 'approve_request',
                'description' => 'Approuver les demandes',
            ],
            [
                'nom' => 'Rejeter demandes',
                'code' => 'reject_request',
                'description' => 'Rejeter les demandes',
            ],

            // Permissions pour les Pointages
            [
                'nom' => 'Lister pointages',
                'code' => 'view_pointages',
                'description' => 'Voir la liste des pointages',
            ],
            [
                'nom' => 'Créer pointage',
                'code' => 'create_pointage',
                'description' => 'Créer un pointage',
            ],
            [
                'nom' => 'Modifier pointage',
                'code' => 'edit_pointage',
                'description' => 'Modifier un pointage',
            ],

            // Permissions pour les Signalements
            [
                'nom' => 'Lister signalements',
                'code' => 'view_signalements',
                'description' => 'Voir la liste des signalements',
            ],
            [
                'nom' => 'Créer signalement',
                'code' => 'create_signalement',
                'description' => 'Créer un signalement',
            ],
            [
                'nom' => 'Modifier signalement',
                'code' => 'edit_signalement',
                'description' => 'Modifier un signalement',
            ],

            // Permissions pour les Rôles et Permissions
            [
                'nom' => 'Lister rôles',
                'code' => 'view_roles',
                'description' => 'Voir la liste des rôles',
            ],
            [
                'nom' => 'Créer rôle',
                'code' => 'create_role',
                'description' => 'Créer un rôle',
            ],
            [
                'nom' => 'Modifier rôle',
                'code' => 'edit_role',
                'description' => 'Modifier un rôle',
            ],
            [
                'nom' => 'Lister permissions',
                'code' => 'view_permissions',
                'description' => 'Voir la liste des permissions',
            ],
            [
                'nom' => 'Accès administration',
                'code' => 'access_admin',
                'description' => 'Accès au panneau d\'administration',
            ],
        ];

        foreach ($permissions as $permission) {
            Permission::create($permission);
        }
    }
}
