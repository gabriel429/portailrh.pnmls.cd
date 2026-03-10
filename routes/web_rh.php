<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\RH\AgentController;
use App\Http\Controllers\RH\DocumentController;
use App\Http\Controllers\RH\RequestController;
use App\Http\Controllers\RH\PointageController;
use App\Http\Controllers\RH\SignalementController;
use App\Http\Controllers\RH\RoleController;
use App\Http\Controllers\RH\PermissionController;
use App\Http\Controllers\RH\ProvinceController;
use App\Http\Controllers\RH\DepartmentController;

// Routes publiques
Route::get('/', function () {
    return view('welcome');
})->name('home');

// Routes d'authentification
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// Routes protégées
Route::middleware('auth')->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Profil utilisateur
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('/profile/password', [ProfileController::class, 'editPassword'])->name('profile.password');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.update-password');

    // Ressources RH
    Route::prefix('rh')->name('rh.')->group(function () {
        // Agents
        Route::resource('agents', AgentController::class);

        // Documents
        Route::resource('documents', DocumentController::class);

        // Demandes
        Route::resource('requests', RequestController::class);

        // Pointages
        Route::resource('pointages', PointageController::class);

        // Signalements
        Route::resource('signalements', SignalementController::class);

        // Rôles et Permissions
        Route::resource('roles', RoleController::class);
        Route::resource('permissions', PermissionController::class);

        // Provinces et Départements
        Route::resource('provinces', ProvinceController::class);
        Route::resource('departments', DepartmentController::class);
    });
});
