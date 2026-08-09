<?php

namespace App\Http\Controllers\RH;

use App\Http\Controllers\Controller;
use App\Models\Holiday;
use App\Models\HolidayModificationRequest;
use App\Services\HolidayPlanningWorkflowService;
use App\Services\NotificationService;
use App\Services\RoleService;
use App\Services\TacheWorkflowService;
use App\Services\UserDataScope;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HolidayModificationRequestController extends Controller
{
    public function index(Request $request)
    {
        $agent = $request->user()->agent;
        $query = HolidayModificationRequest::with(['holiday.agent', 'requestedBy', 'reviewedBy'])
            ->latest();
        $dataScope = app(UserDataScope::class);
        $query->whereHas('holiday.agent', function ($agentQuery) use ($dataScope, $request) {
            $dataScope->applyHolidayAgentScope($agentQuery, $request->user());
        });

        if (!$dataScope->hasGlobalHolidayAccess($request->user())) {
            $user = $request->user();
            $roleService = app(RoleService::class);
            $taskWorkflow = app(TacheWorkflowService::class);

            $query->where(function ($scope) use ($agent, $user, $roleService, $taskWorkflow) {
                $scope->where('requested_by', $agent->id);

                if ($roleService->hasDirecteurOrDafRole($user) && $agent->departement_id) {
                    $scope->orWhereHas('holiday.holidayPlanning', fn ($planning) => $planning
                        ->where('niveau_administratif', 'national')
                        ->where('structure_id', $agent->departement_id));
                }

                if ($roleService->isSepManager($user) && $agent->province_id) {
                    $scope->orWhereHas('holiday.holidayPlanning', fn ($planning) => $planning
                        ->where('niveau_administratif', 'provincial')
                        ->where('structure_id', $agent->province_id));
                }

                if ($taskWorkflow->isSelManager($user)) {
                    $localScopeId = $agent->localite_id ?: $agent->province_id;
                    $scope->orWhereHas('holiday.holidayPlanning', fn ($planning) => $planning
                        ->where('niveau_administratif', 'local')
                        ->where('structure_id', $localScopeId));
                }
            });
        }

        return response()->json($query->paginate(20));
    }

    public function store(Request $request, Holiday $holiday)
    {
        $agent = $request->user()->agent;
        if (!$agent || ($holiday->agent_id !== $agent->id && $holiday->demande_par !== $agent->id)) {
            return response()->json(['message' => 'Vous ne pouvez pas demander la modification de ce congé.'], 403);
        }

        if ($holiday->statut_demande !== 'approuve') {
            return response()->json(['message' => 'Seul un congé validé nécessite une demande de modification.'], 422);
        }

        if ($holiday->modificationRequests()->where('statut', 'en_attente')->exists()) {
            return response()->json(['message' => 'Une demande de modification est déjà en attente.'], 422);
        }

        $validated = $request->validate([
            'date_debut_proposee' => 'required|date',
            'date_fin_proposee' => 'required|date|after_or_equal:date_debut_proposee',
            'motif' => 'required|string|max:1000',
        ]);

        $start = Carbon::parse($validated['date_debut_proposee']);
        $end = Carbon::parse($validated['date_fin_proposee']);
        $days = Holiday::calculateWorkingDays($start, $end);
        if ($days < 1) {
            return response()->json(['message' => 'La période proposée doit contenir au moins un jour ouvrable.'], 422);
        }

        if (Holiday::hasConflict($holiday->agent_id, $start, $end, $holiday->id)) {
            return response()->json(['message' => 'La période proposée chevauche un autre congé validé.'], 422);
        }

        $changeRequest = HolidayModificationRequest::create([
            ...$validated,
            'holiday_id' => $holiday->id,
            'nombre_jours_proposes' => $days,
            'requested_by' => $agent->id,
        ]);

        return response()->json([
            'message' => 'Demande transmise à l’autorité compétente.',
            'modification_request' => $changeRequest->load('holiday.agent'),
        ], 201);
    }

    public function approve(Request $request, HolidayModificationRequest $holidayModificationRequest)
    {
        $holidayModificationRequest->load('holiday.agent', 'holiday.holidayPlanning');
        if (!app(UserDataScope::class)->canAccessHolidayAgent(
            $request->user(),
            $holidayModificationRequest->holiday->agent,
            true,
        )) {
            return response()->json(['message' => 'Ce congé est hors de votre périmètre.'], 403);
        }

        $planning = $holidayModificationRequest->holiday->holidayPlanning;

        if (!$planning || !app(HolidayPlanningWorkflowService::class)->canValidate($request->user(), $planning)) {
            return response()->json(['message' => 'Seule l’autorité compétente peut valider cette modification.'], 403);
        }

        if ($holidayModificationRequest->statut !== 'en_attente') {
            return response()->json(['message' => 'Cette demande a déjà été traitée.'], 422);
        }

        $decision = $request->validate(['decision_comment' => 'nullable|string|max:1000']);

        DB::transaction(function () use ($holidayModificationRequest, $request, $decision) {
            $holiday = $holidayModificationRequest->holiday;
            $oldDays = $holiday->nombre_jours;
            $holiday->update([
                'date_debut' => $holidayModificationRequest->date_debut_proposee,
                'date_fin' => $holidayModificationRequest->date_fin_proposee,
                'nombre_jours' => $holidayModificationRequest->nombre_jours_proposes,
                'date_retour_prevu' => Holiday::nextWorkingDayAfter(Carbon::parse($holidayModificationRequest->date_fin_proposee)),
                'date_retour_effectif' => null,
            ]);

            $holiday->holidayPlanning?->decrementJoursUtilises($oldDays);
            $holiday->holidayPlanning?->incrementJoursUtilises($holidayModificationRequest->nombre_jours_proposes);

            $holidayModificationRequest->update([
                'statut' => 'approuvee',
                'reviewed_by' => $request->user()->agent->id,
                'reviewed_at' => now(),
                'decision_comment' => $decision['decision_comment'] ?? null,
            ]);
        });

        $this->notifyRequester($holidayModificationRequest, 'Modification de congé validée');

        return response()->json([
            'message' => 'La nouvelle période a été validée et appliquée au planning.',
            'modification_request' => $holidayModificationRequest->fresh(['holiday.agent', 'reviewedBy']),
        ]);
    }

    public function reject(Request $request, HolidayModificationRequest $holidayModificationRequest)
    {
        $holidayModificationRequest->load('holiday.agent', 'holiday.holidayPlanning');
        if (!app(UserDataScope::class)->canAccessHolidayAgent(
            $request->user(),
            $holidayModificationRequest->holiday->agent,
            true,
        )) {
            return response()->json(['message' => 'Ce congé est hors de votre périmètre.'], 403);
        }

        $planning = $holidayModificationRequest->holiday->holidayPlanning;

        if (!$planning || !app(HolidayPlanningWorkflowService::class)->canValidate($request->user(), $planning)) {
            return response()->json(['message' => 'Seule l’autorité compétente peut refuser cette modification.'], 403);
        }

        $decision = $request->validate(['decision_comment' => 'required|string|max:1000']);
        $holidayModificationRequest->update([
            'statut' => 'refusee',
            'reviewed_by' => $request->user()->agent->id,
            'reviewed_at' => now(),
            'decision_comment' => $decision['decision_comment'],
        ]);
        $this->notifyRequester($holidayModificationRequest, 'Modification de congé refusée');

        return response()->json(['message' => 'Demande de modification refusée.']);
    }

    private function notifyRequester(HolidayModificationRequest $changeRequest, string $title): void
    {
        $userId = $changeRequest->requestedBy?->user?->id;
        if ($userId) {
            NotificationService::envoyer(
                $userId,
                'holiday_planning_validated',
                $title,
                "Votre demande concernant le congé de {$changeRequest->holiday->agent->nom_complet} a été traitée.",
                '/mon-planning-conges',
                null,
                false,
            );
        }
    }
}