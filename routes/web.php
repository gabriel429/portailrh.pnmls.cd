<?php

// ──────────────────────────────────────────────────────────────
// web.php — Web routes for SPA + Blade admin pages
// ──────────────────────────────────────────────────────────────

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\ParametresController;
use App\Http\Controllers\Admin\DeploymentController;
use App\Http\Controllers\DocumentTravailController;

// Named login route — required by Laravel auth middleware for redirect
Route::get('/login', function () {
    return view('spa');
})->name('login');

// Named dashboard route — points to SPA
Route::get('/dashboard', function () {
    return view('spa');
})->name('dashboard');

// Logout (POST) — destroy session and redirect to login
Route::post('/logout', function (\Illuminate\Http\Request $request) {
    \Illuminate\Support\Facades\Auth::guard('web')->logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect('/login');
})->middleware('auth')->name('logout');

// Document de travail download
Route::get('/documents-travail/{documentTravail}/download', [DocumentTravailController::class, 'download'])
    ->middleware('auth')
    ->name('documents-travail.download');

// ── Admin Blade Pages (NT = Nouvelles Technologies) ─────────
Route::middleware(['auth', 'admin.nt'])->prefix('admin')->name('admin.')->group(function () {

    // Dashboard
    Route::get('/dashboard', [ParametresController::class, 'dashboard'])->name('dashboard');

    // Organes
    Route::get('/organes', [ParametresController::class, 'organesIndex'])->name('organes.index');
    Route::get('/organes/create', [ParametresController::class, 'organesCreate'])->name('organes.create');
    Route::post('/organes', [ParametresController::class, 'organesStore'])->name('organes.store');
    Route::get('/organes/{organe}/edit', [ParametresController::class, 'organesEdit'])->name('organes.edit');
    Route::put('/organes/{organe}', [ParametresController::class, 'organesUpdate'])->name('organes.update');
    Route::delete('/organes/{organe}', [ParametresController::class, 'organesDestroy'])->name('organes.destroy');

    // Departments
    Route::get('/departments', [ParametresController::class, 'departmentsIndex'])->name('departments.index');
    Route::get('/departments/create', [ParametresController::class, 'departmentsCreate'])->name('departments.create');
    Route::post('/departments', [ParametresController::class, 'departmentsStore'])->name('departments.store');
    Route::get('/departments/{department}/edit', [ParametresController::class, 'departmentsEdit'])->name('departments.edit');
    Route::put('/departments/{department}', [ParametresController::class, 'departmentsUpdate'])->name('departments.update');
    Route::delete('/departments/{department}', [ParametresController::class, 'departmentsDestroy'])->name('departments.destroy');

    // Sections
    Route::get('/sections', [ParametresController::class, 'sectionsIndex'])->name('sections.index');
    Route::get('/sections/create', [ParametresController::class, 'sectionsCreate'])->name('sections.create');
    Route::post('/sections', [ParametresController::class, 'sectionsStore'])->name('sections.store');
    Route::get('/sections/{section}/edit', [ParametresController::class, 'sectionsEdit'])->name('sections.edit');
    Route::put('/sections/{section}', [ParametresController::class, 'sectionsUpdate'])->name('sections.update');
    Route::delete('/sections/{section}', [ParametresController::class, 'sectionsDestroy'])->name('sections.destroy');

    // Cellules
    Route::get('/cellules', [ParametresController::class, 'cellulesIndex'])->name('cellules.index');
    Route::get('/cellules/create', [ParametresController::class, 'cellulesCreate'])->name('cellules.create');
    Route::post('/cellules', [ParametresController::class, 'cellulesStore'])->name('cellules.store');
    Route::get('/cellules/{cellule}/edit', [ParametresController::class, 'cellulesEdit'])->name('cellules.edit');
    Route::put('/cellules/{cellule}', [ParametresController::class, 'cellulesUpdate'])->name('cellules.update');
    Route::delete('/cellules/{cellule}', [ParametresController::class, 'cellulesDestroy'])->name('cellules.destroy');

    // Provinces
    Route::get('/provinces', [ParametresController::class, 'provincesIndex'])->name('provinces.index');
    Route::get('/provinces/create', [ParametresController::class, 'provincesCreate'])->name('provinces.create');
    Route::post('/provinces', [ParametresController::class, 'provincesStore'])->name('provinces.store');
    Route::get('/provinces/{province}/edit', [ParametresController::class, 'provincesEdit'])->name('provinces.edit');
    Route::put('/provinces/{province}', [ParametresController::class, 'provincesUpdate'])->name('provinces.update');
    Route::delete('/provinces/{province}', [ParametresController::class, 'provincesDestroy'])->name('provinces.destroy');

    // Localités
    Route::get('/localites', [ParametresController::class, 'localitesIndex'])->name('localites.index');
    Route::get('/localites/create', [ParametresController::class, 'localitesCreate'])->name('localites.create');
    Route::post('/localites', [ParametresController::class, 'localitesStore'])->name('localites.store');
    Route::get('/localites/{localite}/edit', [ParametresController::class, 'localitesEdit'])->name('localites.edit');
    Route::put('/localites/{localite}', [ParametresController::class, 'localitesUpdate'])->name('localites.update');
    Route::delete('/localites/{localite}', [ParametresController::class, 'localitesDestroy'])->name('localites.destroy');

    // Fonctions
    Route::get('/fonctions', [ParametresController::class, 'fonctionsIndex'])->name('fonctions.index');
    Route::get('/fonctions/create', [ParametresController::class, 'fonctionsCreate'])->name('fonctions.create');
    Route::post('/fonctions', [ParametresController::class, 'fonctionsStore'])->name('fonctions.store');
    Route::get('/fonctions/{fonction}/edit', [ParametresController::class, 'fonctionsEdit'])->name('fonctions.edit');
    Route::put('/fonctions/{fonction}', [ParametresController::class, 'fonctionsUpdate'])->name('fonctions.update');
    Route::delete('/fonctions/{fonction}', [ParametresController::class, 'fonctionsDestroy'])->name('fonctions.destroy');

    // Grades
    Route::get('/grades', [ParametresController::class, 'gradesIndex'])->name('grades.index');
    Route::get('/grades/create', [ParametresController::class, 'gradesCreate'])->name('grades.create');
    Route::post('/grades', [ParametresController::class, 'gradesStore'])->name('grades.store');
    Route::get('/grades/{grade}/edit', [ParametresController::class, 'gradesEdit'])->name('grades.edit');
    Route::put('/grades/{grade}', [ParametresController::class, 'gradesUpdate'])->name('grades.update');
    Route::delete('/grades/{grade}', [ParametresController::class, 'gradesDestroy'])->name('grades.destroy');

    // Rôles
    Route::get('/roles', [ParametresController::class, 'rolesIndex'])->name('roles.index');
    Route::get('/roles/create', [ParametresController::class, 'rolesCreate'])->name('roles.create');
    Route::post('/roles', [ParametresController::class, 'rolesStore'])->name('roles.store');
    Route::get('/roles/{role}/edit', [ParametresController::class, 'rolesEdit'])->name('roles.edit');
    Route::put('/roles/{role}', [ParametresController::class, 'rolesUpdate'])->name('roles.update');
    Route::delete('/roles/{role}', [ParametresController::class, 'rolesDestroy'])->name('roles.destroy');

    // Documents de travail
    Route::get('/documents-travail', [ParametresController::class, 'docsTravailIndex'])->name('documents-travail.index');
    Route::get('/documents-travail/create', [ParametresController::class, 'docsTravailCreate'])->name('documents-travail.create');
    Route::post('/documents-travail', [ParametresController::class, 'docsTravailStore'])->name('documents-travail.store');
    Route::get('/documents-travail/{documentTravail}/edit', [ParametresController::class, 'docsTravailEdit'])->name('documents-travail.edit');
    Route::put('/documents-travail/{documentTravail}', [ParametresController::class, 'docsTravailUpdate'])->name('documents-travail.update');
    Route::delete('/documents-travail/{documentTravail}', [ParametresController::class, 'docsTravailDestroy'])->name('documents-travail.destroy');

    // Catégories de documents
    Route::get('/categories-documents', [ParametresController::class, 'categoriesDocsIndex'])->name('categories-documents.index');
    Route::get('/categories-documents/create', [ParametresController::class, 'categoriesDocsCreate'])->name('categories-documents.create');
    Route::post('/categories-documents', [ParametresController::class, 'categoriesDocsStore'])->name('categories-documents.store');
    Route::get('/categories-documents/{categorieDocument}/edit', [ParametresController::class, 'categoriesDocsEdit'])->name('categories-documents.edit');
    Route::put('/categories-documents/{categorieDocument}', [ParametresController::class, 'categoriesDocsUpdate'])->name('categories-documents.update');
    Route::delete('/categories-documents/{categorieDocument}', [ParametresController::class, 'categoriesDocsDestroy'])->name('categories-documents.destroy');

    // Utilisateurs
    Route::get('/utilisateurs', [ParametresController::class, 'utilisateursIndex'])->name('utilisateurs.index');
    Route::get('/utilisateurs/create', [ParametresController::class, 'utilisateursCreate'])->name('utilisateurs.create');
    Route::post('/utilisateurs', [ParametresController::class, 'utilisateursStore'])->name('utilisateurs.store');
    Route::get('/utilisateurs/{user}/edit', [ParametresController::class, 'utilisateursEdit'])->name('utilisateurs.edit');
    Route::put('/utilisateurs/{user}', [ParametresController::class, 'utilisateursUpdate'])->name('utilisateurs.update');
    Route::delete('/utilisateurs/{user}', [ParametresController::class, 'utilisateursDestroy'])->name('utilisateurs.destroy');

    // Logs
    Route::get('/logs', [ParametresController::class, 'logs'])->name('logs');
    Route::post('/logs/clear', [ParametresController::class, 'logsClear'])->name('logs.clear');

    // Deployment (Blade page for server operations)
    Route::get('/deployment', [DeploymentController::class, 'index'])->name('deployment.index');
    Route::post('/deployment/git-pull', [DeploymentController::class, 'gitPull'])->name('deployment.git-pull');
    Route::post('/deployment/migrate', [DeploymentController::class, 'migrate'])->name('deployment.migrate');
    Route::post('/deployment/migrate-fresh', [DeploymentController::class, 'migrateFresh'])->name('deployment.migrate-fresh');
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
    Route::post('/deployment/deploy-grades', [DeploymentController::class, 'deployGrades'])->name('deployment.deploy-grades');
    Route::post('/deployment/deploy-agents', [DeploymentController::class, 'deployAgents'])->name('deployment.deploy-agents');
});
