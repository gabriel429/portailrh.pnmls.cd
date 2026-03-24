<?php

// ──────────────────────────────────────────────────────────────
// web.php — Web routes (SPA catch-all is in bootstrap/app.php)
// ──────────────────────────────────────────────────────────────
//
// All GET pages are served by the Vue SPA via the catch-all in
// bootstrap/app.php.  Only server-side actions (POST, PUT, DELETE)
// and file downloads remain here.
// ──────────────────────────────────────────────────────────────

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\ParametresController;
use App\Http\Controllers\Admin\DeploymentController;
use App\Http\Controllers\DocumentTravailController;

// Named login route — required by Laravel auth middleware for redirect
Route::get('/login', function () {
    return view('spa');
})->name('login');

// Logout (POST) — destroy session and redirect to login
Route::post('/logout', function (\Illuminate\Http\Request $request) {
    \Illuminate\Support\Facades\Auth::guard('web')->logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect('/login');
})->middleware('auth')->name('logout');

// Document de travail download (file stream, not a page)
Route::get('/documents-travail/{documentTravail}/download', [DocumentTravailController::class, 'download'])
    ->middleware('auth')
    ->name('documents-travail.download');

// ── Admin server-side POST actions ───────────────────────────
Route::middleware(['auth', 'admin.nt'])->prefix('admin')->name('admin.')->group(function () {
    Route::post('/deployment/git-pull', [DeploymentController::class, 'gitPull'])->name('deployment.git-pull');
    Route::post('/deployment/migrate', [DeploymentController::class, 'migrate'])->name('deployment.migrate');
    Route::post('/deployment/migrate-fresh', [DeploymentController::class, 'migrateFresh'])->name('deployment.migrate-fresh');
    Route::post('/deployment/deploy-organes', [DeploymentController::class, 'deployOrganes'])->name('deployment.deploy-organes');
    Route::post('/deployment/deploy-users', [DeploymentController::class, 'deployUsers'])->name('deployment.deploy-users');
    Route::post('/deployment/deploy-departments', [DeploymentController::class, 'deployDepartments'])->name('deployment.deploy-departments');
    Route::post('/deployment/deploy-grades', [DeploymentController::class, 'deployGrades'])->name('deployment.deploy-grades');
    Route::post('/deployment/deploy-affectations', [DeploymentController::class, 'deployAffectations'])->name('deployment.deploy-affectations');
    Route::post('/deployment/deploy-institutions', [DeploymentController::class, 'deployInstitutions'])->name('deployment.deploy-institutions');
    Route::post('/deployment/deploy-messages', [DeploymentController::class, 'deployMessages'])->name('deployment.deploy-messages');
    Route::post('/deployment/deploy-communiques', [DeploymentController::class, 'deployCommuniques'])->name('deployment.deploy-communiques');
    Route::post('/deployment/deploy-taches', [DeploymentController::class, 'deployTaches'])->name('deployment.deploy-taches');
    Route::post('/deployment/deploy-plan-travail', [DeploymentController::class, 'deployPlanTravail'])->name('deployment.deploy-plan-travail');
    Route::post('/deployment/deploy-rename-roles', [DeploymentController::class, 'deployRenameRoles'])->name('deployment.deploy-rename-roles');
    Route::post('/deployment/deploy-domaine-etudes', [DeploymentController::class, 'deployDomaineEtudes'])->name('deployment.deploy-domaine-etudes');
    Route::post('/deployment/deploy-agents', [DeploymentController::class, 'deployAgents'])->name('deployment.deploy-agents');
    Route::post('/deployment/seed-superadmin', [DeploymentController::class, 'seedSuperAdmin'])->name('deployment.seed-superadmin');
    Route::post('/logs/clear', [ParametresController::class, 'logsClear'])->name('logs.clear');
});
