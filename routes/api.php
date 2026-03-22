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
use App\Http\Controllers\Admin\ParametresController;
use App\Http\Controllers\Api\SyncController;

// Public
Route::post('/login', [AuthController::class, 'login']);

// Diagnostic - remove after debugging
Route::get('/health-check', function () {
    $checks = [];
    try {
        \DB::connection()->getPdo();
        $checks['database'] = 'ok';
    } catch (\Throwable $e) {
        $checks['database'] = 'FAIL: ' . $e->getMessage();
    }
    try {
        $hasUsersTable = \Schema::hasTable('users');
        $checks['users_table'] = $hasUsersTable ? 'ok' : 'MISSING';
    } catch (\Throwable $e) {
        $checks['users_table'] = 'FAIL: ' . $e->getMessage();
    }
    try {
        $hasSessionsTable = \Schema::hasTable('sessions');
        $checks['sessions_table'] = $hasSessionsTable ? 'ok' : 'MISSING';
    } catch (\Throwable $e) {
        $checks['sessions_table'] = 'FAIL: ' . $e->getMessage();
    }
    $checks['session_driver'] = config('session.driver');
    $checks['php_version'] = PHP_VERSION;
    $checks['laravel_version'] = app()->version();
    return response()->json($checks);
});

// Sync status (public, for online detection)
Route::get('/sync/status', [SyncController::class, 'status']);

