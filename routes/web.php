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
    Route::post('/logs/clear', [ParametresController::class, 'logsClear'])->name('logs.clear');
});
