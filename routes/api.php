<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ApiMetaController;
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
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Api\SyncController;
use App\Http\Controllers\Api\MyHolidayPlanningController;
use App\Http\Controllers\RH\HolidayPlanningController;
use App\Http\Controllers\RH\HolidayController;
use App\Http\Controllers\RH\AgentStatusController;
use App\Http\Controllers\Api\RenforcementController;

// Public
Route::get('/', [ApiMetaController::class, 'index']);
Route::get('/resources', [ApiMetaController::class, 'resources']);
Route::get('/openapi.json', [ApiMetaController::class, 'openapi']);

Route::post('/login', [\App\Http\Controllers\Api\AuthController::class, 'login'])
    ->middleware('throttle:5,1'); // Max 5 tentatives par minute

// --- Mobile API (token-based auth) ---
Route::prefix('mobile')->group(function () {
    Route::post('/login', [AuthController::class, 'mobileLogin'])
        ->middleware('throttle:5,1');
    Route::post('/register', [AuthController::class, 'mobileRegister'])
        ->middleware('throttle:3,1');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/user', [AuthController::class, 'user']);
        Route::post('/logout', [AuthController::class, 'mobileLogout']);
        Route::get('/dashboard', [DashboardController::class, 'index']);
        Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
        Route::get('/profile', [ApiProfileController::class, 'show']);
        Route::put('/profile', [ApiProfileController::class, 'update']);
        Route::put('/profile/password', [ApiProfileController::class, 'updatePassword']);
        Route::get('/notifications', [ApiNotificationController::class, 'index']);
        Route::post('/notifications/{notification}/read', [ApiNotificationController::class, 'markRead']);
        Route::get('/taches', [TacheController::class, 'index']);
        Route::put('/taches/{tache}/statut', [TacheController::class, 'updateStatut']);
        Route::get('/communiques', [CommuniqueController::class, 'index']);
        Route::get('/documents-travail', [DocumentTravailController::class, 'index']);
        Route::get('/mon-planning-conges', [MyHolidayPlanningController::class, 'index']);
    });
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
    Route::get('/dashboard/executive/organe/{code}', [\App\Http\Controllers\Api\ExecutiveDashboardController::class, 'organeDetail']);
    Route::get('/dashboard/executive/province/{id}', [\App\Http\Controllers\Api\ExecutiveDashboardController::class, 'provinceDetail']);
    Route::get('/dashboard/executive/department/{id}', [\App\Http\Controllers\Api\ExecutiveDashboardController::class, 'departmentDetail']);
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
    Route::post('requests/{request}/validate', [RequestController::class, 'validateStep']);
    Route::post('requests/{request}/reject', [RequestController::class, 'rejectStep']);

    // Taches
    Route::get('taches/create', [TacheController::class, 'create']);
    Route::get('taches/performance', [TacheController::class, 'performanceReport']);
    Route::apiResource('taches', TacheController::class)->except(['edit', 'update', 'destroy']);
    Route::put('taches/{tache}/statut', [TacheController::class, 'updateStatut']);
    Route::post('taches/{tache}/commentaire', [TacheController::class, 'addCommentaire']);
    Route::get('taches/{tache}/documents/{document}/download', [TacheController::class, 'downloadDocument']);
    Route::post('taches/{tache}/report', [TacheController::class, 'submitReport']);
    Route::get('taches/{tache}/reports', [TacheController::class, 'viewReports']);

    // Plan de Travail
    Route::get('plan-travail/create', [PlanTravailController::class, 'create']);
    Route::apiResource('plan-travail', PlanTravailController::class)->parameters(['plan-travail' => 'activitePlan']);
    Route::put('plan-travail/{activitePlan}/statut', [PlanTravailController::class, 'updateStatut']);
    Route::post('plan-travail/{activitePlan}/validate-section', [PlanTravailController::class, 'validateSection']);
    Route::post('plan-travail/{activitePlan}/validate-cellule', [PlanTravailController::class, 'validateCellule']);

    // Notifications (SPA)
    Route::get('notifications', [ApiNotificationController::class, 'index']);
    Route::post('notifications/{notification}/read', [ApiNotificationController::class, 'markRead']);
    Route::delete('notifications/{notification}', [ApiNotificationController::class, 'destroy']);

    // Absences
    Route::get('mes-absences', [ApiProfileController::class, 'mesAbsences']);

    // Mon Planning Congés (tous les agents authentifiés)
    Route::get('mon-planning-conges', [MyHolidayPlanningController::class, 'index']);

    // Documents de Travail
    Route::get('documents-travail', [DocumentTravailController::class, 'index']);
    Route::get('documents-travail/{doc}/download', [DocumentTravailController::class, 'download']);

    // Messages
    Route::get('messages/{message}', [MessageController::class, 'show']);

    // RH Agents (role-protected)
    Route::middleware('role:Section ressources humaines,Chef Section RH,RH National,RH Provincial,Section Nouvelle Technologie,Chef Section Nouvelle Technologie,Chef de Section Nouvelle Technologie,SEN')->group(function () {
        Route::get('rh/dashboard', [\App\Http\Controllers\Api\RhDashboardController::class, 'index']);
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
        Route::get('signalements/report/monthly', [SignalementController::class, 'reportMonthly']);
        Route::get('signalements/report/annual', [SignalementController::class, 'reportAnnual']);
        Route::apiResource('signalements', SignalementController::class);

        // Communiques
        Route::apiResource('communiques', CommuniqueController::class);

        // Renforcement des Capacites
        Route::get('renforcements/report/monthly', [RenforcementController::class, 'reportMonthly']);
        Route::get('renforcements/report/annual', [RenforcementController::class, 'reportAnnual']);
        Route::apiResource('renforcements', RenforcementController::class);
        Route::post('renforcements/{formation}/validate', [RenforcementController::class, 'validate']);
        Route::post('renforcements/{formation}/beneficiaire', [RenforcementController::class, 'addBeneficiaire']);
        Route::put('renforcements/{formation}/beneficiaire/{beneficiaire}', [RenforcementController::class, 'updateStatutBeneficiaire']);

        // Affectations
        Route::get('affectations', [ParametresController::class, 'apiAffectationsIndex']);
        Route::get('affectations/form-data', [ParametresController::class, 'apiAffectationsFormData']);
        Route::post('affectations', [ParametresController::class, 'apiAffectationsStore']);
        Route::put('affectations/{affectation}', [ParametresController::class, 'apiAffectationsUpdate']);
        Route::delete('affectations/{affectation}', [ParametresController::class, 'apiAffectationsDestroy']);

        // Gestion des Congés et Planning
        // Départements (lecture seule pour filtres congés)
        Route::get('departments', function () {
            return response()->json(\App\Models\Department::orderBy('nom')->get(['id', 'code', 'nom']));
        });
        // Planning des Congés
        Route::get('holiday-plannings', [HolidayPlanningController::class, 'index']);
        Route::get('holiday-plannings/calendar', [HolidayPlanningController::class, 'calendar']);
        Route::get('holiday-plannings/statistiques', [HolidayPlanningController::class, 'statistiques']);
        Route::get('holiday-plannings/export', [HolidayPlanningController::class, 'export']);
        Route::post('holiday-plannings', [HolidayPlanningController::class, 'store']);
        Route::get('holiday-plannings/{holidayPlanning}', [HolidayPlanningController::class, 'show']);
        Route::put('holiday-plannings/{holidayPlanning}', [HolidayPlanningController::class, 'update']);
        Route::post('holiday-plannings/{holidayPlanning}/validate', [HolidayPlanningController::class, 'validate']);
        Route::delete('holiday-plannings/{holidayPlanning}', [HolidayPlanningController::class, 'destroy']);

        // Congés Individuels
        Route::get('holidays', [HolidayController::class, 'index']);
        Route::get('holidays/pending', [HolidayController::class, 'pending']);
        Route::get('holidays/active', [HolidayController::class, 'active']);
        Route::get('holidays/agents-by-structure', [HolidayController::class, 'agentsByStructure']);
        Route::post('holidays', [HolidayController::class, 'store']);
        Route::post('holidays/batch', [HolidayController::class, 'storeBatch']);
        Route::get('holidays/{holiday}', [HolidayController::class, 'show']);
        Route::put('holidays/{holiday}', [HolidayController::class, 'update']);
        Route::post('holidays/{holiday}/approve', [HolidayController::class, 'approve']);
        Route::post('holidays/{holiday}/refuse', [HolidayController::class, 'refuse']);
        Route::post('holidays/{holiday}/cancel', [HolidayController::class, 'cancel']);
        Route::post('holidays/{holiday}/mark-returned', [HolidayController::class, 'markReturned']);

        // Statuts des Agents
        Route::get('agent-statuses', [AgentStatusController::class, 'index']);
        Route::get('agent-statuses/current', [AgentStatusController::class, 'current']);
        Route::get('agent-statuses/statistics', [AgentStatusController::class, 'statistics']);
        Route::get('agent-statuses/available', [AgentStatusController::class, 'available']);
        Route::get('agent-statuses/absence-report', [AgentStatusController::class, 'absenceReport']);
        Route::post('agent-statuses', [AgentStatusController::class, 'store']);
        Route::get('agent-statuses/{agentStatus}', [AgentStatusController::class, 'show']);
        Route::post('agent-statuses/{agentStatus}/approve', [AgentStatusController::class, 'approve']);
        Route::put('agent-statuses/{agentStatus}/extend', [AgentStatusController::class, 'extend']);

        // Utilitaires Planning
        Route::get('agents/{agent}/holidays/stats', [HolidayController::class, 'agentStats']);
        Route::get('agents/{agent}/statuses/history', [AgentStatusController::class, 'history']);
        Route::get('agents/{agent}/availability', [HolidayController::class, 'checkAvailability']);
    });

    // Admin NT (Chef Section Nouvelle Technologie)
    Route::middleware('admin.nt')->prefix('admin')->group(function () {
        Route::get('dashboard', [ParametresController::class, 'apiDashboard']);
        Route::get('agents/import-template', [ApiAgentController::class, 'importTemplate']);
        Route::post('agents/import', [ApiAgentController::class, 'import']);

        // Provinces
        Route::get('provinces', [ParametresController::class, 'apiProvincesIndex']);
        Route::post('provinces', [ParametresController::class, 'apiProvincesStore']);
        Route::get('provinces/{province}', [ParametresController::class, 'apiProvincesShow']);
        Route::put('provinces/{province}', [ParametresController::class, 'apiProvincesUpdate']);
        Route::delete('provinces/{province}', [ParametresController::class, 'apiProvincesDestroy']);

        // Grades
        Route::get('grades', [ParametresController::class, 'apiGradesIndex']);
        Route::get('grades/{grade}', [ParametresController::class, 'apiGradesShow']);
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
        Route::get('fonctions/{fonction}', [ParametresController::class, 'apiFonctionsShow']);
        Route::post('fonctions', [ParametresController::class, 'apiFonctionsStore']);
        Route::put('fonctions/{fonction}', [ParametresController::class, 'apiFonctionsUpdate']);
        Route::delete('fonctions/{fonction}', [ParametresController::class, 'apiFonctionsDestroy']);

        // Sections
        Route::get('sections', [ParametresController::class, 'apiSectionsIndex']);
        Route::get('sections/{section}', [ParametresController::class, 'apiSectionsShow']);
        Route::post('sections', [ParametresController::class, 'apiSectionsStore']);
        Route::put('sections/{section}', [ParametresController::class, 'apiSectionsUpdate']);
        Route::delete('sections/{section}', [ParametresController::class, 'apiSectionsDestroy']);

        // Cellules
        Route::get('cellules', [ParametresController::class, 'apiCellulesIndex']);
        Route::get('cellules/{cellule}', [ParametresController::class, 'apiCellulesShow']);
        Route::post('cellules', [ParametresController::class, 'apiCellulesStore']);
        Route::put('cellules/{cellule}', [ParametresController::class, 'apiCellulesUpdate']);
        Route::delete('cellules/{cellule}', [ParametresController::class, 'apiCellulesDestroy']);

        // Localites
        Route::get('localites', [ParametresController::class, 'apiLocalitesIndex']);
        Route::get('localites/{localite}', [ParametresController::class, 'apiLocalitesShow']);
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

        // Documents de Travail (read-only via admin)
        Route::get('documents-travail', [ParametresController::class, 'apiDocsTravailIndex']);
        Route::get('documents-travail/{documentTravail}', [ParametresController::class, 'apiDocsTravailShow']);

        // PTA import backend-only
        Route::post('pta/import-parsed', [PlanTravailController::class, 'importParsed']);

        // Categories Documents
        Route::get('categories-documents', [ParametresController::class, 'apiCategoriesDocsIndex']);
        Route::get('categories-documents/{categorieDocument}', [ParametresController::class, 'apiCategoriesDocsShow']);
        Route::post('categories-documents', [ParametresController::class, 'apiCategoriesDocsStore']);
        Route::put('categories-documents/{categorieDocument}', [ParametresController::class, 'apiCategoriesDocsUpdate']);
        Route::delete('categories-documents/{categorieDocument}', [ParametresController::class, 'apiCategoriesDocsDestroy']);

        // Logs
        Route::get('logs', [ParametresController::class, 'apiLogs']);
        Route::post('logs/clear', [ParametresController::class, 'apiLogsClear']);

        // Deployment
        Route::get('deployment', [ParametresController::class, 'apiDeploymentIndex']);

        // Fonctions by organe (used by forms)
        Route::get('fonctions-by-organe/{code}', [ParametresController::class, 'getAllFonctionsByOrgane']);
    });

    // Documents de Travail CRUD (SEN + RH)
    Route::middleware('role:SEN,Section ressources humaines,Chef Section RH,RH National,RH Provincial')->prefix('admin')->group(function () {
        Route::post('documents-travail', [ParametresController::class, 'apiDocsTravailStore']);
        Route::put('documents-travail/{documentTravail}', [ParametresController::class, 'apiDocsTravailUpdate']);
        Route::delete('documents-travail/{documentTravail}', [ParametresController::class, 'apiDocsTravailDestroy']);
    });

    // SuperAdmin routes (audit logs + user management)
    Route::middleware('super.admin')->prefix('superadmin')->group(function () {
        Route::get('audit-logs', [AuditLogController::class, 'index']);
        Route::get('audit-logs/tables', [AuditLogController::class, 'tables']);
        Route::get('audit-logs/users', [AuditLogController::class, 'users']);
        Route::get('audit-logs/{auditLog}', [AuditLogController::class, 'show']);
        Route::post('audit-logs/{auditLog}/revert', [AuditLogController::class, 'revert']);

        Route::get('utilisateurs', [ParametresController::class, 'apiSuperAdminUtilisateurs']);
        Route::post('utilisateurs/{user}/freeze', [ParametresController::class, 'apiUtilisateurFreeze']);
        Route::post('utilisateurs/{user}/unfreeze', [ParametresController::class, 'apiUtilisateurUnfreeze']);
    });
});