// Authenticated (Sanctum SPA cookie auth)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    // Sync endpoints (desktop ↔ server)
    Route::post('/sync/pull', [SyncController::class, 'pull']);
    Route::post('/sync/push', [SyncController::class, 'push']);
    Route::get('/sync/dirty', [SyncController::class, 'dirty']);
    Route::post('/sync/mark-synced', [SyncController::class, 'markSynced']);
    Route::post('/sync/files/upload', [SyncController::class, 'uploadFile']);
    Route::get('/sync/files/download/{uuid}', [SyncController::class, 'downloadFile']);

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/dashboard/executive', [\App\Http\Controllers\Api\ExecutiveDashboardController::class, 'index']);

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
    Route::middleware('role:Section ressources humaines,Chef Section RH,RH National,RH Provincial,Section Nouvelle Technologie,Chef Section Nouvelle Technologie,Chef de Section Nouvelle Technologie,SEN')->group(function () {
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

    // Admin NT (Chef Section Nouvelle Technologie)
    Route::middleware('admin.nt')->prefix('admin')->group(function () {
        Route::get('dashboard', [ParametresController::class, 'apiDashboard']);

        // Provinces
        Route::get('provinces', [ParametresController::class, 'apiProvincesIndex']);
        Route::post('provinces', [ParametresController::class, 'apiProvincesStore']);
        Route::get('provinces/{province}', [ParametresController::class, 'apiProvincesShow']);
        Route::put('provinces/{province}', [ParametresController::class, 'apiProvincesUpdate']);
        Route::delete('provinces/{province}', [ParametresController::class, 'apiProvincesDestroy']);

        // Grades
        Route::get('grades', [ParametresController::class, 'apiGradesIndex']);
        Route::post('grades', [ParametresController::class, 'apiGradesStore']);
        Route::put('grades/{grade}', [ParametresController::class, 'apiGradesUpdate']);
        Route::delete('grades/{grade}', [ParametresController::class, 'apiGradesDestroy']);

        // Roles
        Route::get('roles', [ParametresController::class, 'apiRolesIndex']);
        Route::get('roles/{role}', [ParametresController::class, 'apiRolesShow']);
        Route::post('roles', [ParametresController::class, 'apiRolesStore']);
        Route::put('roles/{role}', [ParametresController::class, 'apiRolesUpdate']);
        Route::delete('roles/{role}', [ParametresController::class, 'apiRolesDestroy']);

        // Departments
        Route::get('departments', [ParametresController::class, 'apiDepartmentsIndex']);
        Route::get('departments/{department}', [ParametresController::class, 'apiDepartmentsShow']);
        Route::post('departments', [ParametresController::class, 'apiDepartmentsStore']);
        Route::put('departments/{department}', [ParametresController::class, 'apiDepartmentsUpdate']);
        Route::delete('departments/{department}', [ParametresController::class, 'apiDepartmentsDestroy']);

        // Fonctions
        Route::get('fonctions', [ParametresController::class, 'apiFonctionsIndex']);
        Route::post('fonctions', [ParametresController::class, 'apiFonctionsStore']);
        Route::put('fonctions/{fonction}', [ParametresController::class, 'apiFonctionsUpdate']);
        Route::delete('fonctions/{fonction}', [ParametresController::class, 'apiFonctionsDestroy']);

        // Sections
        Route::get('sections', [ParametresController::class, 'apiSectionsIndex']);
        Route::post('sections', [ParametresController::class, 'apiSectionsStore']);
        Route::put('sections/{section}', [ParametresController::class, 'apiSectionsUpdate']);
        Route::delete('sections/{section}', [ParametresController::class, 'apiSectionsDestroy']);

        // Cellules
        Route::get('cellules', [ParametresController::class, 'apiCellulesIndex']);
        Route::post('cellules', [ParametresController::class, 'apiCellulesStore']);
        Route::put('cellules/{cellule}', [ParametresController::class, 'apiCellulesUpdate']);
        Route::delete('cellules/{cellule}', [ParametresController::class, 'apiCellulesDestroy']);

        // Localites
        Route::get('localites', [ParametresController::class, 'apiLocalitesIndex']);
        Route::post('localites', [ParametresController::class, 'apiLocalitesStore']);
        Route::put('localites/{localite}', [ParametresController::class, 'apiLocalitesUpdate']);
        Route::delete('localites/{localite}', [ParametresController::class, 'apiLocalitesDestroy']);

        // Organes
        Route::get('organes', [ParametresController::class, 'apiOrganesIndex']);
        Route::get('organes/{organe}', [ParametresController::class, 'apiOrganesShow']);
        Route::post('organes', [ParametresController::class, 'apiOrganesStore']);
        Route::put('organes/{organe}', [ParametresController::class, 'apiOrganesUpdate']);
        Route::delete('organes/{organe}', [ParametresController::class, 'apiOrganesDestroy']);

        // Utilisateurs
        Route::get('utilisateurs', [ParametresController::class, 'apiUtilisateursIndex']);
        Route::get('utilisateurs/form-data', [ParametresController::class, 'apiUtilisateursFormData']);
        Route::get('utilisateurs/{user}', [ParametresController::class, 'apiUtilisateursShow']);
        Route::post('utilisateurs', [ParametresController::class, 'apiUtilisateursStore']);
        Route::put('utilisateurs/{user}', [ParametresController::class, 'apiUtilisateursUpdate']);
        Route::delete('utilisateurs/{user}', [ParametresController::class, 'apiUtilisateursDestroy']);

        // Documents de Travail
        Route::get('documents-travail', [ParametresController::class, 'apiDocsTravailIndex']);
        Route::get('documents-travail/{documentTravail}', [ParametresController::class, 'apiDocsTravailShow']);
        Route::post('documents-travail', [ParametresController::class, 'apiDocsTravailStore']);
        Route::put('documents-travail/{documentTravail}', [ParametresController::class, 'apiDocsTravailUpdate']);
        Route::delete('documents-travail/{documentTravail}', [ParametresController::class, 'apiDocsTravailDestroy']);

        // Categories Documents
        Route::get('categories-documents', [ParametresController::class, 'apiCategoriesDocsIndex']);
        Route::get('categories-documents/{categorieDocument}', [ParametresController::class, 'apiCategoriesDocsShow']);
        Route::post('categories-documents', [ParametresController::class, 'apiCategoriesDocsStore']);
        Route::put('categories-documents/{categorieDocument}', [ParametresController::class, 'apiCategoriesDocsUpdate']);
        Route::delete('categories-documents/{categorieDocument}', [ParametresController::class, 'apiCategoriesDocsDestroy']);

        // Affectations
        Route::get('affectations', [ParametresController::class, 'apiAffectationsIndex']);
        Route::get('affectations/form-data', [ParametresController::class, 'apiAffectationsFormData']);
        Route::post('affectations', [ParametresController::class, 'apiAffectationsStore']);
        Route::put('affectations/{affectation}', [ParametresController::class, 'apiAffectationsUpdate']);
        Route::delete('affectations/{affectation}', [ParametresController::class, 'apiAffectationsDestroy']);

        // Logs
        Route::get('logs', [ParametresController::class, 'apiLogs']);
        Route::post('logs/clear', [ParametresController::class, 'apiLogsClear']);

        // Deployment
        Route::get('deployment', [ParametresController::class, 'apiDeploymentIndex']);

        // Fonctions by organe (used by forms)
        Route::get('fonctions-by-organe/{code}', [ParametresController::class, 'getAllFonctionsByOrgane']);
    });
});
