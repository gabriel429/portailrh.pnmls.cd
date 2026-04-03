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
use App\Http\Controllers\Admin\DeploymentController;
use App\Http\Controllers\DocumentTravailController;

// ── Build assets with correct MIME types (Hostinger fix) ─────
Route::get('/build/assets/{file}', function (string $file) {
    $path = public_path("build/assets/{$file}");
    if (!file_exists($path)) {
        abort(404);
    }
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    $mimeTypes = [
        'js'  => 'application/javascript',
        'css' => 'text/css',
        'json' => 'application/json',
        'webmanifest' => 'application/manifest+json',
        'woff2' => 'font/woff2',
        'woff' => 'font/woff',
        'ttf' => 'font/ttf',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'svg' => 'image/svg+xml',
    ];
    $mime = $mimeTypes[$ext] ?? 'application/octet-stream';
    return response()->file($path, [
        'Content-Type' => $mime,
        'Cache-Control' => 'public, max-age=31536000, immutable',
    ]);
})->where('file', '.*');

Route::get('/build/{file}', function (string $file) {
    $path = public_path("build/{$file}");
    if (!file_exists($path) || !is_file($path)) {
        abort(404);
    }
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    $mimeTypes = [
        'js'  => 'application/javascript',
        'json' => 'application/json',
        'webmanifest' => 'application/manifest+json',
    ];
    $mime = $mimeTypes[$ext] ?? 'application/octet-stream';
    $cache = in_array($file, ['sw.js']) ? 'no-cache' : 'public, max-age=31536000, immutable';
    return response()->file($path, [
        'Content-Type' => $mime,
        'Cache-Control' => $cache,
    ]);
})->where('file', '[^/]+');

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

// ── Deployment actions (SuperAdmin only) ─────────────────────
Route::middleware(['auth', 'super.admin'])->prefix('admin/deployment')->name('admin.deployment.')->group(function () {
    Route::post('/git-pull', [DeploymentController::class, 'gitPull'])->name('git-pull');
    Route::post('/migrate', [DeploymentController::class, 'migrate'])->name('migrate');
    Route::post('/migrate-fresh', [DeploymentController::class, 'migrateFresh'])->name('migrate-fresh');
    Route::post('/deploy-organes', [DeploymentController::class, 'deployOrganes'])->name('deploy-organes');
    Route::post('/deploy-users', [DeploymentController::class, 'deployUsers'])->name('deploy-users');
    Route::post('/deploy-departments', [DeploymentController::class, 'deployDepartments'])->name('deploy-departments');
    Route::post('/deploy-grades', [DeploymentController::class, 'deployGrades'])->name('deploy-grades');
    Route::post('/deploy-affectations', [DeploymentController::class, 'deployAffectations'])->name('deploy-affectations');
    Route::post('/deploy-institutions', [DeploymentController::class, 'deployInstitutions'])->name('deploy-institutions');
    Route::post('/deploy-messages', [DeploymentController::class, 'deployMessages'])->name('deploy-messages');
    Route::post('/deploy-communiques', [DeploymentController::class, 'deployCommuniques'])->name('deploy-communiques');
    Route::post('/deploy-taches', [DeploymentController::class, 'deployTaches'])->name('deploy-taches');
    Route::post('/deploy-plan-travail', [DeploymentController::class, 'deployPlanTravail'])->name('deploy-plan-travail');
    Route::post('/deploy-rename-roles', [DeploymentController::class, 'deployRenameRoles'])->name('deploy-rename-roles');
    Route::post('/deploy-domaine-etudes', [DeploymentController::class, 'deployDomaineEtudes'])->name('deploy-domaine-etudes');
    Route::post('/deploy-agents', [DeploymentController::class, 'deployAgents'])->name('deploy-agents');
    Route::post('/seed-superadmin', [DeploymentController::class, 'seedSuperAdmin'])->name('seed-superadmin');
    Route::post('/deploy-holidays', [DeploymentController::class, 'deployHolidays'])->name('deploy-holidays');
});
