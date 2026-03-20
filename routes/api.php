<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ProfileController as ApiProfileController;
use App\Http\Controllers\Api\AgentController as ApiAgentController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\RequestController;
use App\Http\Controllers\Api\TacheController;
use App\Http\Controllers\Api\PlanTravailController;
use App\Http\Controllers\Api\NotificationController as ApiNotificationController;
use App\Http\Controllers\Api\SignalementController;
use App\Http\Controllers\Api\CommuniqueController;
use App\Http\Controllers\Api\DocumentTravailController;
use App\Http\Controllers\Api\MessageController;

// Public
Route::post('/login', [AuthController::class, 'login']);

// Authenticated (Sanctum SPA cookie auth)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // Profile (legacy)
    Route::get('/profile', [ProfileController::class, 'apiShow']);

    // Profile (SPA)
    Route::get('/profile/full', [ApiProfileController::class, 'show']);
    Route::put('/profile', [ApiProfileController::class, 'update']);
    Route::put('/profile/password', [ApiProfileController::class, 'updatePassword']);

    // Notifications
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllRead']);

    // Documents (GED)
    Route::apiResource('documents', DocumentController::class);
    Route::get('documents/{document}/download', [DocumentController::class, 'download']);

    // Requests (Demandes)
    Route::apiResource('requests', RequestController::class);

    // Taches
    Route::get('taches/create', [TacheController::class, 'create']);
    Route::apiResource('taches', TacheController::class)->except(['edit', 'update', 'destroy']);
    Route::put('taches/{tache}/statut', [TacheController::class, 'updateStatut']);
    Route::post('taches/{tache}/commentaire', [TacheController::class, 'addCommentaire']);

    // Plan de Travail
    Route::get('plan-travail/create', [PlanTravailController::class, 'create']);
    Route::apiResource('plan-travail', PlanTravailController::class)->parameters(['plan-travail' => 'activitePlan']);
    Route::put('plan-travail/{activitePlan}/statut', [PlanTravailController::class, 'updateStatut']);

    // Notifications (SPA)
    Route::get('notifications', [ApiNotificationController::class, 'index']);
    Route::post('notifications/{notification}/read', [ApiNotificationController::class, 'markRead']);
    Route::delete('notifications/{notification}', [ApiNotificationController::class, 'destroy']);

    // Absences
    Route::get('mes-absences', [ApiProfileController::class, 'mesAbsences']);

    // Documents de Travail
    Route::get('documents-travail', [DocumentTravailController::class, 'index']);
    Route::get('documents-travail/{doc}/download', [DocumentTravailController::class, 'download']);

    // Messages
    Route::get('messages/{message}', [MessageController::class, 'show']);

    // RH Agents (role-protected)
    Route::middleware('role:Section ressources humaines,Chef Section RH,RH National,RH Provincial,Section Nouvelle Technologie,Chef Section Nouvelle Technologie,Chef de Section Nouvelle Technologie')->group(function () {
        Route::get('agents/export', [ApiAgentController::class, 'export']);
        Route::get('agents/form-options', [ApiAgentController::class, 'formOptions']);
        Route::apiResource('agents', ApiAgentController::class);

        // Pointages (Attendance) - custom routes BEFORE apiResource
        Route::get('pointages/daily', [\App\Http\Controllers\Api\PointageController::class, 'daily']);
        Route::get('pointages/daily/export', [\App\Http\Controllers\Api\PointageController::class, 'exportDaily']);
        Route::get('pointages/monthly', [\App\Http\Controllers\Api\PointageController::class, 'monthly']);
        Route::get('pointages/monthly/export', [\App\Http\Controllers\Api\PointageController::class, 'exportMonthly']);
        Route::get('pointages/agents-by-department', [\App\Http\Controllers\Api\PointageController::class, 'agentsByDepartment']);
        Route::apiResource('pointages', \App\Http\Controllers\Api\PointageController::class);

        // Signalements
        Route::get('signalements/agents', [SignalementController::class, 'agents']);
        Route::apiResource('signalements', SignalementController::class);

        // Communiques
        Route::apiResource('communiques', CommuniqueController::class);
    });
});
