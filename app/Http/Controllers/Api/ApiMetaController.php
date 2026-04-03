<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class ApiMetaController extends Controller
{
    public function index(): JsonResponse
    {
        $catalog = $this->catalog();

        return response()->json([
            'name' => config('app.name', 'Laravel API'),
            'description' => 'API REST du portail RH PNMLS.',
            'current_version' => 'v1',
            'authentication' => [
                'default' => 'sanctum',
                'login_endpoint' => '/api/login',
            ],
            'discovery' => [
                'resources' => '/api/resources',
                'openapi' => '/api/openapi.json',
                'health' => '/up',
            ],
            'stats' => [
                'resource_count' => count($catalog),
                'operation_count' => count($this->operationsFromCatalog($catalog)),
            ],
        ]);
    }

    public function resources(): JsonResponse
    {
        $catalog = $this->catalog();

        return response()->json([
            'version' => 'v1',
            'resources' => $catalog,
        ]);
    }

    public function openapi(): JsonResponse
    {
        $catalog = $this->catalog();
        $operations = $this->operationsFromCatalog($catalog);
        $paths = [];

        foreach ($operations as $operation) {
            $path = $operation['path'];
            $method = strtolower($operation['method']);

            $paths[$path][$method] = [
                'summary' => $operation['summary'],
                'tags' => [$operation['tag']],
                'security' => $operation['auth'] === 'public' ? [] : [['sanctum' => []]],
                'responses' => [
                    '200' => ['description' => 'OK'],
                    '201' => ['description' => 'Created'],
                    '401' => ['description' => 'Unauthenticated'],
                    '403' => ['description' => 'Forbidden'],
                ],
            ];
        }

        return response()->json([
            'openapi' => '3.0.3',
            'info' => [
                'title' => config('app.name', 'Laravel API'),
                'version' => 'v1',
                'description' => 'Specification OpenAPI minimale de l\'API REST PNMLS.',
            ],
            'servers' => [
                ['url' => rtrim(config('app.url'), '/') . '/api'],
            ],
            'components' => [
                'securitySchemes' => [
                    'sanctum' => [
                        'type' => 'apiKey',
                        'in' => 'header',
                        'name' => 'Authorization',
                    ],
                ],
            ],
            'paths' => $paths,
        ]);
    }

    private function catalog(): array
    {
        return [
            [
                'name' => 'auth',
                'group' => 'public',
                'auth' => 'public',
                'base_uri' => '/api',
                'description' => 'Authentification et contexte utilisateur.',
                'operations' => [
                    ['method' => 'POST', 'path' => '/login', 'summary' => 'Connexion'],
                    ['method' => 'POST', 'path' => '/logout', 'summary' => 'Deconnexion'],
                    ['method' => 'GET', 'path' => '/user', 'summary' => 'Utilisateur courant'],
                ],
            ],
            [
                'name' => 'sync',
                'group' => 'desktop',
                'auth' => 'mixed',
                'base_uri' => '/api/sync',
                'description' => 'Synchronisation offline et transfert de fichiers.',
                'operations' => [
                    ['method' => 'GET', 'path' => '/sync/status', 'summary' => 'Etat de synchronisation'],
                    ['method' => 'POST', 'path' => '/sync/pull', 'summary' => 'Recuperation des donnees'],
                    ['method' => 'POST', 'path' => '/sync/push', 'summary' => 'Envoi des donnees'],
                    ['method' => 'GET', 'path' => '/sync/dirty', 'summary' => 'Liste des enregistrements modifies'],
                    ['method' => 'POST', 'path' => '/sync/mark-synced', 'summary' => 'Marquer comme synchronise'],
                    ['method' => 'POST', 'path' => '/sync/files/upload', 'summary' => 'Envoyer un fichier'],
                    ['method' => 'GET', 'path' => '/sync/files/download/{uuid}', 'summary' => 'Telecharger un fichier'],
                ],
            ],
            [
                'name' => 'dashboard',
                'group' => 'core',
                'auth' => 'sanctum',
                'base_uri' => '/api/dashboard',
                'description' => 'Tableaux de bord applicatifs.',
                'operations' => [
                    ['method' => 'GET', 'path' => '/dashboard', 'summary' => 'Dashboard utilisateur'],
                    ['method' => 'GET', 'path' => '/dashboard/executive', 'summary' => 'Dashboard executif'],
                    ['method' => 'GET', 'path' => '/rh/dashboard', 'summary' => 'Dashboard RH'],
                    ['method' => 'GET', 'path' => '/admin/dashboard', 'summary' => 'Dashboard administration'],
                ],
            ],
            [
                'name' => 'profile',
                'group' => 'core',
                'auth' => 'sanctum',
                'base_uri' => '/api/profile',
                'description' => 'Profil de l\'utilisateur connecte.',
                'operations' => [
                    ['method' => 'GET', 'path' => '/profile', 'summary' => 'Profil legacy'],
                    ['method' => 'GET', 'path' => '/profile/full', 'summary' => 'Profil complet'],
                    ['method' => 'PUT', 'path' => '/profile', 'summary' => 'Mettre a jour le profil'],
                    ['method' => 'PUT', 'path' => '/profile/password', 'summary' => 'Changer le mot de passe'],
                    ['method' => 'GET', 'path' => '/mes-absences', 'summary' => 'Absences de l\'agent courant'],
                ],
            ],
            [
                'name' => 'notifications',
                'group' => 'core',
                'auth' => 'sanctum',
                'base_uri' => '/api/notifications',
                'description' => 'Notifications utilisateur.',
                'operations' => [
                    ['method' => 'GET', 'path' => '/notifications', 'summary' => 'Lister les notifications'],
                    ['method' => 'GET', 'path' => '/notifications/unread-count', 'summary' => 'Compter les notifications non lues'],
                    ['method' => 'POST', 'path' => '/notifications/mark-all-read', 'summary' => 'Tout marquer comme lu'],
                    ['method' => 'POST', 'path' => '/notifications/{notification}/read', 'summary' => 'Marquer une notification comme lue'],
                    ['method' => 'DELETE', 'path' => '/notifications/{notification}', 'summary' => 'Supprimer une notification'],
                ],
            ],
            [
                'name' => 'documents',
                'group' => 'ged',
                'auth' => 'sanctum',
                'base_uri' => '/api/documents',
                'description' => 'CRUD REST des documents.',
                'operations' => [
                    ['method' => 'GET', 'path' => '/documents', 'summary' => 'Lister les documents'],
                    ['method' => 'POST', 'path' => '/documents', 'summary' => 'Creer un document'],
                    ['method' => 'GET', 'path' => '/documents/{document}', 'summary' => 'Afficher un document'],
                    ['method' => 'PUT', 'path' => '/documents/{document}', 'summary' => 'Mettre a jour un document'],
                    ['method' => 'DELETE', 'path' => '/documents/{document}', 'summary' => 'Supprimer un document'],
                    ['method' => 'GET', 'path' => '/documents/{document}/download', 'summary' => 'Telecharger un document'],
                ],
            ],
            [
                'name' => 'requests',
                'group' => 'workflow',
                'auth' => 'sanctum',
                'base_uri' => '/api/requests',
                'description' => 'CRUD REST des demandes.',
                'operations' => [
                    ['method' => 'GET', 'path' => '/requests', 'summary' => 'Lister les demandes'],
                    ['method' => 'POST', 'path' => '/requests', 'summary' => 'Creer une demande'],
                    ['method' => 'GET', 'path' => '/requests/{request}', 'summary' => 'Afficher une demande'],
                    ['method' => 'PUT', 'path' => '/requests/{request}', 'summary' => 'Mettre a jour une demande'],
                    ['method' => 'DELETE', 'path' => '/requests/{request}', 'summary' => 'Supprimer une demande'],
                ],
            ],
            [
                'name' => 'agents',
                'group' => 'rh',
                'auth' => 'role',
                'base_uri' => '/api/agents',
                'description' => 'CRUD REST des agents RH.',
                'operations' => [
                    ['method' => 'GET', 'path' => '/agents', 'summary' => 'Lister les agents'],
                    ['method' => 'POST', 'path' => '/agents', 'summary' => 'Creer un agent'],
                    ['method' => 'GET', 'path' => '/agents/{agent}', 'summary' => 'Afficher un agent'],
                    ['method' => 'PUT', 'path' => '/agents/{agent}', 'summary' => 'Mettre a jour un agent'],
                    ['method' => 'DELETE', 'path' => '/agents/{agent}', 'summary' => 'Supprimer un agent'],
                    ['method' => 'GET', 'path' => '/agents/export', 'summary' => 'Exporter les agents'],
                    ['method' => 'GET', 'path' => '/agents/form-options', 'summary' => 'Lister les options de formulaire'],
                ],
            ],
            [
                'name' => 'pointages',
                'group' => 'rh',
                'auth' => 'role',
                'base_uri' => '/api/pointages',
                'description' => 'API REST et analytique des pointages.',
                'operations' => [
                    ['method' => 'GET', 'path' => '/pointages', 'summary' => 'Lister les pointages'],
                    ['method' => 'POST', 'path' => '/pointages', 'summary' => 'Creer un pointage'],
                    ['method' => 'GET', 'path' => '/pointages/{pointage}', 'summary' => 'Afficher un pointage'],
                    ['method' => 'PUT', 'path' => '/pointages/{pointage}', 'summary' => 'Mettre a jour un pointage'],
                    ['method' => 'DELETE', 'path' => '/pointages/{pointage}', 'summary' => 'Supprimer un pointage'],
                    ['method' => 'GET', 'path' => '/pointages/daily', 'summary' => 'Vue journaliere'],
                    ['method' => 'GET', 'path' => '/pointages/monthly', 'summary' => 'Vue mensuelle'],
                ],
            ],
            [
                'name' => 'signalements',
                'group' => 'rh',
                'auth' => 'role',
                'base_uri' => '/api/signalements',
                'description' => 'CRUD REST des signalements.',
                'operations' => [
                    ['method' => 'GET', 'path' => '/signalements', 'summary' => 'Lister les signalements'],
                    ['method' => 'POST', 'path' => '/signalements', 'summary' => 'Creer un signalement'],
                    ['method' => 'GET', 'path' => '/signalements/{signalement}', 'summary' => 'Afficher un signalement'],
                    ['method' => 'PUT', 'path' => '/signalements/{signalement}', 'summary' => 'Mettre a jour un signalement'],
                    ['method' => 'DELETE', 'path' => '/signalements/{signalement}', 'summary' => 'Supprimer un signalement'],
                ],
            ],
            [
                'name' => 'communiques',
                'group' => 'rh',
                'auth' => 'role',
                'base_uri' => '/api/communiques',
                'description' => 'CRUD REST des communiques.',
                'operations' => [
                    ['method' => 'GET', 'path' => '/communiques', 'summary' => 'Lister les communiques'],
                    ['method' => 'POST', 'path' => '/communiques', 'summary' => 'Creer un communique'],
                    ['method' => 'GET', 'path' => '/communiques/{communique}', 'summary' => 'Afficher un communique'],
                    ['method' => 'PUT', 'path' => '/communiques/{communique}', 'summary' => 'Mettre a jour un communique'],
                    ['method' => 'DELETE', 'path' => '/communiques/{communique}', 'summary' => 'Supprimer un communique'],
                ],
            ],
            [
                'name' => 'holidays',
                'group' => 'rh',
                'auth' => 'role',
                'base_uri' => '/api/holidays',
                'description' => 'Gestion REST des conges individuels.',
                'operations' => [
                    ['method' => 'GET', 'path' => '/holidays', 'summary' => 'Lister les conges'],
                    ['method' => 'POST', 'path' => '/holidays', 'summary' => 'Creer un conge'],
                    ['method' => 'GET', 'path' => '/holidays/{holiday}', 'summary' => 'Afficher un conge'],
                    ['method' => 'PUT', 'path' => '/holidays/{holiday}', 'summary' => 'Mettre a jour un conge'],
                    ['method' => 'POST', 'path' => '/holidays/{holiday}/approve', 'summary' => 'Approuver un conge'],
                    ['method' => 'POST', 'path' => '/holidays/{holiday}/refuse', 'summary' => 'Refuser un conge'],
                ],
            ],
            [
                'name' => 'holiday-plannings',
                'group' => 'rh',
                'auth' => 'role',
                'base_uri' => '/api/holiday-plannings',
                'description' => 'Gestion REST du planning des conges.',
                'operations' => [
                    ['method' => 'GET', 'path' => '/holiday-plannings', 'summary' => 'Lister les plannings'],
                    ['method' => 'POST', 'path' => '/holiday-plannings', 'summary' => 'Creer un planning'],
                    ['method' => 'GET', 'path' => '/holiday-plannings/{holidayPlanning}', 'summary' => 'Afficher un planning'],
                    ['method' => 'PUT', 'path' => '/holiday-plannings/{holidayPlanning}', 'summary' => 'Mettre a jour un planning'],
                    ['method' => 'DELETE', 'path' => '/holiday-plannings/{holidayPlanning}', 'summary' => 'Supprimer un planning'],
                ],
            ],
            [
                'name' => 'agent-statuses',
                'group' => 'rh',
                'auth' => 'role',
                'base_uri' => '/api/agent-statuses',
                'description' => 'Statuts et disponibilite des agents.',
                'operations' => [
                    ['method' => 'GET', 'path' => '/agent-statuses', 'summary' => 'Lister les statuts'],
                    ['method' => 'POST', 'path' => '/agent-statuses', 'summary' => 'Creer un statut'],
                    ['method' => 'GET', 'path' => '/agent-statuses/{agentStatus}', 'summary' => 'Afficher un statut'],
                    ['method' => 'POST', 'path' => '/agent-statuses/{agentStatus}/approve', 'summary' => 'Approuver un statut'],
                    ['method' => 'PUT', 'path' => '/agent-statuses/{agentStatus}/extend', 'summary' => 'Etendre un statut'],
                ],
            ],
            [
                'name' => 'admin-reference-data',
                'group' => 'admin',
                'auth' => 'admin.nt',
                'base_uri' => '/api/admin',
                'description' => 'CRUD REST des referentiels administratifs.',
                'operations' => [
                    ['method' => 'GET', 'path' => '/admin/provinces', 'summary' => 'Lister les provinces'],
                    ['method' => 'GET', 'path' => '/admin/grades', 'summary' => 'Lister les grades'],
                    ['method' => 'GET', 'path' => '/admin/roles', 'summary' => 'Lister les roles'],
                    ['method' => 'GET', 'path' => '/admin/departments', 'summary' => 'Lister les departements'],
                    ['method' => 'GET', 'path' => '/admin/fonctions', 'summary' => 'Lister les fonctions'],
                    ['method' => 'GET', 'path' => '/admin/sections', 'summary' => 'Lister les sections'],
                    ['method' => 'GET', 'path' => '/admin/cellules', 'summary' => 'Lister les cellules'],
                    ['method' => 'GET', 'path' => '/admin/localites', 'summary' => 'Lister les localites'],
                    ['method' => 'GET', 'path' => '/admin/organes', 'summary' => 'Lister les organes'],
                    ['method' => 'GET', 'path' => '/admin/utilisateurs', 'summary' => 'Lister les utilisateurs'],
                    ['method' => 'GET', 'path' => '/admin/categories-documents', 'summary' => 'Lister les categories de documents'],
                ],
            ],
        ];
    }

    private function operationsFromCatalog(array $catalog): array
    {
        $operations = [];

        foreach ($catalog as $resource) {
            foreach ($resource['operations'] as $operation) {
                $operations[] = [
                    'tag' => $resource['name'],
                    'auth' => $resource['auth'],
                    'method' => $operation['method'],
                    'path' => $operation['path'],
                    'summary' => $operation['summary'],
                ];
            }
        }

        return $operations;
    }
}