// Versioned API aliases for stabilized contracts
Route::prefix('v1')->as('v1.')->group(function () {
    Route::get('/', [ApiMetaController::class, 'index']);
    Route::get('/resources', [ApiMetaController::class, 'resources']);
    Route::get('/openapi.json', [ApiMetaController::class, 'openapi']);

    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:5,1');

    Route::get('/sync/status', [SyncController::class, 'status']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/user', [AuthController::class, 'user']);

        Route::post('/sync/pull', [SyncController::class, 'pull']);
        Route::post('/sync/push', [SyncController::class, 'push']);
        Route::get('/sync/dirty', [SyncController::class, 'dirty']);
        Route::post('/sync/mark-synced', [SyncController::class, 'markSynced']);
        Route::post('/sync/files/upload', [SyncController::class, 'uploadFile']);
        Route::get('/sync/files/download/{uuid}', [SyncController::class, 'downloadFile']);

        Route::get('/dashboard', [DashboardController::class, 'index']);
        Route::get('/dashboard/executive', [\App\Http\Controllers\Api\ExecutiveDashboardController::class, 'index']);

        Route::get('/profile', [ProfileController::class, 'apiShow']);
        Route::get('/profile/full', [ApiProfileController::class, 'show']);
        Route::put('/profile', [ApiProfileController::class, 'update']);
        Route::put('/profile/password', [ApiProfileController::class, 'updatePassword']);
        Route::get('mes-absences', [ApiProfileController::class, 'mesAbsences']);

        Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
        Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllRead']);
        Route::get('notifications', [ApiNotificationController::class, 'index']);
        Route::post('notifications/{notification}/read', [ApiNotificationController::class, 'markRead']);
        Route::delete('notifications/{notification}', [ApiNotificationController::class, 'destroy']);

        Route::apiResource('documents', DocumentController::class)->names('documents');
        Route::get('documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');

        Route::apiResource('requests', RequestController::class)->names('requests');
        Route::post('requests/{request}/validate', [RequestController::class, 'validateStep'])->name('requests.validate');
        Route::post('requests/{request}/reject', [RequestController::class, 'rejectStep'])->name('requests.reject');

        Route::get('taches/create', [TacheController::class, 'create'])->name('taches.create');
        Route::apiResource('taches', TacheController::class)->except(['edit', 'update', 'destroy'])->names('taches');
        Route::put('taches/{tache}/statut', [TacheController::class, 'updateStatut'])->name('taches.update-statut');
        Route::post('taches/{tache}/commentaire', [TacheController::class, 'addCommentaire'])->name('taches.add-commentaire');

        Route::get('plan-travail/create', [PlanTravailController::class, 'create'])->name('plan-travail.create');
        Route::apiResource('plan-travail', PlanTravailController::class)
            ->parameters(['plan-travail' => 'activitePlan'])
            ->names('plan-travail');
        Route::put('plan-travail/{activitePlan}/statut', [PlanTravailController::class, 'updateStatut'])->name('plan-travail.update-statut');

        Route::get('mon-planning-conges', [MyHolidayPlanningController::class, 'index'])->name('mon-planning-conges.index');
        Route::get('documents-travail', [DocumentTravailController::class, 'index'])->name('documents-travail.index');
        Route::get('documents-travail/{doc}/download', [DocumentTravailController::class, 'download'])->name('documents-travail.download');
        Route::get('messages/{message}', [MessageController::class, 'show'])->name('messages.show');

        Route::middleware('role:Section ressources humaines,Chef Section RH,RH National,RH Provincial,Section Nouvelle Technologie,Chef Section Nouvelle Technologie,Chef de Section Nouvelle Technologie,SEN')->group(function () {
            Route::get('rh/dashboard', [\App\Http\Controllers\Api\RhDashboardController::class, 'index'])->name('rh.dashboard');
            Route::get('agents/export', [ApiAgentController::class, 'export'])->name('agents.export');
            Route::get('agents/form-options', [ApiAgentController::class, 'formOptions'])->name('agents.form-options');
            Route::apiResource('agents', ApiAgentController::class)->names('agents');

            Route::get('pointages/daily', [\App\Http\Controllers\Api\PointageController::class, 'daily'])->name('pointages.daily');
            Route::get('pointages/daily/export', [\App\Http\Controllers\Api\PointageController::class, 'exportDaily'])->name('pointages.daily-export');
            Route::get('pointages/monthly', [\App\Http\Controllers\Api\PointageController::class, 'monthly'])->name('pointages.monthly');
            Route::get('pointages/monthly/export', [\App\Http\Controllers\Api\PointageController::class, 'exportMonthly'])->name('pointages.monthly-export');
            Route::get('pointages/agents-by-department', [\App\Http\Controllers\Api\PointageController::class, 'agentsByDepartment'])->name('pointages.agents-by-department');
            Route::apiResource('pointages', \App\Http\Controllers\Api\PointageController::class)->names('pointages');

            Route::get('signalements/agents', [SignalementController::class, 'agents'])->name('signalements.agents');
            Route::apiResource('signalements', SignalementController::class)->names('signalements');
            Route::apiResource('communiques', CommuniqueController::class)->names('communiques');
        });
    });
});
