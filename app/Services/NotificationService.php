<?php

namespace App\Services;

use App\Mail\NotificationMail;
use App\Models\Agent;
use App\Models\NotificationPortail;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

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
        'signalement' => [
            'icone' => 'fa-exclamation-triangle',
            'couleur' => '#dc2626',
        ],
        'renforcement' => [
            'icone' => 'fa-graduation-cap',
            'couleur' => '#7c3aed',
        ],
        'tache' => [
            'icone' => 'fa-tasks',
            'couleur' => '#0ea5e9',
        ],
        'conge' => [
            'icone' => 'fa-calendar',
            'couleur' => '#16a34a',
        ],
        'conge_conflit' => [
            'icone' => 'fa-exclamation-circle',
            'couleur' => '#f97316',
        ],
        'formation' => [
            'icone' => 'fa-chalkboard-teacher',
            'couleur' => '#8b5cf6',
        ],
    ];

    /**
     * Envoyer une notification à un user
     */
    public static function envoyer(int $userId, string $type, string $titre, string $message, ?string $lien = null, ?int $emetteurId = null, bool $sendEmail = true): NotificationPortail
    {
        $config = self::$types[$type] ?? ['icone' => 'fa-bell', 'couleur' => '#0077B5'];

        $notification = NotificationPortail::create([
            'user_id' => $userId,
            'type' => $type,
            'titre' => $titre,
            'message' => $message,
            'icone' => $config['icone'],
            'couleur' => $config['couleur'],
            'lien' => $lien,
            'emetteur_id' => $emetteurId,
        ]);

        $user = User::with('agent')->find($userId);
        if ($sendEmail && $user) {
            self::envoyerEmailProfessionnel($user, $titre, $message, $lien);
        }

        return $notification;
    }

    /**
     * Envoyer une notification à plusieurs users
     */
    public static function envoyerMultiple(array $userIds, string $type, string $titre, string $message, ?string $lien = null, ?int $emetteurId = null, bool $sendEmail = true): void
    {
        $userIds = array_values(array_unique(array_filter(array_map('intval', $userIds))));

        if (empty($userIds)) {
            return;
        }

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

        if ($sendEmail) {
            User::with('agent')
                ->whereIn('id', $userIds)
                ->get()
                ->each(fn(User $user) => self::envoyerEmailProfessionnel($user, $titre, $message, $lien));
        }
    }

    /**
     * Envoyer une notification interne + un e-mail professionnel.
     */
    public static function envoyerAvecEmail(int $userId, string $type, string $titre, string $message, ?string $lien = null, ?int $emetteurId = null): ?NotificationPortail
    {
        return self::envoyer($userId, $type, $titre, $message, $lien, $emetteurId, true);
    }

    /**
     * Envoyer une notification interne + e-mail à plusieurs users.
     */
    public static function envoyerMultipleAvecEmail(array $userIds, string $type, string $titre, string $message, ?string $lien = null, ?int $emetteurId = null): void
    {
        $userIds = array_values(array_unique(array_filter(array_map('intval', $userIds))));

        if (empty($userIds)) {
            return;
        }

        self::envoyerMultiple($userIds, $type, $titre, $message, $lien, $emetteurId);
    }

    /**
     * Envoyer uniquement des e-mails aux adresses professionnelles des agents.
     */
    public static function envoyerEmailAgentsProfessionnels(string $titre, string $message, ?string $lien = null): int
    {
        if (!config('mail.mailer') || config('mail.mailer') === 'log') {
            return 0;
        }

        $sent = 0;

        Agent::query()
            ->whereNotNull('email_professionnel')
            ->where('email_professionnel', '!=', '')
            ->orderBy('id')
            ->chunkById(50, function ($agents) use ($titre, $message, $lien, &$sent) {
                foreach ($agents as $agent) {
                    $email = self::resolveProfessionalEmail(null, $agent);

                    if (!$email) {
                        continue;
                    }

                    try {
                        Mail::to($email)->send(new NotificationMail($titre, $message, $lien));
                        $sent++;
                    } catch (\Throwable $e) {
                        Log::warning('Broadcast professional email failed', [
                            'agent_id' => $agent->id,
                            'email' => $email,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            });

        return $sent;
    }

    /**
     * Notifier un agent concerné: notification interne si un user est lié, email pro sinon.
     */
    public static function notifierAgent(Agent $agent, string $type, string $titre, string $message, ?string $lien = null, ?int $emetteurId = null, bool $sendEmail = true): void
    {
        $agent->loadMissing('user');

        if ($agent->user) {
            self::envoyer($agent->user->id, $type, $titre, $message, $lien, $emetteurId, $sendEmail);
            return;
        }

        if ($sendEmail) {
            self::envoyerEmailProfessionnel(null, $titre, $message, $lien, $agent);
        }
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

    /**
     * Notifier par rôle(s) spécifique(s).
     */
    public static function notifierParRole(array $roleNames, string $type, string $titre, string $message, ?string $lien = null, ?int $emetteurId = null): void
    {
        $userIds = User::whereHas('role', fn ($q) => $q->whereIn('nom_role', $roleNames))
            ->pluck('id')
            ->toArray();

        if (!empty($userIds)) {
            self::envoyerMultiple($userIds, $type, $titre, $message, $lien, $emetteurId);
        }
    }

    /**
     * Notifier par cellule (users whose agent is in the given cellule).
     */
    public static function notifierCellule(string $celluleNom, string $type, string $titre, string $message, ?string $lien = null, ?int $emetteurId = null): void
    {
        $userIds = User::whereHas('agent', fn ($q) => $q->where('cellule', $celluleNom))
            ->pluck('id')
            ->toArray();

        if (!empty($userIds)) {
            self::envoyerMultiple($userIds, $type, $titre, $message, $lien, $emetteurId);
        }
    }

    /**
     * Notifier tous les utilisateurs avec notification interne + email.
     */
    public static function notifierTousAvecEmail(string $type, string $titre, string $message, ?string $lien = null, ?int $emetteurId = null): void
    {
        $userIds = User::pluck('id')->toArray();

        if (!empty($userIds)) {
            self::envoyerMultipleAvecEmail($userIds, $type, $titre, $message, $lien, $emetteurId);
        }
    }

    protected static function envoyerEmailProfessionnel(?User $user, string $titre, string $message, ?string $lien = null, ?Agent $agent = null): void
    {
        if (!config('mail.mailer') || config('mail.mailer') === 'log') {
            return;
        }

        $agent = $agent ?: $user?->agent;
        $email = self::resolveProfessionalEmail($user, $agent);

        if (!$email) {
            return;
        }

        try {
            Mail::to($email)->send(new NotificationMail($titre, $message, $lien));
        } catch (\Throwable $e) {
            Log::warning('Email notification failed', [
                'user_id' => $user?->id,
                'agent_id' => $agent?->id,
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected static function resolveProfessionalEmail(?User $user = null, ?Agent $agent = null): ?string
    {
        $candidate = trim((string) ($agent?->email_professionnel ?? ''));

        if (filter_var($candidate, FILTER_VALIDATE_EMAIL)) {
            return $candidate;
        }

        return null;
    }
}
