<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Affectation;
use App\Models\Agent;
use App\Models\Localite;
use App\Models\Organe;
use App\Models\Province;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class DataQualityController extends Controller
{
    private const EXPECTED_PROVINCES = [
        'Bas-Uele',
        'Equateur',
        'Haut-Katanga',
        'Haut-Lomami',
        'Haut-Uele',
        'Ituri',
        'Kasai',
        'Kasai Central',
        'Kasai Oriental',
        'Kinshasa',
        'Kongo Central',
        'Kwango',
        'Kwilu',
        'Lomami',
        'Lualaba',
        'Mai-Ndombe',
        'Maniema',
        'Mongala',
        'Nord-Kivu',
        'Nord-Ubangi',
        'Sankuru',
        'Sud-Kivu',
        'Sud-Ubangi',
        'Tanganyika',
        'Tshopo',
        'Tshuapa',
    ];

    public function index(): JsonResponse
    {
        $agents = Agent::with([
            'province:id,code,nom',
            'localite:id,code,nom,province_id',
            'localite.province:id,code,nom',
            'departement:id,code,nom',
            'role:id,nom_role',
            'user:id,name,email,agent_id',
        ])->get();

        $users = User::with([
            'agent:id,nom,postnom,prenom,email,email_prive,email_professionnel,telephone,telephone_professionnel,organe,fonction,poste_actuel,province_id,localite_id,statut',
            'role:id,nom_role',
        ])->get();

        $localites = Localite::with('province:id,code,nom')
            ->withCount('agents')
            ->orderBy('nom')
            ->get();

        $provinces = Province::withCount(['agents', 'localites'])
            ->orderBy('nom')
            ->get();

        $organes = Organe::where('actif', true)->orderInstitutionally()->get();
        $activeAffectations = Affectation::with([
            'agent:id,nom,postnom,prenom,organe,fonction,poste_actuel,statut',
            'province:id,code,nom',
            'localite:id,code,nom,province_id',
            'localite.province:id,code,nom',
        ])->where('actif', true)->get();

        $activeAgents = $agents->reject(fn (Agent $agent) => $this->isFormerAgent($agent));
        $knownOrganeNames = $organes
            ->pluck('nom')
            ->filter()
            ->mapWithKeys(fn ($name) => [$this->normalize($name) => $name]);

        $categories = collect([
            $this->usersWithoutAgent($users),
            $this->usersWithMissingAgent($users),
            $this->agentsWithoutUser($activeAgents),
            $this->userAgentEmailMismatch($users),
            $this->agentsMissingAccountEmail($activeAgents),
            $this->localAgentsWithoutLocalite($activeAgents),
            $this->provincialAgentsWithoutProvince($activeAgents),
            $this->localAgentsProvinceMismatch($activeAgents),
            $this->nationalAgentsWithLocalScope($activeAgents),
            $this->localitesWithoutProvince($localites),
            $this->provincesWithoutLocalites($provinces),
            $this->missingExpectedProvinces($provinces),
            $this->unknownOrganeLabels($activeAgents, $knownOrganeNames),
            $this->organeLabelsToHarmonize($activeAgents, $knownOrganeNames),
            $this->activeAffectationsMissingScope($activeAffectations),
            $this->duplicateUsersEmails($users),
            $this->duplicateAgentEmails($activeAgents),
            $this->duplicateMatricules($activeAgents),
        ])->values();

        $totalIssues = $categories->sum('count');
        $blockingIssues = $categories
            ->where('severity', 'danger')
            ->sum('count');
        $warningIssues = $categories
            ->where('severity', 'warning')
            ->sum('count');

        return response()->json([
            'generated_at' => now()->toIso8601String(),
            'summary' => [
                'score' => max(0, 100 - ($blockingIssues * 7) - ($warningIssues * 2)),
                'issues' => $totalIssues,
                'blocking' => $blockingIssues,
                'warnings' => $warningIssues,
                'agents' => $agents->count(),
                'users' => $users->count(),
                'localites' => $localites->count(),
                'provinces' => $provinces->count(),
                'organes' => $organes->count(),
            ],
            'categories' => $categories,
        ]);
    }

    private function usersWithoutAgent(Collection $users): array
    {
        $items = $users
            ->filter(fn (User $user) => !$user->is_super_admin && empty($user->agent_id))
            ->map(fn (User $user) => $this->userIssue(
                $user,
                'Ce compte ne pointe vers aucun agent. Il risque de voir des menus vides ou des donnees non filtrees.',
                ['Role' => $user->role?->nom_role ?: '-']
            ));

        return $this->category(
            'users_without_agent',
            'Utilisateurs sans agent lie',
            'danger',
            'Comptes applicatifs qui ne sont relies a aucune fiche agent.',
            $items,
            'Lier chaque utilisateur a sa fiche agent.'
        );
    }

    private function usersWithMissingAgent(Collection $users): array
    {
        $items = $users
            ->filter(fn (User $user) => !empty($user->agent_id) && !$user->agent)
            ->map(fn (User $user) => $this->userIssue(
                $user,
                'Le compte pointe vers un agent qui n existe plus dans la table agents.',
                ['Agent ID' => $user->agent_id]
            ));

        return $this->category(
            'users_with_missing_agent',
            'Utilisateurs avec agent introuvable',
            'danger',
            'Comptes dont la liaison agent_id est cassee.',
            $items,
            'Relier le compte au bon agent ou recreer la fiche agent.'
        );
    }

    private function agentsWithoutUser(Collection $agents): array
    {
        $items = $agents
            ->filter(fn (Agent $agent) => !$agent->user)
            ->map(fn (Agent $agent) => $this->agentIssue(
                $agent,
                'Cet agent actif n a pas encore de compte utilisateur.',
                ['Statut' => $agent->statut ?: '-'],
                '/admin/utilisateurs/create',
                'Creer le compte'
            ));

        return $this->category(
            'agents_without_user',
            'Agents actifs sans compte utilisateur',
            'warning',
            'Agents qui ne peuvent pas se connecter tant qu un compte n est pas cree.',
            $items,
            'Creer un utilisateur et selectionner l agent concerne.'
        );
    }

    private function userAgentEmailMismatch(Collection $users): array
    {
        $items = $users
            ->filter(function (User $user) {
                if (!$user->agent) {
                    return false;
                }

                $expected = $this->resolveAgentAccountEmail($user->agent);

                return $expected !== null
                    && $this->normalizeEmail($user->email) !== $this->normalizeEmail($expected);
            })
            ->map(function (User $user) {
                $expected = $this->resolveAgentAccountEmail($user->agent);

                return $this->userIssue(
                    $user,
                    'L email du compte ne correspond plus a l email prioritaire de la fiche agent.',
                    [
                        'Compte' => $user->email,
                        'Fiche agent' => $expected,
                    ]
                );
            });

        return $this->category(
            'user_agent_email_mismatch',
            'Emails utilisateur / agent incoherents',
            'warning',
            'Cas typique apres modification du mail professionnel de l agent.',
            $items,
            'Mettre a jour le compte utilisateur ou verifier l email professionnel de l agent.'
        );
    }

    private function agentsMissingAccountEmail(Collection $agents): array
    {
        $items = $agents
            ->filter(fn (Agent $agent) => $this->resolveAgentAccountEmail($agent) === null)
            ->map(fn (Agent $agent) => $this->agentIssue(
                $agent,
                'Aucun email valide n est disponible pour creer ou synchroniser le compte.',
                [
                    'Email pro' => $agent->email_professionnel ?: '-',
                    'Email agent' => $agent->email ?: '-',
                ]
            ));

        return $this->category(
            'agents_missing_account_email',
            'Agents sans email exploitable',
            'warning',
            'Fiches agents sans email professionnel, email principal ou email prive valide.',
            $items,
            'Renseigner au moins un email valide sur la fiche agent.'
        );
    }

    private function localAgentsWithoutLocalite(Collection $agents): array
    {
        $items = $agents
            ->filter(fn (Agent $agent) => $this->isLocalAgent($agent) && empty($agent->localite_id))
            ->map(fn (Agent $agent) => $this->agentIssue(
                $agent,
                'Agent local sans localite. Le RH local, le SEL, les demandes et le drill-down ne pourront pas le retrouver.',
                ['Organe' => $agent->organe ?: '-']
            ));

        return $this->category(
            'local_agents_without_localite',
            'Agents locaux sans localite',
            'danger',
            'Agents SEL ou RH local non rattaches a une localite.',
            $items,
            'Modifier la fiche agent et choisir la province puis la localite.'
        );
    }

    private function provincialAgentsWithoutProvince(Collection $agents): array
    {
        $items = $agents
            ->filter(fn (Agent $agent) => $this->isProvincialAgent($agent) && empty($agent->province_id))
            ->map(fn (Agent $agent) => $this->agentIssue(
                $agent,
                'Agent provincial sans province. Les dashboards SEP et les filtres provinciaux peuvent etre vides.',
                ['Organe' => $agent->organe ?: '-']
            ));

        return $this->category(
            'provincial_agents_without_province',
            'Agents provinciaux sans province',
            'danger',
            'Agents SEP non rattaches a une province.',
            $items,
            'Modifier la fiche agent et choisir la province.'
        );
    }

    private function localAgentsProvinceMismatch(Collection $agents): array
    {
        $items = $agents
            ->filter(function (Agent $agent) {
                return $this->isLocalAgent($agent)
                    && $agent->province_id
                    && $agent->localite?->province_id
                    && (int) $agent->province_id !== (int) $agent->localite->province_id;
            })
            ->map(fn (Agent $agent) => $this->agentIssue(
                $agent,
                'La province de l agent ne correspond pas a la province de sa localite.',
                [
                    'Province agent' => $agent->province?->nom ?: '-',
                    'Province localite' => $agent->localite?->province?->nom ?: '-',
                    'Localite' => $agent->localite?->nom ?: '-',
                ]
            ));

        return $this->category(
            'local_agents_province_mismatch',
            'Province et localite incoherentes',
            'danger',
            'Agents locaux rattaches a une province differente de celle de leur localite.',
            $items,
            'Aligner la province agent avec la province de la localite.'
        );
    }

    private function nationalAgentsWithLocalScope(Collection $agents): array
    {
        $items = $agents
            ->filter(fn (Agent $agent) => $this->isNationalAgent($agent) && ($agent->province_id || $agent->localite_id))
            ->map(fn (Agent $agent) => $this->agentIssue(
                $agent,
                'Agent national avec province ou localite renseignee. Cela peut le faire apparaitre dans de mauvais regroupements.',
                [
                    'Province' => $agent->province?->nom ?: '-',
                    'Localite' => $agent->localite?->nom ?: '-',
                ]
            ));

        return $this->category(
            'national_agents_with_local_scope',
            'Agents SEN avec scope local/provincial',
            'warning',
            'Agents nationaux qui portent encore une province ou localite.',
            $items,
            'Vider la province/localite pour les agents strictement SEN.'
        );
    }

    private function localitesWithoutProvince(Collection $localites): array
    {
        $items = $localites
            ->filter(fn (Localite $localite) => empty($localite->province_id))
            ->map(fn (Localite $localite) => $this->localiteIssue(
                $localite,
                'Cette localite n est liee a aucune province.',
                ['Agents' => $localite->agents_count]
            ));

        return $this->category(
            'localites_without_province',
            'Localites sans province',
            'danger',
            'Localites impossibles a filtrer correctement par province.',
            $items,
            'Modifier la localite et renseigner sa province.'
        );
    }

    private function provincesWithoutLocalites(Collection $provinces): array
    {
        $items = $provinces
            ->filter(fn (Province $province) => (int) $province->localites_count === 0)
            ->map(fn (Province $province) => $this->provinceIssue(
                $province,
                'Aucune localite n est encore creee pour cette province.',
                ['Agents' => $province->agents_count]
            ));

        return $this->category(
            'provinces_without_localites',
            'Provinces sans localites',
            'info',
            'Provinces presentes dans la base mais sans localites rattachees.',
            $items,
            'Creer les localites utiles pour les SEL et RH locaux.'
        );
    }

    private function missingExpectedProvinces(Collection $provinces): array
    {
        $existing = $provinces
            ->map(fn (Province $province) => $this->normalize($province->nom))
            ->all();

        $items = collect(self::EXPECTED_PROVINCES)
            ->reject(fn (string $name) => in_array($this->normalize($name), $existing, true))
            ->map(fn (string $name) => [
                'id' => 'province-missing-' . Str::slug($name),
                'entity_type' => 'province',
                'entity_id' => null,
                'title' => $name,
                'subtitle' => 'Province attendue RDC',
                'message' => 'Cette province manque dans la base.',
                'meta' => [],
                'action' => [
                    'label' => 'Creer la province',
                    'url' => '/admin/provinces/create',
                ],
            ]);

        return $this->category(
            'missing_expected_provinces',
            'Provinces RDC manquantes',
            'danger',
            'Controle de presence des 26 provinces attendues.',
            $items,
            'Executer la migration/creation des provinces ou les creer manuellement.'
        );
    }

    private function unknownOrganeLabels(Collection $agents, Collection $knownOrganeNames): array
    {
        $items = $agents
            ->filter(function (Agent $agent) use ($knownOrganeNames) {
                $organe = trim((string) $agent->organe);

                return $organe !== '' && !$knownOrganeNames->has($this->normalize($organe));
            })
            ->map(fn (Agent $agent) => $this->agentIssue(
                $agent,
                'Le libelle organe ne correspond a aucun organe actif connu.',
                ['Organe actuel' => $agent->organe]
            ));

        return $this->category(
            'unknown_organe_labels',
            'Organes inconnus sur des agents',
            'danger',
            'Libelles qui cassent les filtres par SEN, SEP ou SEL.',
            $items,
            'Corriger le libelle organe avec une valeur officielle.'
        );
    }

    private function organeLabelsToHarmonize(Collection $agents, Collection $knownOrganeNames): array
    {
        $items = $agents
            ->filter(function (Agent $agent) use ($knownOrganeNames) {
                $normalized = $this->normalize($agent->organe);
                if ($normalized === '' || !$knownOrganeNames->has($normalized)) {
                    return false;
                }

                return trim((string) $agent->organe) !== $knownOrganeNames->get($normalized);
            })
            ->map(fn (Agent $agent) => $this->agentIssue(
                $agent,
                'Le libelle est reconnu mais il n utilise pas la casse officielle.',
                [
                    'Actuel' => $agent->organe,
                    'Attendu' => $knownOrganeNames->get($this->normalize($agent->organe)),
                ]
            ));

        return $this->category(
            'organe_labels_to_harmonize',
            'Organes a harmoniser',
            'warning',
            'Mêmes organes mais ecrits differemment selon les fiches.',
            $items,
            'Remplacer par le libelle officiel de l organe.'
        );
    }

    private function activeAffectationsMissingScope(Collection $affectations): array
    {
        $items = $affectations
            ->filter(function (Affectation $affectation) {
                $level = $this->normalize($affectation->niveau_administratif . ' ' . $affectation->niveau);

                return (str_contains($level, 'sel') || str_contains($level, 'local')) && empty($affectation->localite_id)
                    || (str_contains($level, 'sep') || str_contains($level, 'province')) && empty($affectation->province_id);
            })
            ->map(function (Affectation $affectation) {
                return [
                    'id' => 'affectation-' . $affectation->id,
                    'entity_type' => 'affectation',
                    'entity_id' => $affectation->id,
                    'title' => $this->agentName($affectation->agent),
                    'subtitle' => trim(($affectation->niveau_administratif ?: '-') . ' / ' . ($affectation->niveau ?: '-')),
                    'message' => 'Affectation active sans rattachement geographique obligatoire.',
                    'meta' => $this->meta([
                        'Province' => $affectation->province?->nom ?: '-',
                        'Localite' => $affectation->localite?->nom ?: '-',
                    ]),
                    'action' => [
                        'label' => 'Voir affectations',
                        'url' => '/rh/affectations',
                    ],
                ];
            });

        return $this->category(
            'active_affectations_missing_scope',
            'Affectations actives sans scope',
            'danger',
            'Affectations locales/provinciales sans province ou localite.',
            $items,
            'Corriger l affectation active ou synchroniser la fiche agent.'
        );
    }

    private function duplicateUsersEmails(Collection $users): array
    {
        $items = $users
            ->filter(fn (User $user) => $this->normalizeEmail($user->email) !== '')
            ->groupBy(fn (User $user) => $this->normalizeEmail($user->email))
            ->filter(fn (Collection $group) => $group->count() > 1)
            ->flatMap(fn (Collection $group) => $group->map(fn (User $user) => $this->userIssue(
                $user,
                'Plusieurs comptes utilisent le meme email.',
                ['Email' => $user->email]
            )));

        return $this->category(
            'duplicate_user_emails',
            'Emails utilisateurs en doublon',
            'danger',
            'Doublons d emails dans les comptes utilisateurs.',
            $items,
            'Conserver un email unique par compte.'
        );
    }

    private function duplicateAgentEmails(Collection $agents): array
    {
        $items = $agents
            ->flatMap(function (Agent $agent) {
                return collect([
                    'Email professionnel' => $agent->email_professionnel,
                    'Email agent' => $agent->email,
                ])->map(fn ($email, $label) => [
                    'agent' => $agent,
                    'label' => $label,
                    'email' => $this->normalizeEmail($email),
                    'raw' => $email,
                ]);
            })
            ->filter(fn (array $row) => $row['email'] !== '')
            ->groupBy('email')
            ->filter(fn (Collection $group) => $group->pluck('agent.id')->unique()->count() > 1)
            ->flatMap(fn (Collection $group) => $group->map(fn (array $row) => $this->agentIssue(
                $row['agent'],
                'Cet email est utilise sur plusieurs fiches agents.',
                [
                    'Champ' => $row['label'],
                    'Email' => $row['raw'],
                ]
            )));

        return $this->category(
            'duplicate_agent_emails',
            'Emails agents en doublon',
            'warning',
            'Emails reutilises par plusieurs agents.',
            $items,
            'Verifier les fiches et garder un email par personne.'
        );
    }

    private function duplicateMatricules(Collection $agents): array
    {
        $items = $agents
            ->filter(fn (Agent $agent) => trim((string) $agent->matricule_etat) !== '')
            ->groupBy(fn (Agent $agent) => $this->normalize($agent->matricule_etat))
            ->filter(fn (Collection $group) => $group->count() > 1)
            ->flatMap(fn (Collection $group) => $group->map(fn (Agent $agent) => $this->agentIssue(
                $agent,
                'Ce matricule est partage par plusieurs agents.',
                ['Matricule' => $agent->matricule_etat]
            )));

        return $this->category(
            'duplicate_matricules',
            'Matricules en doublon',
            'warning',
            'Matricules Etat reutilises par plusieurs fiches agents.',
            $items,
            'Verifier le matricule ou la provenance du matricule.'
        );
    }

    private function category(
        string $key,
        string $title,
        string $severity,
        string $description,
        Collection $items,
        string $recommendation
    ): array {
        $items = $items->values();
        $limit = 60;

        return [
            'key' => $key,
            'title' => $title,
            'severity' => $severity,
            'description' => $description,
            'recommendation' => $recommendation,
            'count' => $items->count(),
            'items' => $items->take($limit)->values(),
            'overflow' => max(0, $items->count() - $limit),
        ];
    }

    private function agentIssue(
        Agent $agent,
        string $message,
        array $meta = [],
        ?string $url = null,
        string $label = 'Corriger la fiche'
    ): array {
        return [
            'id' => 'agent-' . $agent->id . '-' . Str::slug($message),
            'entity_type' => 'agent',
            'entity_id' => $agent->id,
            'title' => $this->agentName($agent),
            'subtitle' => collect([$agent->poste_actuel ?: $agent->fonction, $agent->organe])
                ->filter()
                ->implode(' · '),
            'message' => $message,
            'meta' => $this->meta($meta + [
                'Province' => $agent->province?->nom,
                'Localite' => $agent->localite?->nom,
            ]),
            'action' => [
                'label' => $label,
                'url' => $url ?: '/rh/agents/' . $agent->id . '/edit',
            ],
        ];
    }

    private function userIssue(User $user, string $message, array $meta = []): array
    {
        return [
            'id' => 'user-' . $user->id . '-' . Str::slug($message),
            'entity_type' => 'user',
            'entity_id' => $user->id,
            'title' => $user->name ?: ('Utilisateur #' . $user->id),
            'subtitle' => $user->email ?: '-',
            'message' => $message,
            'meta' => $this->meta($meta),
            'action' => [
                'label' => 'Corriger le compte',
                'url' => '/admin/utilisateurs/' . $user->id . '/edit',
            ],
        ];
    }

    private function localiteIssue(Localite $localite, string $message, array $meta = []): array
    {
        return [
            'id' => 'localite-' . $localite->id . '-' . Str::slug($message),
            'entity_type' => 'localite',
            'entity_id' => $localite->id,
            'title' => $localite->nom ?: ('Localite #' . $localite->id),
            'subtitle' => $localite->province?->nom ?: 'Sans province',
            'message' => $message,
            'meta' => $this->meta($meta),
            'action' => [
                'label' => 'Corriger la localite',
                'url' => '/admin/localites/' . $localite->id . '/edit',
            ],
        ];
    }

    private function provinceIssue(Province $province, string $message, array $meta = []): array
    {
        return [
            'id' => 'province-' . $province->id . '-' . Str::slug($message),
            'entity_type' => 'province',
            'entity_id' => $province->id,
            'title' => $province->nom ?: ('Province #' . $province->id),
            'subtitle' => $province->code ?: '-',
            'message' => $message,
            'meta' => $this->meta($meta),
            'action' => [
                'label' => 'Voir la province',
                'url' => '/admin/provinces/' . $province->id . '/edit',
            ],
        ];
    }

    private function meta(array $values): array
    {
        return collect($values)
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->map(fn ($value, $label) => [
                'label' => (string) $label,
                'value' => (string) $value,
            ])
            ->values()
            ->all();
    }

    private function agentName(?Agent $agent): string
    {
        if (!$agent) {
            return 'Agent introuvable';
        }

        $name = collect([$agent->prenom, $agent->nom, $agent->postnom])
            ->filter(fn ($part) => filled($part))
            ->implode(' ');

        return $name !== '' ? $name : ('Agent #' . $agent->id);
    }

    private function resolveAgentAccountEmail(?Agent $agent): ?string
    {
        if (!$agent) {
            return null;
        }

        foreach ([$agent->email_professionnel, $agent->email, $agent->email_prive] as $candidate) {
            $email = $this->normalizeEmail($candidate);
            if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return $email;
            }
        }

        return null;
    }

    private function isFormerAgent(Agent $agent): bool
    {
        return $this->normalize($agent->statut) === 'ancien';
    }

    private function isLocalAgent(Agent $agent): bool
    {
        $profile = $this->agentSearchText($agent);

        return str_contains($profile, 'local')
            || str_contains($profile, 'sel')
            || str_contains($profile, 'secretaire executif local')
            || str_contains($profile, 'rh local')
            || str_contains($profile, 'assistant administratif et financier');
    }

    private function isProvincialAgent(Agent $agent): bool
    {
        $profile = $this->agentSearchText($agent);

        return str_contains($profile, 'provincial') || str_contains($profile, 'sep');
    }

    private function isNationalAgent(Agent $agent): bool
    {
        $profile = $this->agentSearchText($agent);

        return str_contains($profile, 'national') || str_contains($profile, 'sen');
    }

    private function agentSearchText(Agent $agent): string
    {
        return $this->normalize(collect([
            $agent->organe,
            $agent->fonction,
            $agent->poste_actuel,
            $agent->role?->nom_role,
        ])->filter()->implode(' '));
    }

    private function normalizeEmail(mixed $value): string
    {
        return strtolower(trim((string) $value));
    }

    private function normalize(mixed $value): string
    {
        return Str::of((string) $value)
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', ' ')
            ->squish()
            ->toString();
    }
}
