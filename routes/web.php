<?php

// ──────────────────────────────────────────────────────────────
// web.php — Minimal routes for SPA mode
// All UI is handled by the Vue SPA (spa.blade.php + catch-all).
// Only auth session routes remain here; everything else goes
// through the API (routes/api.php) + SPA catch-all.
// ──────────────────────────────────────────────────────────────

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DeploymentController;

// Named login route — required by Laravel auth middleware for redirect
Route::get('/login', function () {
    return view('spa');
})->name('login');

// ── Admin Deployment Routes (Blade-based, not SPA) ──────────────────
// These routes are used by the deployment page which is a Blade view
// for managing server-side operations (git pull, migrations, seeders).
Route::prefix('admin/deployment')->name('admin.deployment.')->middleware('auth')->group(function () {
    Route::get('/', [DeploymentController::class, 'index'])->name('index');
    Route::post('/git-pull', [DeploymentController::class, 'gitPull'])->name('git-pull');
    Route::post('/migrate', [DeploymentController::class, 'migrate'])->name('migrate');
    Route::post('/migrate-fresh', [DeploymentController::class, 'migrateFresh'])->name('migrate-fresh');
    Route::post('/deploy-organes', [DeploymentController::class, 'deployOrganes'])->name('deploy-organes');
    Route::post('/deploy-users', [DeploymentController::class, 'deployUsers'])->name('deploy-users');
    Route::post('/deploy-institutions', [DeploymentController::class, 'deployInstitutions'])->name('deploy-institutions');
    Route::post('/deploy-messages', [DeploymentController::class, 'deployMessages'])->name('deploy-messages');
    Route::post('/deploy-communiques', [DeploymentController::class, 'deployCommuniques'])->name('deploy-communiques');
    Route::post('/deploy-taches', [DeploymentController::class, 'deployTaches'])->name('deploy-taches');
    Route::post('/deploy-plan-travail', [DeploymentController::class, 'deployPlanTravail'])->name('deploy-plan-travail');
    Route::post('/deploy-rename-roles', [DeploymentController::class, 'deployRenameRoles'])->name('deploy-rename-roles');
    Route::post('/deploy-domaine-etudes', [DeploymentController::class, 'deployDomaineEtudes'])->name('deploy-domaine-etudes');
    Route::post('/deploy-departments', [DeploymentController::class, 'deployDepartments'])->name('deploy-departments');
    Route::post('/deploy-affectations', [DeploymentController::class, 'deployAffectations'])->name('deploy-affectations');
    Route::post('/deploy-grades', [DeploymentController::class, 'deployGrades'])->name('deploy-grades');
});
