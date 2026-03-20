<?php

// ──────────────────────────────────────────────────────────────
// web.php — Minimal routes for SPA mode
// All UI is handled by the Vue SPA (spa.blade.php + catch-all).
// Only auth session routes remain here; everything else goes
// through the API (routes/api.php) + SPA catch-all.
// ──────────────────────────────────────────────────────────────

use Illuminate\Support\Facades\Route;

// Named login route — required by Laravel auth middleware for redirect
Route::get('/login', function () {
    return view('spa');
})->name('login');
