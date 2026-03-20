<?php

// ──────────────────────────────────────────────────────────────
// web_rh.php — Minimal server-side routes for SPA mode
// Only POST routes that trigger server-side operations are kept.
// All page-rendering (GET) routes removed — SPA catch-all
// in bootstrap/app.php handles them via Vue Router.
// ──────────────────────────────────────────────────────────────

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DeploymentController;

// Deployment POST actions — trigger server operations (git pull, migrate, etc.)
Route::middleware(['auth', 'admin.nt'])->prefix('admin')->name('admin.')->group(function () {
    Route::post('/deployment/deploy-organes', [DeploymentController::class, 'deployOrganes'])->name('deployment.deploy-organes');
    Route::post('/deployment/deploy-users', [DeploymentController::class, 'deployUsers'])->name('deployment.deploy-users');
    Route::post('/deployment/deploy-institutions', [DeploymentController::class, 'deployInstitutions'])->name('deployment.deploy-institutions');
    Route::post('/deployment/deploy-messages', [DeploymentController::class, 'deployMessages'])->name('deployment.deploy-messages');
    Route::post('/deployment/deploy-communiques', [DeploymentController::class, 'deployCommuniques'])->name('deployment.deploy-communiques');
    Route::post('/deployment/deploy-taches', [DeploymentController::class, 'deployTaches'])->name('deployment.deploy-taches');
    Route::post('/deployment/deploy-plan-travail', [DeploymentController::class, 'deployPlanTravail'])->name('deployment.deploy-plan-travail');
    Route::post('/deployment/deploy-rename-roles', [DeploymentController::class, 'deployRenameRoles'])->name('deployment.deploy-rename-roles');
    Route::post('/deployment/deploy-domaine-etudes', [DeploymentController::class, 'deployDomaineEtudes'])->name('deployment.deploy-domaine-etudes');
    Route::post('/deployment/deploy-departments', [DeploymentController::class, 'deployDepartments'])->name('deployment.deploy-departments');
    Route::post('/deployment/deploy-affectations', [DeploymentController::class, 'deployAffectations'])->name('deployment.deploy-affectations');
    Route::post('/deployment/migrate', [DeploymentController::class, 'migrate'])->name('deployment.migrate');
    Route::post('/deployment/migrate-fresh', [DeploymentController::class, 'migrateFresh'])->name('deployment.migrate-fresh');
    Route::post('/deployment/deploy-grades', [DeploymentController::class, 'deployGrades'])->name('deployment.deploy-grades');
    Route::post('/deployment/git-pull', [DeploymentController::class, 'gitPull'])->name('deployment.git-pull');
});
