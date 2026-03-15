<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\RH\RequestController;
use App\Http\Controllers\RH\SignalementController;
use App\Http\Controllers\RH\AgentController;
use App\Http\Controllers\RH\PointageController;
use App\Http\Controllers\RH\CommuniqueController;
use App\Http\Controllers\TacheController;
use App\Http\Controllers\PlanTravailController;

// Page d'accueil
Route::get('/', function () {
    return redirect()->route('login');
});

// Routes d'authentification
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.store');
    Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->name('password.email');
    Route::get('/reset-password/{token}', [AuthController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');
});

Route::middleware('auth')->group(function () {
    // Profil agent
    Route::get('/dashboard', [ProfileController::class, 'index'])->name('dashboard');
    Route::get('/profile/{agent}', [ProfileController::class, 'show'])->name('profile.show');
    Route::get('/profile/{agent}/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile/{agent}', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Messages
    Route::get('/messages/{message}', [ProfileController::class, 'showMessage'])->name('messages.show');

    // Mes absences
    Route::get('/mes-absences', [ProfileController::class, 'mesAbsences'])->name('mes-absences');

    // Communiqués (vue agent)
    Route::get('/communiques/{communique}', [CommuniqueController::class, 'showPublic'])->name('communiques.show-public');

    // Documents (GED)
    Route::resource('documents', DocumentController::class);
    Route::post('documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');

    // Demandes
    Route::resource('requests', RequestController::class)->names('requests');
    Route::put('/requests/{request}/visa', [RequestController::class, 'visa'])->name('requests.visa');
    Route::put('/requests/{request}/approve', [RequestController::class, 'approve'])->name('requests.approve');
    Route::put('/requests/{request}/reject', [RequestController::class, 'reject'])->name('requests.reject');

    // Signalements
    Route::resource('signalements', SignalementController::class)->names('signalements');
    Route::post('signalements/{signalement}/resolve', [SignalementController::class, 'resolve'])->name('signalements.resolve');

    // Taches
    Route::get('/taches', [TacheController::class, 'index'])->name('taches.index');
    Route::get('/taches/create', [TacheController::class, 'create'])->name('taches.create');
    Route::post('/taches', [TacheController::class, 'store'])->name('taches.store');
    Route::get('/taches/{tache}', [TacheController::class, 'show'])->name('taches.show');
    Route::put('/taches/{tache}/statut', [TacheController::class, 'updateStatut'])->name('taches.update-statut');
    Route::post('/taches/{tache}/commentaire', [TacheController::class, 'addCommentaire'])->name('taches.commentaire');

    // Plan de Travail Annuel
    Route::get('/plan-travail', [PlanTravailController::class, 'index'])->name('plan-travail.index');
    Route::get('/plan-travail/create', [PlanTravailController::class, 'create'])->name('plan-travail.create');
    Route::post('/plan-travail', [PlanTravailController::class, 'store'])->name('plan-travail.store');
    Route::get('/plan-travail/{activitePlan}', [PlanTravailController::class, 'show'])->name('plan-travail.show');
    Route::get('/plan-travail/{activitePlan}/edit', [PlanTravailController::class, 'edit'])->name('plan-travail.edit');
    Route::put('/plan-travail/{activitePlan}', [PlanTravailController::class, 'update'])->name('plan-travail.update');
    Route::delete('/plan-travail/{activitePlan}', [PlanTravailController::class, 'destroy'])->name('plan-travail.destroy');
    Route::put('/plan-travail/{activitePlan}/statut', [PlanTravailController::class, 'updateStatut'])->name('plan-travail.update-statut');

    // RH JSON API for Modal (returns agent details as JSON)
    Route::middleware(['auth', 'role:Chef Section RH,RH National,RH Provincial'])->group(function () {
        Route::get('api/agents/{agent}', [AgentController::class, 'apiShow'])->name('api.agents.show');
    });

    // Routes admin/RH
    Route::middleware(['auth', 'role:Chef Section RH,RH National,RH Provincial'])->prefix('rh')->name('rh.')->group(function () {
        // Gestion des agents
        Route::resource('agents', AgentController::class);
        Route::post('agents/{agent}/generate-matricule', [AgentController::class, 'generateMatricule'])->name('agents.generate-matricule');
        Route::post('agents/{agent}/messages', [AgentController::class, 'storeMessage'])->name('agents.messages.store');
        Route::get('agents/{agent}/print', [AgentController::class, 'printFiche'])->name('agents.print');

        // Communiqués
        Route::resource('communiques', CommuniqueController::class);

        // Pointages
        Route::resource('pointages', PointageController::class);
        Route::get('pointages/daily/view', [PointageController::class, 'daily'])->name('pointages.daily');
        Route::get('pointages/daily/export', [PointageController::class, 'exportDailyExcel'])->name('pointages.daily-export');
        Route::get('pointages/monthly/report', [PointageController::class, 'monthlyReport'])->name('pointages.monthly-report');
        Route::get('pointages/monthly/export', [PointageController::class, 'exportMonthlyExcel'])->name('pointages.monthly-export');
        Route::post('pointages/import', [PointageController::class, 'import'])->name('pointages.import');

        // Tableau de bord RH
        Route::get('/dashboard', [AgentController::class, 'dashboard'])->name('dashboard');
    });
});
