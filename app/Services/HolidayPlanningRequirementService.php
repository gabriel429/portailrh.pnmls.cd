<?php

namespace App\Services;

use App\Models\Department;
use App\Models\HolidayPlanning;
use App\Models\Localite;
use App\Models\NotificationPortail;
use App\Models\Province;
use App\Models\Tache;
use App\Models\User;
use Illuminate\Support\Collection;

class HolidayPlanningRequirementService
{
    public function generateForYear(int $year): array
    {
        $createdTasks = 0;
        $createdNotifications = 0;

        User::with(['agent.departement', 'role'])
            ->whereHas('agent', fn ($query) => $query->where('statut', 'actif'))
            ->get()
            ->each(function (User $user) use ($year, &$createdTasks, &$createdNotifications) {
                $responsibility = app(HolidayPlanningWorkflowService::class)->responsibilityFor($user);
                if (!$responsibility) {
                    return;
                }

                $planning = HolidayPlanning::forYear($year)
                    ->forStructure($responsibility['type'], $responsibility['structure_id'])
                    ->first();

                if ($planning?->statut === HolidayPlanning::STATUT_VALIDE) {
                    $this->closeForPlanning($planning, $planning->validated_by);

                    return;
                }

                $scopeKey = $this->scopeKey(
                    $year,
                    $responsibility['type'],
                    $responsibility['structure_id'],
                );
                $structureName = $this->structureName($responsibility['type'], $responsibility['structure_id']);
                $task = Tache::firstOrCreate(
                    ['system_key' => "{$scopeKey}:task:{$user->agent->id}"],
                    [
                        'createur_id' => $user->agent->id,
                        'agent_id' => $user->agent->id,
                        'titre' => "Soumission du planning annuel de congés - {$year}",
                        'description' => "Élaborer et soumettre le planning annuel de congés {$year} de {$structureName}.",
                        'source_type' => 'hors_pta',
                        'source_emetteur' => $responsibility['level'] === 'national' ? 'assistant_departement' : 'autre',
                        'niveau_gestion' => match ($responsibility['level']) {
                            'provincial' => 'province',
                            'local' => 'local',
                            default => 'departement',
                        },
                        'validation_responsable_role' => match ($responsibility['level']) {
                            'provincial' => 'sep',
                            'local' => 'sel',
                            default => 'directeur',
                        },
                        'priorite' => 'haute',
                        'statut' => 'nouvelle',
                        'pourcentage' => 0,
                        'validation_statut' => 'non_requise',
                        'date_tache' => "{$year}-01-01",
                        'date_echeance' => "{$year}-03-31",
                    ],
                );
                $createdTasks += $task->wasRecentlyCreated ? 1 : 0;

                $actorNotification = NotificationPortail::firstOrCreate(
                    [
                        'user_id' => $user->id,
                        'type' => 'holiday_planning_required_actor',
                        'context_key' => "{$scopeKey}:actor:{$user->agent->id}",
                    ],
                    [
                        'titre' => "Action requise : planning annuel {$year}",
                        'message' => "Le planning annuel de congés {$year} de {$structureName} n'a pas encore été soumis et validé.",
                        'icone' => 'fa-calendar-exclamation',
                        'couleur' => '#dc2626',
                        'lien' => '/rh/holidays/planning',
                        'lu' => false,
                    ],
                );
                $createdNotifications += $actorNotification->wasRecentlyCreated ? 1 : 0;

                $this->concernedUsers($responsibility['type'], $responsibility['structure_id'])
                    ->where('id', '!=', $user->id)
                    ->each(function (User $concernedUser) use ($year, $scopeKey, $structureName, $user, &$createdNotifications) {
                        $notification = NotificationPortail::firstOrCreate(
                            [
                                'user_id' => $concernedUser->id,
                                'type' => 'holiday_planning_unavailable',
                                'context_key' => "{$scopeKey}:agents",
                            ],
                            [
                                'titre' => "Planning annuel {$year} indisponible",
                                'message' => "Votre planning annuel de congés n'est pas encore disponible. Veuillez vous rapprocher de {$user->agent->nom_complet}.",
                                'icone' => 'fa-calendar-xmark',
                                'couleur' => '#0369a1',
                                'lien' => '/holidays/my-planning',
                                'lu' => false,
                            ],
                        );
                        $createdNotifications += $notification->wasRecentlyCreated ? 1 : 0;
                    });
            });

        return [
            'tasks_created' => $createdTasks,
            'notifications_created' => $createdNotifications,
        ];
    }

    public function closeForPlanning(HolidayPlanning $planning, ?int $validatorAgentId = null): void
    {
        $scopeKey = $this->scopeKey($planning->annee, $planning->type_structure, $planning->structure_id);

        Tache::where('system_key', 'like', "{$scopeKey}:task:%")
            ->where('statut', '!=', 'terminee')
            ->update([
                'statut' => 'terminee',
                'pourcentage' => 100,
                'validation_statut' => 'validee',
                'validated_by' => $validatorAgentId,
                'validated_at' => now(),
            ]);

        NotificationPortail::where('context_key', 'like', "{$scopeKey}:%")->delete();
    }

    private function concernedUsers(string $type, int $structureId): Collection
    {
        return User::query()
            ->whereHas('agent', function ($query) use ($type, $structureId) {
                $query->where('statut', 'actif');

                if ($type === 'department') {
                    $query->where('departement_id', $structureId);
                } elseif ($type === 'local') {
                    $query->where('localite_id', $structureId);
                } else {
                    $query->where('province_id', $structureId);
                }
            })
            ->get();
    }

    private function structureName(string $type, int $structureId): string
    {
        if ($type === 'department') {
            return Department::find($structureId)?->nom ?? "la structure {$structureId}";
        }

        if ($type === 'local') {
            return Localite::find($structureId)?->nom ?? "la structure locale {$structureId}";
        }

        return Province::find($structureId)?->nom ?? "la structure {$structureId}";
    }

    private function scopeKey(int $year, string $type, int $structureId): string
    {
        return "holiday-planning:{$year}:{$type}:{$structureId}";
    }
}