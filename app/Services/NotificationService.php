<?php

namespace App\Services;

use App\Models\NotificationPortail;
use App\Models\User;

class NotificationService
{
    /**
     * Config des types de notifications
     */
    private static array $types = [
        'demande' => [
            'icone' => 'fa-paper-plane',
            'couleur' => '#8b5cf6',
        ],
        'demande_modifiee' => [
            'icone' => 'fa-pen',
            'couleur' => '#f59e0b',
        ],
        'demande_approuvee' => [
            'icone' => 'fa-check-circle',
            'couleur' => '#22c55e',
        ],
        'demande_rejetee' => [
            'icone' => 'fa-times-circle',
            'couleur' => '#ef4444',
        ],
        'plan_travail' => [
            'icone' => 'fa-calendar-check',
            'couleur' => '#0077B5',
        ],
        'communique' => [
            'icone' => 'fa-bullhorn',
            'couleur' => '#ea580c',
        ],
        'message' => [
            'icone' => 'fa-envelope',
            'couleur' => '#6366f1',
        ],
        'document_travail' => [
            'icone' => 'fa-file-alt',
            'couleur' => '#0891b2',
        ],
    ];

    /**
     * Envoyer une notification à un user
     */
    public static function envoyer(int $userId, string $type, string $titre, string $message, ?string $lien = null, ?int $emetteurId = null): NotificationPortail
    {
        $config = self::$types[$type] ?? ['icone' => 'fa-bell', 'couleur' => '#0077B5'];

        return NotificationPortail::create([
            'user_id' => $userId,
            'type' => $type,
            'titre' => $titre,
            'message' => $message,
            'icone' => $config['icone'],
            'couleur' => $config['couleur'],
            'lien' => $lien,
            'emetteur_id' => $emetteurId,
        ]);
    }

    /**
     * Envoyer une notification à plusieurs users
     */
    public static function envoyerMultiple(array $userIds, string $type, string $titre, string $message, ?string $lien = null, ?int $emetteurId = null): void
    {
        $config = self::$types[$type] ?? ['icone' => 'fa-bell', 'couleur' => '#0077B5'];

        $records = [];
        $now = now();
        foreach ($userIds as $userId) {
            $records[] = [
                'user_id' => $userId,
                'type' => $type,
                'titre' => $titre,
                'message' => $message,
                'icone' => $config['icone'],
                'couleur' => $config['couleur'],
                'lien' => $lien,
                'emetteur_id' => $emetteurId,
                'lu' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        NotificationPortail::insert($records);
    }

    /**
     * Notifier tous les RH d'un événement
     */
    public static function notifierRH(string $type, string $titre, string $message, ?string $lien = null, ?int $emetteurId = null): void
    {
        $rhRoles = ['Section ressources humaines', 'Chef Section RH', 'RH National', 'RH Provincial'];
        $userIds = User::whereHas('role', fn($q) => $q->whereIn('nom_role', $rhRoles))
            ->pluck('id')
            ->toArray();

        if (!empty($userIds)) {
            self::envoyerMultiple($userIds, $type, $titre, $message, $lien, $emetteurId);
        }
    }

    /**
     * Notifier tous les utilisateurs (pour communiqués, etc.)
     */
    public static function notifierTous(string $type, string $titre, string $message, ?string $lien = null, ?int $emetteurId = null): void
    {
        $userIds = User::pluck('id')->toArray();

        if (!empty($userIds)) {
            self::envoyerMultiple($userIds, $type, $titre, $message, $lien, $emetteurId);
        }
    }
}
