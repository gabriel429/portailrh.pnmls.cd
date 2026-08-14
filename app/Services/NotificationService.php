<?php

namespace App\Services;

use App\Mail\NotificationMail;
use App\Models\Affectation;
use App\Models\Agent;
use App\Models\NotificationPortail;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

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
        'demande_annulee' => [
            'icone' => 'fa-ban',
            'couleur' => '#64748b',
        ],
        'demande_supprimee' => [
            'icone' => 'fa-trash-alt',
            'couleur' => '#dc2626',
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
        'agent' => [
            'icone' => 'fa-user-plus',
            'couleur' => '#0f766e',
        ],
        'technical_support_new' => [
            'icone' => 'fa-headset',
            'couleur' => '#dc2626',
        ],
        'technical_support_reply' => [
            'icone' => 'fa-comments',
            'couleur' => '#0284c7',
        ],
        'technical_support_status' => [
            'icone' => 'fa-screwdriver-wrench',
            'couleur' => '#16a34a',
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
        'holiday_planning_validated' => [
            'icone' => 'fa-calendar-check',
            'couleur' => '#15803d',
        ],
        'holiday_departure_reminder' => [
            'icone' => 'fa-calendar-day',
            'couleur' => '#c2410c',
        ],
        'holiday_planning_required_actor' => [
            'icone' => 'fa-calendar-exclamation',
            'couleur' => '#dc2626',
        ],
        'holiday_planning_unavailable' => [
            'icone' => 'fa-calendar-xmark',
            'couleur' => '#0369a1',
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
     * Notifier la structure cible et le SEN/SENA lors de la creation d'une fiche agent.
     */
    public static function notifierNouvelAgent(Agent $agent, ?int $emetteurId = null): void
    {
        $agent->loadMissing(['user', 'departement', 'province', 'localite']);

        $structure = self::agentStructureLabel($agent);
        $message = 'La fiche agent de ' . self::agentDisplayName($agent) . ' a ete creee';
        if ($structure !== '') {
            $message .= ' pour ' . $structure;
        }
        $message .= '.';

        $userIds = self::recipientIdsForAgentStructure($agent, null, [$agent->user?->id]);
        self::envoyerMultiple($userIds, 'agent', 'Nouvel agent créé', $message, self::agentLink($agent), $emetteurId);

        self::notifierAgent(
            $agent,
            'agent',
            'Votre fiche agent a été créée',
            'Votre fiche agent est maintenant disponible dans E-PNMLS.',
            '/profile',
            $emetteurId
        );
    }

    /**
     * Notification groupée lors d'un import d'agents.
     */
    public static function notifierImportAgents(int $count, ?int $emetteurId = null): void
    {
        if ($count < 1) {
            return;
        }

        $message = $count === 1
            ? 'Une nouvelle fiche agent a ete importee dans E-PNMLS.'
            : $count . ' nouvelles fiches agents ont ete importees dans E-PNMLS.';

        self::envoyerMultiple(
            self::executiveRecipientIds(),
            'agent',
            'Import d\'agents terminé',
            $message,
            '/rh/agents',
            $emetteurId
        );
    }

    /**
     * Notifier une affectation créée ou mise à jour depuis le module affectations.
     */
    public static function notifierAffectationAgent(Affectation $affectation, ?int $emetteurId = null, bool $miseAJour = false): void
    {
        $affectation->loadMissing([
            'agent.user',
            'agent.departement',
            'agent.province',
            'agent.localite',
            'fonction',
            'department',
            'section',
            'cellule',
            'province',
            'localite',
        ]);

        if (!$affectation->agent) {
            return;
        }

        self::notifyAssignmentRecipients(
            $affectation->agent,
            self::affectationStructureLabel($affectation),
            $affectation->fonction?->nom,
            $emetteurId,
            $miseAJour,
            $affectation
        );
    }

    /**
     * Notifier une affectation modifiée directement depuis la fiche agent.
     */
    public static function notifierAffectationAgentDepuisFiche(Agent $agent, ?int $emetteurId = null): void
    {
        $agent->loadMissing(['user', 'departement', 'province', 'localite']);

        self::notifyAssignmentRecipients(
            $agent,
            self::agentStructureLabel($agent),
            $agent->fonction,
            $emetteurId,
            true
        );
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

    protected static function notifyAssignmentRecipients(
        Agent $agent,
        string $structure,
        ?string $fonction,
        ?int $emetteurId,
        bool $miseAJour,
        ?Affectation $affectation = null
    ): void {
        $agent->loadMissing('user');

        $structure = $structure !== '' ? $structure : 'sa structure';
        $fonction = trim((string) $fonction);
        $fonction = $fonction !== '' ? $fonction : 'fonction non renseignée';
        $agentName = self::agentDisplayName($agent);

        $titre = $miseAJour ? 'Affectation agent mise à jour' : 'Nouvelle affectation agent';
        $message = $agentName . ' a ete affecte(e) a ' . $structure . ' comme ' . $fonction . '.';

        $userIds = self::recipientIdsForAgentStructure($agent, $affectation, [$agent->user?->id]);
        self::envoyerMultiple($userIds, 'agent', $titre, $message, self::agentLink($agent), $emetteurId);

        self::notifierAgent(
            $agent,
            'agent',
            $miseAJour ? 'Votre affectation a été mise à jour' : 'Nouvelle affectation',
            'Vous etes affecte(e) a ' . $structure . ' comme ' . $fonction . '.',
            '/profile',
            $emetteurId
        );
    }

    protected static function recipientIdsForAgentStructure(Agent $agent, ?Affectation $affectation = null, array $excludeUserIds = []): array
    {
        $departmentId = $affectation?->department_id ?: $agent->departement_id;
        $provinceId = $affectation?->province_id ?: $agent->province_id;
        $localiteId = $affectation?->localite_id ?: $agent->localite_id;
        $organe = self::organeLabelForAffectation($affectation) ?: $agent->organe;

        $structureUserIds = [];
        if ($departmentId || $organe) {
            $structureUserIds = User::query()
                ->whereHas('agent', function (Builder $query) use ($departmentId, $provinceId, $localiteId, $organe) {
                    $query->where('statut', 'actif');

                    if ($departmentId) {
                        $query->where('departement_id', $departmentId);
                        return;
                    }

                    self::applyOrganeConstraint($query, (string) $organe);

                    if ($provinceId) {
                        $query->where('province_id', $provinceId);
                    }

                    if ($localiteId) {
                        $query->where('localite_id', $localiteId);
                    }
                })
                ->pluck('id')
                ->all();
        }

        $excludeUserIds = array_values(array_unique(array_filter(array_map('intval', $excludeUserIds))));

        return collect($structureUserIds)
            ->merge(self::executiveRecipientIds())
            ->map(fn ($id) => (int) $id)
            ->reject(fn ($id) => in_array($id, $excludeUserIds, true))
            ->unique()
            ->values()
            ->all();
    }

    protected static function executiveRecipientIds(): array
    {
        return User::query()
            ->whereHas('role', fn (Builder $query) => $query->whereIn('nom_role', ['SEN', 'SENA']))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    protected static function applyOrganeConstraint(Builder $query, string $organe): void
    {
        $normalized = self::normalizeStructureText($organe);
        $variants = self::organeVariants($normalized, $organe);

        $query->where(function (Builder $scope) use ($normalized, $variants) {
            $scope->whereIn('organe', $variants);

            if ($normalized === 'sen' || str_contains($normalized, 'national')) {
                $scope->orWhere('organe', 'like', '%National%')
                    ->orWhere('organe', 'SEN');
            } elseif ($normalized === 'sep' || str_contains($normalized, 'provincial')) {
                $scope->orWhere('organe', 'like', '%Provincial%')
                    ->orWhere('organe', 'SEP');
            } elseif ($normalized === 'sel' || str_contains($normalized, 'local')) {
                $scope->orWhere('organe', 'like', '%Local%')
                    ->orWhere('organe', 'SEL');
            }
        });
    }

    protected static function organeVariants(string $normalized, string $organe): array
    {
        return match (true) {
            $normalized === 'sen' || str_contains($normalized, 'national') => [
                'SEN',
                'Secrétariat Exécutif National',
                'Secretariat Executif National',
            ],
            $normalized === 'sep' || str_contains($normalized, 'provincial') => [
                'SEP',
                'Secrétariat Exécutif Provincial',
                'Secretariat Executif Provincial',
            ],
            $normalized === 'sel' || str_contains($normalized, 'local') => [
                'SEL',
                'Secrétariat Exécutif Local',
                'Secretariat Executif Local',
            ],
            default => [$organe],
        };
    }

    protected static function organeLabelForAffectation(?Affectation $affectation): ?string
    {
        if (!$affectation?->niveau_administratif) {
            return null;
        }

        return match ($affectation->niveau_administratif) {
            'SEN' => 'Secrétariat Exécutif National',
            'SEP' => 'Secrétariat Exécutif Provincial',
            'SEL' => 'Secrétariat Exécutif Local',
            default => $affectation->niveau_administratif,
        };
    }

    protected static function agentStructureLabel(Agent $agent): string
    {
        if ($agent->departement) {
            return 'le département ' . $agent->departement->nom;
        }

        return collect([
            $agent->organe,
            $agent->province?->nom,
            $agent->localite?->nom,
        ])->filter(fn ($value) => trim((string) $value) !== '')->implode(' / ');
    }

    protected static function affectationStructureLabel(Affectation $affectation): string
    {
        if ($affectation->cellule) {
            return 'la cellule ' . $affectation->cellule->nom;
        }

        if ($affectation->section) {
            return 'la section/service ' . $affectation->section->nom;
        }

        if ($affectation->department) {
            return 'le département ' . $affectation->department->nom;
        }

        if ($affectation->localite) {
            return 'le SEL ' . $affectation->localite->nom;
        }

        if ($affectation->province) {
            return 'le SEP ' . $affectation->province->nom;
        }

        return self::organeLabelForAffectation($affectation) ?? '';
    }

    protected static function agentDisplayName(Agent $agent): string
    {
        return collect([$agent->prenom, $agent->nom, $agent->postnom])
            ->filter(fn ($value) => trim((string) $value) !== '')
            ->implode(' ') ?: 'Agent #' . $agent->id;
    }

    protected static function agentLink(Agent $agent): string
    {
        return '/rh/agents/' . $agent->id;
    }

    protected static function normalizeStructureText(?string $value): string
    {
        $value = Str::ascii((string) $value);
        $value = strtolower(trim($value));

        return preg_replace('/\s+/', ' ', $value) ?? '';
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
