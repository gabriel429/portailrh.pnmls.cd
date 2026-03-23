<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Province;
use App\Models\Grade;
use App\Models\Role;
use App\Models\User;
use App\Models\Department;
use App\Models\Section;
use App\Models\Cellule;
use App\Models\Fonction;
use App\Models\Affectation;
use App\Models\Localite;
use App\Models\Organe;
use App\Models\Permission;
use App\Models\DocumentTravail;
use App\Models\CategorieDocument;
use App\Services\NotificationService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class ParametresController extends Controller
{
    /**
     * Tableau de bord des paramètres.
     */
    public function dashboard()
    {
        // Get organes for filtering
        $organes = [];
        if (Schema::hasTable('organes')) {
            $organes = Organe::where('actif', true)->get();
        }

        // Build general stats
        $stats = [
            'provinces'   => Province::count(),
            'departments' => Department::count(),
            'sections'    => Section::count(),
            'cellules'    => Cellule::count(),
            'fonctions'   => Fonction::count(),
            'localites'   => Localite::count(),
            'grades'      => Grade::count(),
            'roles'       => Role::count(),
            'permissions' => Permission::count(),
            'organes'     => Schema::hasTable('organes') ? Organe::count() : 0,
            'agents'      => Agent::count(),
            'users'       => User::count(),
        ];

        // Build stats by organes
        $statsByOrgane = [];

        // If organes table exists, group by organe
        if (Schema::hasTable('organes') && count($organes) > 0) {
            foreach ($organes as $organe) {
                $organeCode = $organe->code; // SEN, SEP, SEL

                // Map organe code to niveau_administratif names/values
                $niveauMap = [
                    'SEN' => 'Secrétariat Exécutif National',
                    'SEP' => 'Secrétariat Exécutif Provincial',
                    'SEL' => 'Secrétariat Exécutif Local',
                ];

                $niveau = $niveauMap[$organeCode] ?? $organe->nom;

                $statsByOrgane[$organeCode] = [
                    'nom' => $organe->nom,
                    'sigle' => $organe->sigle,
                    'code' => $organeCode,
                    'icon' => match($organeCode) {
                        'SEN' => 'fa-flag',
                        'SEP' => 'fa-map-marked-alt',
                        'SEL' => 'fa-map-pin',
                        default => 'fa-sitemap'
                    },
                    'color' => match($organeCode) {
                        'SEN' => '#0077B5',
                        'SEP' => '#0ea5e9',
                        'SEL' => '#0d9488',
                        default => '#6b7280'
                    },
                    'bg-color' => match($organeCode) {
                        'SEN' => '#eff6ff',
                        'SEP' => '#e0f2fe',
                        'SEL' => '#ccfbf1',
                        default => '#f3f4f6'
                    },
                    'agents' => Agent::where('organe', $niveau)->count(),
                    'affectations' => Affectation::whereHas('fonction', function($q) use ($organeCode) {
                        $q->where('niveau_administratif', $organeCode)->orWhere('niveau_administratif', 'TOUS');
                    })->count(),
                    'fonctions' => Fonction::where('niveau_administratif', $organeCode)
                        ->orWhere('niveau_administratif', 'TOUS')
                        ->count(),
                ];
            }
        }

        // Connected users (active sessions in last 30 minutes)
        $connectedUsers = collect();
        if (Schema::hasTable('sessions')) {
            try {
                $threshold = now()->subMinutes(30)->timestamp;
                $sessions = DB::table('sessions')
                    ->whereNotNull('user_id')
                    ->where('last_activity', '>=', $threshold)
                    ->orderByDesc('last_activity')
                    ->get();

                $userIds = $sessions->pluck('user_id')->unique();
                $users = User::with(['agent.province', 'role'])
                    ->whereIn('id', $userIds)
                    ->get()
                    ->keyBy('id');

                $connectedUsers = $sessions->map(function ($session) use ($users) {
                    $user = $users->get($session->user_id);
                    if (!$user || !$user->agent) return null;

                    return (object) [
                        'user' => $user,
                        'agent' => $user->agent,
                        'nom_complet' => trim(($user->agent->prenom ?? '') . ' ' . ($user->agent->nom ?? $user->name)),
                        'province' => $user->agent->province?->nom ?? 'Non définie',
                        'role' => $user->role?->nom_role ?? 'Agent',
                        'last_activity' => \Carbon\Carbon::createFromTimestamp($session->last_activity),
                        'ip_address' => $session->ip_address,
                    ];
                })->filter()->unique(fn($item) => $item->user->id);
            } catch (\Exception $e) {
                // Silently fail if sessions table has different structure
            }
        }

        return view('admin.dashboard', compact('stats', 'statsByOrgane', 'organes', 'connectedUsers'));
    }

    /**
     * API: Tableau de bord des paramètres (JSON).
     */
    public function apiDashboard()
    {
        $organes = [];
        if (Schema::hasTable('organes')) {
            $organes = Organe::where('actif', true)->get();
        }

        $stats = [
            'provinces'   => Province::count(),
            'departments' => Department::count(),
            'sections'    => Section::count(),
            'cellules'    => Cellule::count(),
            'fonctions'   => Fonction::count(),
            'localites'   => Localite::count(),
            'grades'      => Grade::count(),
            'roles'       => Role::count(),
            'permissions' => Permission::count(),
            'organes'     => Schema::hasTable('organes') ? Organe::count() : 0,
            'agents'      => Agent::count(),
            'users'       => User::count(),
        ];

        $statsByOrgane = [];
        if (Schema::hasTable('organes') && count($organes) > 0) {
            $niveauMap = [
                'SEN' => 'Secrétariat Exécutif National',
                'SEP' => 'Secrétariat Exécutif Provincial',
                'SEL' => 'Secrétariat Exécutif Local',
            ];
            foreach ($organes as $organe) {
                $organeCode = $organe->code;
                $niveau = $niveauMap[$organeCode] ?? $organe->nom;
                $statsByOrgane[] = [
                    'nom' => $organe->nom,
                    'sigle' => $organe->sigle,
                    'code' => $organeCode,
                    'icon' => match($organeCode) {
                        'SEN' => 'fa-flag',
                        'SEP' => 'fa-map-marked-alt',
                        'SEL' => 'fa-map-pin',
                        default => 'fa-sitemap'
                    },
                    'color' => match($organeCode) {
                        'SEN' => '#0077B5',
                        'SEP' => '#0ea5e9',
                        'SEL' => '#0d9488',
                        default => '#6b7280'
                    },
                    'bgColor' => match($organeCode) {
                        'SEN' => '#eff6ff',
                        'SEP' => '#e0f2fe',
                        'SEL' => '#ccfbf1',
                        default => '#f3f4f6'
                    },
                    'agents' => Agent::where('organe', $niveau)->count(),
                    'affectations' => Affectation::whereHas('fonction', function($q) use ($organeCode) {
                        $q->where('niveau_administratif', $organeCode)->orWhere('niveau_administratif', 'TOUS');
                    })->count(),
                    'fonctions' => Fonction::where('niveau_administratif', $organeCode)
                        ->orWhere('niveau_administratif', 'TOUS')
                        ->count(),
                ];
            }
        }

        $connectedUsers = [];
        if (Schema::hasTable('sessions')) {
            try {
                $threshold = now()->subMinutes(30)->timestamp;
                $sessions = DB::table('sessions')
                    ->whereNotNull('user_id')
                    ->where('last_activity', '>=', $threshold)
                    ->orderByDesc('last_activity')
                    ->get();

                $userIds = $sessions->pluck('user_id')->unique();
                $users = User::with(['agent.province', 'role'])
                    ->whereIn('id', $userIds)
                    ->get()
                    ->keyBy('id');

                $connectedUsers = $sessions->map(function ($session) use ($users) {
                    $user = $users->get($session->user_id);
                    if (!$user || !$user->agent) return null;
                    return [
                        'id' => $user->id,
                        'nom_complet' => trim(($user->agent->prenom ?? '') . ' ' . ($user->agent->nom ?? $user->name)),
                        'province' => $user->agent->province?->nom ?? 'Non definie',
                        'role' => $user->role?->nom_role ?? 'Agent',
                        'last_activity' => \Carbon\Carbon::createFromTimestamp($session->last_activity)->toIso8601String(),
                        'ip_address' => $session->ip_address,
                    ];
                })->filter()->unique('id')->values();
            } catch (\Exception $e) {
                // Silently fail
            }
        }

        return response()->json([
            'stats' => $stats,
            'statsByOrgane' => $statsByOrgane,
            'connectedUsers' => $connectedUsers,
        ]);
    }

    // ─── PROVINCES ───────────────────────────────────────────────

    public function provincesIndex()
    {
        $provinces = Province::withCount(['agents', 'departments'])->orderBy('nom')->paginate(20);
        return view('admin.provinces.index', compact('provinces'));
    }

    public function provincesCreate()
    {
        return view('admin.provinces.create');
    }

    public function provincesStore(Request $request)
    {
        $validated = $request->validate([
            'code'                     => 'required|string|max:10|unique:provinces',
            'nom'                      => 'required|string|max:255|unique:provinces',
            'description'              => 'nullable|string',
            'ville_secretariat'        => 'nullable|string|max:255',
            'adresse'                  => 'nullable|string|max:500',
            'nom_gouverneur'           => 'nullable|string|max:255',
            'nom_secretariat_executif' => 'nullable|string|max:255',
            'email_officiel'           => 'nullable|email|max:255',
            'telephone_officiel'       => 'nullable|string|max:50',
        ]);

        Province::create($validated);

        return redirect()->route('admin.provinces.index')
            ->with('success', 'Province créée avec succès.');
    }

    public function provincesEdit(Province $province)
    {
        return view('admin.provinces.edit', compact('province'));
    }

    public function provincesUpdate(Request $request, Province $province)
    {
        $validated = $request->validate([
            'code'                     => 'required|string|max:10|unique:provinces,code,' . $province->id,
            'nom'                      => 'required|string|max:255|unique:provinces,nom,' . $province->id,
            'description'              => 'nullable|string',
            'ville_secretariat'        => 'nullable|string|max:255',
            'adresse'                  => 'nullable|string|max:500',
            'nom_gouverneur'           => 'nullable|string|max:255',
            'nom_secretariat_executif' => 'nullable|string|max:255',
            'email_officiel'           => 'nullable|email|max:255',
            'telephone_officiel'       => 'nullable|string|max:50',
        ]);

        $province->update($validated);

        return redirect()->route('admin.provinces.index')
            ->with('success', 'Province mise à jour avec succès.');
    }

    public function provincesDestroy(Province $province)
    {
        $province->delete();
        return redirect()->route('admin.provinces.index')
            ->with('success', 'Province supprimée.');
    }

    // ─── GRADES ──────────────────────────────────────────────────

    public function gradesIndex()
    {
        $grades = Grade::orderBy('ordre')->get()->groupBy('categorie');
        return view('admin.grades.index', compact('grades'));
    }

    public function gradesCreate()
    {
        return view('admin.grades.create');
    }

    public function gradesStore(Request $request)
    {
        $validated = $request->validate([
            'categorie'     => 'required|in:A,B,C',
            'nom_categorie' => 'required|string|max:255',
            'ordre'         => 'required|integer|min:1|unique:grades,ordre',
            'libelle'       => 'required|string|max:255',
        ]);

        Grade::create($validated);

        return redirect()->route('admin.grades.index')
            ->with('success', 'Grade créé avec succès.');
    }

    public function gradesEdit(Grade $grade)
    {
        return view('admin.grades.edit', compact('grade'));
    }

    public function gradesUpdate(Request $request, Grade $grade)
    {
        $validated = $request->validate([
            'categorie'     => 'required|in:A,B,C',
            'nom_categorie' => 'required|string|max:255',
            'ordre'         => 'required|integer|min:1|unique:grades,ordre,' . $grade->id,
            'libelle'       => 'required|string|max:255',
        ]);

        $grade->update($validated);

        return redirect()->route('admin.grades.index')
            ->with('success', 'Grade mis à jour.');
    }

    public function gradesDestroy(Grade $grade)
    {
        $grade->delete();
        return redirect()->route('admin.grades.index')
            ->with('success', 'Grade supprimé.');
    }

    // ─── RÔLES ───────────────────────────────────────────────────

    public function rolesIndex()
    {
        $roles = Role::withCount('agents')->orderBy('nom_role')->paginate(20);
        return view('admin.roles.index', compact('roles'));
    }

    public function rolesCreate()
    {
        return view('admin.roles.create');
    }

    public function rolesStore(Request $request)
    {
        $validated = $request->validate([
            'nom_role'    => 'required|string|max:255|unique:roles',
            'description' => 'nullable|string',
        ]);

        Role::create($validated);

        return redirect()->route('admin.roles.index')
            ->with('success', 'Rôle créé avec succès.');
    }

    public function rolesEdit(Role $role)
    {
        return view('admin.roles.edit', compact('role'));
    }

    public function rolesUpdate(Request $request, Role $role)
    {
        $validated = $request->validate([
            'nom_role'    => 'required|string|max:255|unique:roles,nom_role,' . $role->id,
            'description' => 'nullable|string',
        ]);

        $role->update($validated);

        return redirect()->route('admin.roles.index')
            ->with('success', 'Rôle mis à jour.');
    }

    public function rolesDestroy(Role $role)
    {
        if ($role->agents()->count() > 0) {
            return redirect()->route('admin.roles.index')
                ->with('error', 'Impossible de supprimer ce rôle : des agents y sont encore affectés.');
        }

        $role->delete();
        return redirect()->route('admin.roles.index')
            ->with('success', 'Rôle supprimé.');
    }

    // ─── DÉPARTEMENTS ─────────────────────────────────────────────

    public function departmentsIndex()
    {
        $departments = Department::with('province')->withCount(['agents', 'sections'])->orderBy('nom')->paginate(20);
        return view('admin.departments.index', compact('departments'));
    }

    public function departmentsCreate()
    {
        $provinces = Province::orderBy('nom')->get();
        return view('admin.departments.create', compact('provinces'));
    }

    public function departmentsStore(Request $request)
    {
        $validated = $request->validate([
            'code'        => 'required|string|max:10|unique:departments',
            'nom'         => 'required|string|max:255',
            'description' => 'nullable|string',
            'province_id' => 'nullable|exists:provinces,id',
        ]);

        Department::create($validated);

        return redirect()->route('admin.departments.index')
            ->with('success', 'Département créé avec succès.');
    }

    public function departmentsEdit(Department $department)
    {
        $provinces = Province::orderBy('nom')->get();
        return view('admin.departments.edit', compact('department', 'provinces'));
    }

    public function departmentsUpdate(Request $request, Department $department)
    {
        $validated = $request->validate([
            'code'        => 'required|string|max:10|unique:departments,code,' . $department->id,
            'nom'         => 'required|string|max:255',
            'description' => 'nullable|string',
            'province_id' => 'nullable|exists:provinces,id',
        ]);

        $department->update($validated);

        return redirect()->route('admin.departments.index')
            ->with('success', 'Département mis à jour.');
    }

    public function departmentsDestroy(Department $department)
    {
        $department->delete();
        return redirect()->route('admin.departments.index')
            ->with('success', 'Département supprimé.');
    }

    // ─── FONCTIONS ────────────────────────────────────────────────

    public function fonctionsIndex()
    {
        $fonctions = Fonction::orderBy('niveau_administratif')
            ->orderBy('type_poste')
            ->orderBy('nom')
            ->paginate(30);
        return view('admin.fonctions.index', compact('fonctions'));
    }

    public function fonctionsCreate()
    {
        return view('admin.fonctions.create');
    }

    public function fonctionsStore(Request $request)
    {
        $validated = $request->validate([
            'nom'                  => 'required|string|max:255|unique:fonctions',
            'niveau_administratif' => 'required|in:SEN,SEP,SEL,TOUS',
            'type_poste'           => 'required|in:direction,service_rattache,département,section,cellule,appui,province,local',
            'est_chef'             => 'boolean',
            'description'          => 'nullable|string',
        ]);
        $validated['est_chef'] = $request->boolean('est_chef');

        Fonction::create($validated);

        return redirect()->route('admin.fonctions.index')
            ->with('success', 'Fonction créée avec succès.');
    }

    public function fonctionsEdit(Fonction $fonction)
    {
        return view('admin.fonctions.edit', compact('fonction'));
    }

    public function fonctionsUpdate(Request $request, Fonction $fonction)
    {
        $validated = $request->validate([
            'nom'                  => 'required|string|max:255|unique:fonctions,nom,' . $fonction->id,
            'niveau_administratif' => 'required|in:SEN,SEP,SEL,TOUS',
            'type_poste'           => 'required|in:direction,service_rattache,département,section,cellule,appui,province,local',
            'est_chef'             => 'boolean',
            'description'          => 'nullable|string',
        ]);
        $validated['est_chef'] = $request->boolean('est_chef');

        $fonction->update($validated);

        return redirect()->route('admin.fonctions.index')
            ->with('success', 'Fonction mise à jour.');
    }

    public function fonctionsDestroy(Fonction $fonction)
    {
        if ($fonction->affectations()->count() > 0) {
            return redirect()->route('admin.fonctions.index')
                ->with('error', 'Impossible de supprimer : des agents ont cette fonction.');
        }
        $fonction->delete();
        return redirect()->route('admin.fonctions.index')
            ->with('success', 'Fonction supprimée.');
    }

    // ─── SECTIONS ─────────────────────────────────────────────────

    public function sectionsIndex()
    {
        $sections = Section::with('department')
            ->withCount('cellules')
            ->orderBy('type')
            ->orderBy('department_id')
            ->orderBy('nom')
            ->paginate(25);
        return view('admin.sections.index', compact('sections'));
    }

    public function sectionsCreate()
    {
        $departments = Department::orderBy('nom')->get();
        return view('admin.sections.create', compact('departments'));
    }

    public function sectionsStore(Request $request)
    {
        $type = $request->input('type', 'section');
        $validated = $request->validate([
            'code'          => 'required|string|max:20|unique:sections',
            'nom'           => 'required|string|max:255',
            'description'   => 'nullable|string',
            'type'          => 'required|in:section,service_rattache',
            'department_id' => $type === 'section' ? 'required|exists:departments,id' : 'nullable|exists:departments,id',
        ]);

        Section::create($validated);

        return redirect()->route('admin.sections.index')
            ->with('success', 'Section créée avec succès.');
    }

    public function sectionsEdit(Section $section)
    {
        $departments = Department::orderBy('nom')->get();
        return view('admin.sections.edit', compact('section', 'departments'));
    }

    public function sectionsUpdate(Request $request, Section $section)
    {
        $type = $request->input('type', 'section');
        $validated = $request->validate([
            'code'          => 'required|string|max:20|unique:sections,code,' . $section->id,
            'nom'           => 'required|string|max:255',
            'description'   => 'nullable|string',
            'type'          => 'required|in:section,service_rattache',
            'department_id' => $type === 'section' ? 'required|exists:departments,id' : 'nullable|exists:departments,id',
        ]);

        $section->update($validated);

        return redirect()->route('admin.sections.index')
            ->with('success', 'Section mise à jour.');
    }

    public function sectionsDestroy(Section $section)
    {
        $section->delete();
        return redirect()->route('admin.sections.index')
            ->with('success', 'Section supprimée.');
    }

    // ─── CELLULES ─────────────────────────────────────────────────

    public function cellulesIndex()
    {
        $cellules = Cellule::with(['section.department'])->orderBy('section_id')->orderBy('nom')->paginate(25);
        return view('admin.cellules.index', compact('cellules'));
    }

    public function cellulesCreate()
    {
        $sections = Section::with('department')->orderBy('nom')->get();
        return view('admin.cellules.create', compact('sections'));
    }

    public function cellulesStore(Request $request)
    {
        $validated = $request->validate([
            'code'       => 'required|string|max:20|unique:cellules',
            'nom'        => 'required|string|max:255',
            'description'=> 'nullable|string',
            'section_id' => 'required|exists:sections,id',
        ]);

        Cellule::create($validated);

        return redirect()->route('admin.cellules.index')
            ->with('success', 'Cellule créée avec succès.');
    }

    public function cellulesEdit(Cellule $cellule)
    {
        $sections = Section::with('department')->orderBy('nom')->get();
        return view('admin.cellules.edit', compact('cellule', 'sections'));
    }

    public function cellulesUpdate(Request $request, Cellule $cellule)
    {
        $validated = $request->validate([
            'code'       => 'required|string|max:20|unique:cellules,code,' . $cellule->id,
            'nom'        => 'required|string|max:255',
            'description'=> 'nullable|string',
            'section_id' => 'required|exists:sections,id',
        ]);

        $cellule->update($validated);

        return redirect()->route('admin.cellules.index')
            ->with('success', 'Cellule mise à jour.');
    }

    public function cellulesDestroy(Cellule $cellule)
    {
        $cellule->delete();
        return redirect()->route('admin.cellules.index')
            ->with('success', 'Cellule supprimée.');
    }

    // ─── AFFECTATIONS ─────────────────────────────────────────────

    public function affectationsIndex()
    {
        if (!Schema::hasTable('affectations')) {
            return redirect()->route('dashboard')
                ->with('error', 'Le module Affectations n\'est pas encore déployé. Rendez-vous dans Admin > Déploiement pour créer les tables nécessaires.');
        }

        try {
            $affectations = Affectation::with([
                'agent', 'fonction', 'department', 'section', 'cellule', 'province', 'localite',
            ])
            ->orderBy('niveau_administratif')
            ->orderBy('niveau')
            ->paginate(25);
        } catch (\Exception $e) {
            $affectations = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 25);
        }
        return view('rh.affectations.index', compact('affectations'));
    }

    private function affectationsFormData(): array
    {
        return [
            'agents'      => Agent::orderBy('nom')->get(),
            'fonctions'   => Schema::hasTable('fonctions') ? Fonction::orderBy('niveau_administratif')->orderBy('type_poste')->orderBy('nom')->get() : collect(),
            'departments' => Department::orderBy('nom')->get(),
            'sections'    => Schema::hasTable('sections') ? Section::with('department')->orderBy('type')->orderBy('nom')->get() : collect(),
            'cellules'    => Schema::hasTable('cellules') ? Cellule::with('section')->orderBy('nom')->get() : collect(),
            'provinces'   => Province::orderBy('nom')->get(),
            'localites'   => Schema::hasTable('localites') ? Localite::with('province')->orderBy('nom')->get() : collect(),
        ];
    }

    public function affectationsCreate(Request $request)
    {
        if (!Schema::hasTable('affectations') || !Schema::hasTable('fonctions')) {
            return redirect()->route('dashboard')
                ->with('error', 'Le module Affectations n\'est pas encore déployé. Rendez-vous dans Admin > Déploiement.');
        }
        $data = $this->affectationsFormData();
        $data['preselectedAgentId'] = $request->query('agent_id');
        return view('rh.affectations.create', $data);
    }

    public function affectationsStore(Request $request)
    {
        $niveauAdmin = $request->input('niveau_administratif');
        $niveau      = $request->input('niveau');

        $validated = $request->validate([
            'agent_id'             => 'required|exists:agents,id',
            'fonction_id'          => 'required|exists:fonctions,id',
            'niveau_administratif' => 'required|in:SEN,SEP,SEL',
            'niveau'               => 'required|in:direction,service_rattache,departement,département,section,cellule,province,local',
            'department_id'        => 'nullable|exists:departments,id',
            'section_id'           => 'nullable|exists:sections,id',
            'cellule_id'           => 'nullable|exists:cellules,id',
            'province_id'          => 'nullable|exists:provinces,id',
            'localite_id'          => 'nullable|exists:localites,id',
            'date_debut'           => 'nullable|date',
            'date_fin'             => 'nullable|date|after_or_equal:date_debut',
            'remarque'             => 'nullable|string',
        ]);
        $validated['actif'] = true;

        // Effacer les FKs non pertinentes selon le niveau
        if ($niveauAdmin === 'SEP') {
            $validated['department_id'] = null;
            $validated['section_id']    = null;
            $validated['cellule_id']    = null;
            $validated['localite_id']   = null;
        } elseif ($niveauAdmin === 'SEL') {
            $validated['department_id'] = null;
            $validated['section_id']    = null;
            $validated['cellule_id']    = null;
            $validated['province_id']   = null;
        } else { // SEN
            $validated['province_id']   = null;
            $validated['localite_id']   = null;
            $validated['cellule_id']    = null;
            // Département: pas de section ; Service rattaché: pas de département
            if ($niveau === 'departement') {
                $validated['section_id'] = null;
            } elseif ($niveau === 'service_rattache') {
                $validated['department_id'] = null;
            }
        }

        // Unicité chef actif par entité
        $fonction = Fonction::find($validated['fonction_id']);
        if ($fonction?->est_chef) {
            $exists = Affectation::where('actif', true)
                ->where('niveau_administratif', $niveauAdmin)
                ->where('niveau', $niveau)
                ->whereHas('fonction', fn($q) => $q->where('est_chef', true))
                ->when($niveau === 'département',      fn($q) => $q->where('department_id', $validated['department_id']))
                ->when($niveau === 'section',          fn($q) => $q->where('section_id', $validated['section_id']))
                ->when($niveau === 'cellule',          fn($q) => $q->where('cellule_id', $validated['cellule_id']))
                ->when($niveau === 'province',         fn($q) => $q->where('province_id', $validated['province_id']))
                ->when($niveau === 'local',            fn($q) => $q->where('localite_id', $validated['localite_id']))
                ->when($niveau === 'direction',        fn($q) => $q->where('niveau_administratif', 'SEN'))
                ->when($niveau === 'service_rattache', fn($q) => $q->where('section_id', $validated['section_id']))
                ->exists();

            if ($exists) {
                return back()->withInput()
                    ->with('error', 'Un responsable est déjà affecté à cette entité. Désactivez d\'abord l\'affectation existante.');
            }
        }

        try {
            Affectation::create($validated);
        } catch (\Exception $e) {
            return back()->withInput()
                ->with('error', 'Erreur lors de la création : ' . $e->getMessage());
        }

        return redirect()->route('rh.affectations.index')
            ->with('success', 'Affectation créée avec succès.');
    }

    public function affectationsEdit(Affectation $affectation)
    {
        return view('rh.affectations.edit', array_merge(
            $this->affectationsFormData(),
            ['affectation' => $affectation]
        ));
    }

    public function affectationsUpdate(Request $request, Affectation $affectation)
    {
        $validated = $request->validate([
            'agent_id'             => 'required|exists:agents,id',
            'fonction_id'          => 'required|exists:fonctions,id',
            'niveau_administratif' => 'required|in:SEN,SEP,SEL',
            'niveau'               => 'required|in:direction,service_rattache,departement,département,section,cellule,province,local',
            'department_id'        => 'nullable|exists:departments,id',
            'section_id'           => 'nullable|exists:sections,id',
            'cellule_id'           => 'nullable|exists:cellules,id',
            'province_id'          => 'nullable|exists:provinces,id',
            'localite_id'          => 'nullable|exists:localites,id',
            'date_debut'           => 'nullable|date',
            'date_fin'             => 'nullable|date|after_or_equal:date_debut',
            'actif'                => 'boolean',
            'remarque'             => 'nullable|string',
        ]);
        $validated['actif'] = $request->boolean('actif');

        $affectation->update($validated);

        return redirect()->route('rh.affectations.index')
            ->with('success', 'Affectation mise à jour.');
    }

    public function affectationsDestroy(Affectation $affectation)
    {
        $affectation->delete();
        return redirect()->route('rh.affectations.index')
            ->with('success', 'Affectation supprimée.');
    }

    // ─── LOCALITÉS (SEL) ──────────────────────────────────────────

    public function localitesIndex()
    {
        $localites = Localite::with('province')
            ->withCount('affectations')
            ->orderBy('province_id')
            ->orderBy('nom')
            ->paginate(25);
        return view('admin.localites.index', compact('localites'));
    }

    public function localitesCreate()
    {
        $provinces = Province::orderBy('nom')->get();
        return view('admin.localites.create', compact('provinces'));
    }

    public function localitesStore(Request $request)
    {
        $validated = $request->validate([
            'code'        => 'required|string|max:30|unique:localites',
            'nom'         => 'required|string|max:255',
            'type'        => 'required|in:territoire,zone_de_sante,commune,ville,autre',
            'description' => 'nullable|string',
            'province_id' => 'required|exists:provinces,id',
        ]);

        Localite::create($validated);

        return redirect()->route('admin.localites.index')
            ->with('success', 'Localité créée avec succès.');
    }

    public function localitesEdit(Localite $localite)
    {
        $provinces = Province::orderBy('nom')->get();
        return view('admin.localites.edit', compact('localite', 'provinces'));
    }

    public function localitesUpdate(Request $request, Localite $localite)
    {
        $validated = $request->validate([
            'code'        => 'required|string|max:30|unique:localites,code,' . $localite->id,
            'nom'         => 'required|string|max:255',
            'type'        => 'required|in:territoire,zone_de_sante,commune,ville,autre',
            'description' => 'nullable|string',
            'province_id' => 'required|exists:provinces,id',
        ]);

        $localite->update($validated);

        return redirect()->route('admin.localites.index')
            ->with('success', 'Localité mise à jour.');
    }

    public function localitesDestroy(Localite $localite)
    {
        $localite->delete();
        return redirect()->route('admin.localites.index')
            ->with('success', 'Localité supprimée.');
    }

    // ─── ORGANES ──────────────────────────────────────────────

    public function organesIndex()
    {
        if (!Schema::hasTable('organes')) {
            return redirect()->route('admin.dashboard')
                ->with('error', 'La table organes n\'existe pas encore. Veuillez exécuter les migrations avec: php deploy-organes.php');
        }

        $organes = Organe::orderBy('code')->paginate(20);
        return view('admin.organes.index', compact('organes'));
    }

    public function organesCreate()
    {
        if (!Schema::hasTable('organes')) {
            return redirect()->route('admin.dashboard')
                ->with('error', 'La table organes n\'existe pas encore. Veuillez exécuter les migrations avec: php deploy-organes.php');
        }

        return view('admin.organes.create');
    }

    public function organesStore(Request $request)
    {
        if (!Schema::hasTable('organes')) {
            return redirect()->route('admin.dashboard')
                ->with('error', 'La table organes n\'existe pas encore. Veuillez exécuter les migrations avec: php deploy-organes.php');
        }

        $validated = $request->validate([
            'code'        => 'required|string|max:10|unique:organes',
            'nom'         => 'required|string|max:255|unique:organes',
            'sigle'       => 'nullable|string|max:30',
            'description' => 'nullable|string',
            'actif'       => 'boolean',
        ]);
        $validated['actif'] = $request->boolean('actif');

        Organe::create($validated);

        return redirect()->route('admin.organes.index')
            ->with('success', 'Organe créé avec succès.');
    }

    public function organesEdit(Organe $organe)
    {
        return view('admin.organes.edit', compact('organe'));
    }

    public function organesUpdate(Request $request, Organe $organe)
    {
        $validated = $request->validate([
            'code'        => 'required|string|max:10|unique:organes,code,' . $organe->id,
            'nom'         => 'required|string|max:255|unique:organes,nom,' . $organe->id,
            'sigle'       => 'nullable|string|max:30',
            'description' => 'nullable|string',
            'actif'       => 'boolean',
        ]);
        $validated['actif'] = $request->boolean('actif');

        $organe->update($validated);

        return redirect()->route('admin.organes.index')
            ->with('success', 'Organe mis à jour.');
    }

    public function organesDestroy(Organe $organe)
    {
        $organe->delete();
        return redirect()->route('admin.organes.index')
            ->with('success', 'Organe supprimé.');
    }

    /**
     * API: Get fonctions filtered by organe code
     */
    public function getAllFonctionsByOrgane($organeCode)
    {
        if (!Schema::hasTable('organes')) {
            return response()->json(['error' => 'Migration non exécutée'], 503);
        }

        $fonctions = Fonction::where(function ($query) use ($organeCode) {
            $query->where('niveau_administratif', $organeCode)
                  ->orWhere('niveau_administratif', 'TOUS');
        })
        ->orderBy('type_poste')
        ->orderBy('nom')
        ->get();

        return response()->json($fonctions);
    }

    // ─── UTILISATEURS ────────────────────────────────────────────────────

    /**
     * Display list of users (linked to agents)
     */
    public function utilisateursIndex()
    {
        $users = User::with(['agent', 'role'])->paginate(15);
        return view('admin.utilisateurs.index', compact('users'));
    }

    /**
     * Show form to create new user
     */
    public function utilisateursCreate()
    {
        $agents = Agent::whereDoesntHave('user')->orderBy('nom')->get();
        $roles = Role::all();
        return view('admin.utilisateurs.create', compact('agents', 'roles'));
    }

    /**
     * Store a new user linked to an agent
     */
    public function utilisateursStore(Request $request)
    {
        $validated = $request->validate([
            'agent_id' => 'required|exists:agents,id|unique:users,agent_id',
            'role_id' => 'nullable|exists:roles,id',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $agent = Agent::findOrFail($validated['agent_id']);

        // Use email_professionnel if available, otherwise generate one
        $email = $agent->email_professionnel ?? $agent->email;
        if (!$email) {
            // Fallback: generate email from name
            $email = strtolower(str_replace(' ', '.', $agent->nom . '.' . $agent->prenom)) . '@portail-rh.local';
        }

        User::create([
            'name' => $agent->nom . ' ' . $agent->prenom,
            'email' => $email,
            'agent_id' => $validated['agent_id'],
            'role_id' => $validated['role_id'],
            'password' => bcrypt($validated['password']),
        ]);

        return redirect()->route('admin.utilisateurs.index')
            ->with('success', 'Utilisateur créé avec succès pour ' . $agent->nom);
    }

    /**
     * Show form to edit user
     */
    public function utilisateursEdit(User $user)
    {
        $user->load(['agent', 'role']);
        $roles = Role::all();
        return view('admin.utilisateurs.edit', compact('user', 'roles'));
    }

    /**
     * Update user (role and password)
     */
    public function utilisateursUpdate(Request $request, User $user)
    {
        $validated = $request->validate([
            'role_id' => 'nullable|exists:roles,id',
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        $user->update([
            'role_id' => $validated['role_id'],
        ]);

        // Update password if provided
        if (!empty($validated['password'])) {
            $user->update(['password' => bcrypt($validated['password'])]);
        }

        return redirect()->route('admin.utilisateurs.index')
            ->with('success', 'Utilisateur modifié avec succès');
    }

    /**
     * Delete user
     */
    public function utilisateursDestroy(User $user)
    {
        // Prevent deleting the current user
        if ($user->id === auth()->id()) {
            return redirect()->route('admin.utilisateurs.index')
                ->with('error', 'Vous ne pouvez pas supprimer votre propre compte');
        }

        $user->delete();

        return redirect()->route('admin.utilisateurs.index')
            ->with('success', 'Utilisateur supprimé avec succès');
    }

    // ─── LOGS ────────────────────────────────────────────────────

    public function logs(Request $request)
    {
        $logPath = storage_path('logs/laravel.log');
        $lines   = [];
        $error   = null;

        if (File::exists($logPath)) {
            $content = File::get($logPath);
            // Garder les 300 dernières lignes
            $all   = array_reverse(explode("\n", $content));
            $lines = array_slice($all, 0, 300);
        } else {
            $error = 'Aucun fichier de log trouvé.';
        }

        return view('admin.logs', compact('lines', 'error'));
    }

    public function logsClear()
    {
        $logPath = storage_path('logs/laravel.log');
        if (File::exists($logPath)) {
            File::put($logPath, '');
        }

        return redirect()->route('admin.logs')
            ->with('success', 'Logs effacés avec succès.');
    }

    // ─── Documents de travail ────────────────────────────────────────────────────

    public function docsTravailIndex()
    {
        $documents = DocumentTravail::with('uploader')->latest()->paginate(20);
        return view('admin.documents-travail.index', compact('documents'));
    }

    public function docsTravailCreate()
    {
        $categories = CategorieDocument::actives()->orderBy('nom')->pluck('nom');
        return view('admin.documents-travail.form', compact('categories'));
    }

    public function docsTravailStore(Request $request)
    {
        $validated = $request->validate([
            'titre'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'categorie'   => 'required|string|max:100',
            'fichier'     => 'required|file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png|max:10240',
            'actif'       => 'nullable|boolean',
        ]);

        $file = $request->file('fichier');
        $validated['fichier']       = $file->store('documents_travail', 'public');
        $validated['type_fichier']  = $file->getClientOriginalExtension();
        $validated['taille']        = $file->getSize();
        $validated['uploaded_by']   = auth()->id();
        $validated['actif']         = $request->has('actif');

        $doc = DocumentTravail::create($validated);

        // Notifier tous les utilisateurs du nouveau document
        if ($doc->actif) {
            NotificationService::notifierTous(
                'document_travail',
                'Nouveau document : ' . $doc->titre,
                'Un nouveau document de travail a été publié dans la catégorie "' . $doc->categorie . '".',
                '/documents-travail?categorie=' . urlencode($doc->categorie),
                auth()->id()
            );
        }

        return redirect()->route('admin.documents-travail.index')
            ->with('success', 'Document de travail ajouté avec succès.');
    }

    public function docsTravailEdit(DocumentTravail $documentTravail)
    {
        $categories = CategorieDocument::actives()->orderBy('nom')->pluck('nom');
        return view('admin.documents-travail.form', ['document' => $documentTravail, 'categories' => $categories]);
    }

    public function docsTravailUpdate(Request $request, DocumentTravail $documentTravail)
    {
        $validated = $request->validate([
            'titre'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'categorie'   => 'required|string|max:100',
            'fichier'     => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png|max:10240',
            'actif'       => 'nullable|boolean',
        ]);

        if ($request->hasFile('fichier')) {
            $file = $request->file('fichier');
            $validated['fichier']      = $file->store('documents_travail', 'public');
            $validated['type_fichier'] = $file->getClientOriginalExtension();
            $validated['taille']       = $file->getSize();
        }

        $validated['actif'] = $request->has('actif');

        $documentTravail->update($validated);

        return redirect()->route('admin.documents-travail.index')
            ->with('success', 'Document mis à jour avec succès.');
    }

    public function docsTravailDestroy(DocumentTravail $documentTravail)
    {
        $documentTravail->delete();

        return redirect()->route('admin.documents-travail.index')
            ->with('success', 'Document supprimé avec succès.');
    }

    // ─── Catégories Documents ────────────────────────────────────────────────────

    public function categoriesDocsIndex()
    {
        $categories = CategorieDocument::orderBy('nom')->get();
        return view('admin.categories-documents.index', compact('categories'));
    }

    public function categoriesDocsCreate()
    {
        return view('admin.categories-documents.form');
    }

    public function categoriesDocsStore(Request $request)
    {
        $validated = $request->validate([
            'nom'   => 'required|string|max:100|unique:categorie_documents,nom',
            'icone' => 'nullable|string|max:50',
            'actif' => 'nullable|boolean',
        ]);
        $validated['actif'] = $request->has('actif');

        CategorieDocument::create($validated);

        return redirect()->route('admin.categories-documents.index')
            ->with('success', 'Catégorie ajoutée avec succès.');
    }

    public function categoriesDocsEdit(CategorieDocument $categorieDocument)
    {
        return view('admin.categories-documents.form', ['categorie' => $categorieDocument]);
    }

    public function categoriesDocsUpdate(Request $request, CategorieDocument $categorieDocument)
    {
        $validated = $request->validate([
            'nom'   => 'required|string|max:100|unique:categorie_documents,nom,' . $categorieDocument->id,
            'icone' => 'nullable|string|max:50',
            'actif' => 'nullable|boolean',
        ]);
        $validated['actif'] = $request->has('actif');

        $categorieDocument->update($validated);

        return redirect()->route('admin.categories-documents.index')
            ->with('success', 'Catégorie mise à jour.');
    }

    public function categoriesDocsDestroy(CategorieDocument $categorieDocument)
    {
        $categorieDocument->delete();

        return redirect()->route('admin.categories-documents.index')
            ->with('success', 'Catégorie supprimée.');
    }

    // ─── API METHODS (JSON) ─────────────────────────────────────

    public function apiProvincesIndex(Request $request)
    {
        $q = Province::withCount(['agents', 'departments'])->orderBy('nom');
        if ($request->search) {
            $q->where('nom', 'like', "%{$request->search}%");
        }
        return response()->json($q->paginate($request->per_page ?? 20));
    }

    public function apiProvincesShow(Province $province)
    {
        $province->loadCount(['agents', 'departments']);
        return response()->json($province);
    }

    public function apiProvincesStore(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:10|unique:provinces',
            'nom' => 'required|string|max:255|unique:provinces',
            'description' => 'nullable|string',
            'ville_secretariat' => 'nullable|string|max:255',
            'adresse' => 'nullable|string|max:500',
            'nom_gouverneur' => 'nullable|string|max:255',
            'nom_secretariat_executif' => 'nullable|string|max:255',
            'email_officiel' => 'nullable|email|max:255',
            'telephone_officiel' => 'nullable|string|max:50',
        ]);
        $province = Province::create($validated);
        return response()->json($province, 201);
    }

    public function apiProvincesUpdate(Request $request, Province $province)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:10|unique:provinces,code,' . $province->id,
            'nom' => 'required|string|max:255|unique:provinces,nom,' . $province->id,
            'description' => 'nullable|string',
            'ville_secretariat' => 'nullable|string|max:255',
            'adresse' => 'nullable|string|max:500',
            'nom_gouverneur' => 'nullable|string|max:255',
            'nom_secretariat_executif' => 'nullable|string|max:255',
            'email_officiel' => 'nullable|email|max:255',
            'telephone_officiel' => 'nullable|string|max:50',
        ]);
        $province->update($validated);
        return response()->json($province);
    }

    public function apiProvincesDestroy(Province $province)
    {
        $province->delete();
        return response()->json(['message' => 'Province supprimee.']);
    }

    public function apiGradesIndex()
    {
        $grades = Grade::orderBy('ordre')->get();
        return response()->json(['data' => $grades, 'grouped' => $grades->groupBy('categorie')]);
    }

    public function apiGradesStore(Request $request)
    {
        $validated = $request->validate([
            'categorie' => 'required|in:A,B,C',
            'nom_categorie' => 'required|string|max:255',
            'ordre' => 'required|integer',
            'libelle' => 'required|string|max:255',
        ]);
        $grade = Grade::create($validated);
        return response()->json($grade, 201);
    }

    public function apiGradesUpdate(Request $request, Grade $grade)
    {
        $validated = $request->validate([
            'categorie' => 'required|in:A,B,C',
            'nom_categorie' => 'required|string|max:255',
            'ordre' => 'required|integer',
            'libelle' => 'required|string|max:255',
        ]);
        $grade->update($validated);
        return response()->json($grade);
    }

    public function apiGradesDestroy(Grade $grade)
    {
        $grade->delete();
        return response()->json(['message' => 'Grade supprime.']);
    }

    public function apiRolesIndex(Request $request)
    {
        $q = Role::withCount('agents')->orderBy('nom_role');
        if ($request->search) {
            $q->where('nom_role', 'like', "%{$request->search}%");
        }
        return response()->json($q->paginate($request->per_page ?? 20));
    }

    public function apiRolesShow(Role $role)
    {
        return response()->json($role);
    }

    public function apiRolesStore(Request $request)
    {
        $validated = $request->validate([
            'nom_role' => 'required|string|max:255|unique:roles',
            'description' => 'nullable|string',
        ]);
        $role = Role::create($validated);
        return response()->json($role, 201);
    }

    public function apiRolesUpdate(Request $request, Role $role)
    {
        $validated = $request->validate([
            'nom_role' => 'required|string|max:255|unique:roles,nom_role,' . $role->id,
            'description' => 'nullable|string',
        ]);
        $role->update($validated);
        return response()->json($role);
    }

    public function apiRolesDestroy(Role $role)
    {
        if ($role->agents()->count() > 0) {
            return response()->json(['message' => 'Impossible de supprimer: des agents utilisent ce role.'], 422);
        }
        $role->delete();
        return response()->json(['message' => 'Role supprime.']);
    }

    public function apiDepartmentsIndex(Request $request)
    {
        $q = Department::withCount(['agents', 'sections'])->orderBy('nom');
        if ($request->search) {
            $q->where('nom', 'like', "%{$request->search}%");
        }
        return response()->json($q->paginate($request->per_page ?? 20));
    }

    public function apiDepartmentsShow(Department $department)
    {
        return response()->json($department);
    }

    public function apiDepartmentsStore(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:10|unique:departments',
            'nom' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);
        $dept = Department::create($validated);
        return response()->json($dept, 201);
    }

    public function apiDepartmentsUpdate(Request $request, Department $department)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:10|unique:departments,code,' . $department->id,
            'nom' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);
        $department->update($validated);
        return response()->json($department);
    }

    public function apiDepartmentsDestroy(Department $department)
    {
        $department->delete();
        return response()->json(['message' => 'Departement supprime.']);
    }

    public function apiFonctionsIndex(Request $request)
    {
        $q = Fonction::orderBy('niveau_administratif')->orderBy('type_poste')->orderBy('nom');
        if ($request->search) {
            $q->where('nom', 'like', "%{$request->search}%");
        }
        if ($request->niveau) {
            $q->where('niveau_administratif', $request->niveau);
        }
        return response()->json($q->paginate($request->per_page ?? 30));
    }

    public function apiFonctionsStore(Request $request)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'niveau_administratif' => 'required|in:SEN,SEP,SEL,TOUS',
            'type_poste' => 'nullable|string|max:100',
            'est_chef' => 'boolean',
            'description' => 'nullable|string',
        ]);
        $fonction = Fonction::create($validated);
        return response()->json($fonction, 201);
    }

    public function apiFonctionsUpdate(Request $request, Fonction $fonction)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'niveau_administratif' => 'required|in:SEN,SEP,SEL,TOUS',
            'type_poste' => 'nullable|string|max:100',
            'est_chef' => 'boolean',
            'description' => 'nullable|string',
        ]);
        $fonction->update($validated);
        return response()->json($fonction);
    }

    public function apiFonctionsDestroy(Fonction $fonction)
    {
        if (Schema::hasTable('affectations') && Affectation::where('fonction_id', $fonction->id)->exists()) {
            return response()->json(['message' => 'Impossible: des affectations utilisent cette fonction.'], 422);
        }
        $fonction->delete();
        return response()->json(['message' => 'Fonction supprimee.']);
    }

    public function apiSectionsIndex(Request $request)
    {
        $q = Section::with('department')->withCount('cellules')->orderBy('nom');
        if ($request->search) {
            $q->where('nom', 'like', "%{$request->search}%");
        }
        return response()->json($q->paginate($request->per_page ?? 25));
    }

    public function apiSectionsStore(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:10|unique:sections',
            'nom' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:section,service_rattache',
            'department_id' => 'nullable|exists:departments,id',
        ]);
        $section = Section::create($validated);
        return response()->json($section->load('department'), 201);
    }

    public function apiSectionsUpdate(Request $request, Section $section)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:10|unique:sections,code,' . $section->id,
            'nom' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:section,service_rattache',
            'department_id' => 'nullable|exists:departments,id',
        ]);
        $section->update($validated);
        return response()->json($section->load('department'));
    }

    public function apiSectionsDestroy(Section $section)
    {
        $section->delete();
        return response()->json(['message' => 'Section supprimee.']);
    }

    public function apiCellulesIndex(Request $request)
    {
        $q = Cellule::with('section.department')->orderBy('nom');
        if ($request->search) {
            $q->where('nom', 'like', "%{$request->search}%");
        }
        return response()->json($q->paginate($request->per_page ?? 25));
    }

    public function apiCellulesStore(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:10|unique:cellules',
            'nom' => 'required|string|max:255',
            'description' => 'nullable|string',
            'section_id' => 'required|exists:sections,id',
        ]);
        $cellule = Cellule::create($validated);
        return response()->json($cellule->load('section'), 201);
    }

    public function apiCellulesUpdate(Request $request, Cellule $cellule)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:10|unique:cellules,code,' . $cellule->id,
            'nom' => 'required|string|max:255',
            'description' => 'nullable|string',
            'section_id' => 'required|exists:sections,id',
        ]);
        $cellule->update($validated);
        return response()->json($cellule->load('section'));
    }

    public function apiCellulesDestroy(Cellule $cellule)
    {
        $cellule->delete();
        return response()->json(['message' => 'Cellule supprimee.']);
    }

    public function apiLocalitesIndex(Request $request)
    {
        $q = Localite::with('province')->withCount('affectations')->orderBy('nom');
        if ($request->search) {
            $q->where('nom', 'like', "%{$request->search}%");
        }
        return response()->json($q->paginate($request->per_page ?? 25));
    }

    public function apiLocalitesStore(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:10|unique:localites',
            'nom' => 'required|string|max:255',
            'type' => 'required|in:territoire,zone_de_sante,commune,ville,autre',
            'description' => 'nullable|string',
            'province_id' => 'required|exists:provinces,id',
        ]);
        $localite = Localite::create($validated);
        return response()->json($localite->load('province'), 201);
    }

    public function apiLocalitesUpdate(Request $request, Localite $localite)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:10|unique:localites,code,' . $localite->id,
            'nom' => 'required|string|max:255',
            'type' => 'required|in:territoire,zone_de_sante,commune,ville,autre',
            'description' => 'nullable|string',
            'province_id' => 'required|exists:provinces,id',
        ]);
        $localite->update($validated);
        return response()->json($localite->load('province'));
    }

    public function apiLocalitesDestroy(Localite $localite)
    {
        $localite->delete();
        return response()->json(['message' => 'Localite supprimee.']);
    }

    public function apiOrganesIndex()
    {
        if (!Schema::hasTable('organes')) {
            return response()->json(['data' => []]);
        }
        $organes = Organe::orderBy('code')->get();
        return response()->json(['data' => $organes]);
    }

    public function apiOrganesShow(Organe $organe)
    {
        return response()->json($organe);
    }

    public function apiOrganesStore(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:10|unique:organes',
            'nom' => 'required|string|max:255',
            'sigle' => 'required|string|max:20',
            'description' => 'nullable|string',
            'actif' => 'boolean',
        ]);
        $organe = Organe::create($validated);
        return response()->json($organe, 201);
    }

    public function apiOrganesUpdate(Request $request, Organe $organe)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:10|unique:organes,code,' . $organe->id,
            'nom' => 'required|string|max:255',
            'sigle' => 'required|string|max:20',
            'description' => 'nullable|string',
            'actif' => 'boolean',
        ]);
        $organe->update($validated);
        return response()->json($organe);
    }

    public function apiOrganesDestroy(Organe $organe)
    {
        $organe->delete();
        return response()->json(['message' => 'Organe supprime.']);
    }

    public function apiUtilisateursIndex(Request $request)
    {
        $q = User::with(['agent', 'role'])->orderByDesc('id');
        if ($request->search) {
            $q->where('name', 'like', "%{$request->search}%")
              ->orWhere('email', 'like', "%{$request->search}%");
        }
        return response()->json($q->paginate($request->per_page ?? 15));
    }

    public function apiUtilisateursFormData()
    {
        $agentsWithoutUser = Agent::whereDoesntHave('user')->orderBy('nom')->get(['id', 'nom', 'prenom']);
        $roles = Role::orderBy('nom_role')->get(['id', 'nom_role']);
        return response()->json(['agents' => $agentsWithoutUser, 'roles' => $roles]);
    }

    public function apiUtilisateursShow(User $user)
    {
        return response()->json($user->load(['agent', 'role']));
    }

    public function apiUtilisateursStore(Request $request)
    {
        $validated = $request->validate([
            'agent_id' => 'required|exists:agents,id|unique:users,agent_id',
            'role_id' => 'required|exists:roles,id',
            'password' => 'required|string|min:6',
        ]);
        $agent = Agent::findOrFail($validated['agent_id']);
        $email = $agent->email ?: strtolower(str_replace(' ', '.', trim($agent->prenom ?? 'agent') . '.' . trim($agent->nom ?? $agent->id))) . '@pnmls.cd';
        $user = User::create([
            'name' => trim(($agent->prenom ?? '') . ' ' . ($agent->nom ?? '')),
            'email' => $email,
            'password' => bcrypt($validated['password']),
            'agent_id' => $agent->id,
            'role_id' => $validated['role_id'],
        ]);
        return response()->json($user->load(['agent', 'role']), 201);
    }

    public function apiUtilisateursUpdate(Request $request, User $user)
    {
        $validated = $request->validate([
            'role_id' => 'required|exists:roles,id',
            'password' => 'nullable|string|min:6',
        ]);
        $user->role_id = $validated['role_id'];
        if (!empty($validated['password'])) {
            $user->password = bcrypt($validated['password']);
        }
        $user->save();
        return response()->json($user->load(['agent', 'role']));
    }

    public function apiUtilisateursDestroy(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            return response()->json(['message' => 'Vous ne pouvez pas supprimer votre propre compte.'], 422);
        }
        $user->delete();
        return response()->json(['message' => 'Utilisateur supprime.']);
    }

    public function apiDocsTravailIndex(Request $request)
    {
        $q = DocumentTravail::with('uploader')->orderByDesc('created_at');
        if ($request->search) {
            $q->where('titre', 'like', "%{$request->search}%");
        }
        return response()->json($q->paginate($request->per_page ?? 20));
    }

    public function apiDocsTravailShow(DocumentTravail $documentTravail)
    {
        return response()->json($documentTravail->load('uploader'));
    }

    public function apiDocsTravailStore(Request $request)
    {
        $validated = $request->validate([
            'titre' => 'required|string|max:255',
            'description' => 'nullable|string',
            'categorie' => 'nullable|string|max:100',
            'fichier' => 'required|file|max:10240|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png',
            'actif' => 'boolean',
        ]);
        $path = $request->file('fichier')->store('documents-travail', 'public');
        $doc = DocumentTravail::create([
            'titre' => $validated['titre'],
            'description' => $validated['description'] ?? null,
            'categorie' => $validated['categorie'] ?? null,
            'fichier' => $path,
            'actif' => $validated['actif'] ?? true,
            'uploaded_by' => $request->user()->id,
        ]);
        return response()->json($doc, 201);
    }

    public function apiDocsTravailUpdate(Request $request, DocumentTravail $documentTravail)
    {
        $validated = $request->validate([
            'titre' => 'required|string|max:255',
            'description' => 'nullable|string',
            'categorie' => 'nullable|string|max:100',
            'fichier' => 'nullable|file|max:10240|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png',
            'actif' => 'boolean',
        ]);
        $data = collect($validated)->except('fichier')->toArray();
        if ($request->hasFile('fichier')) {
            $data['fichier'] = $request->file('fichier')->store('documents-travail', 'public');
        }
        $documentTravail->update($data);
        return response()->json($documentTravail);
    }

    public function apiDocsTravailDestroy(DocumentTravail $documentTravail)
    {
        $documentTravail->delete();
        return response()->json(['message' => 'Document supprime.']);
    }

    public function apiCategoriesDocsIndex()
    {
        $categories = CategorieDocument::orderBy('nom')->get();
        return response()->json(['data' => $categories]);
    }

    public function apiCategoriesDocsShow(CategorieDocument $categorieDocument)
    {
        return response()->json($categorieDocument);
    }

    public function apiCategoriesDocsStore(Request $request)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255|unique:categorie_documents',
            'icone' => 'nullable|string|max:50',
            'actif' => 'boolean',
        ]);
        $cat = CategorieDocument::create($validated);
        return response()->json($cat, 201);
    }

    public function apiCategoriesDocsUpdate(Request $request, CategorieDocument $categorieDocument)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255|unique:categorie_documents,nom,' . $categorieDocument->id,
            'icone' => 'nullable|string|max:50',
            'actif' => 'boolean',
        ]);
        $categorieDocument->update($validated);
        return response()->json($categorieDocument);
    }

    public function apiCategoriesDocsDestroy(CategorieDocument $categorieDocument)
    {
        $categorieDocument->delete();
        return response()->json(['message' => 'Categorie supprimee.']);
    }

    public function apiLogs()
    {
        $logFile = storage_path('logs/laravel.log');
        $lines = [];
        if (file_exists($logFile)) {
            $content = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $lines = array_slice($content, -300);
        }
        return response()->json(['lines' => $lines, 'file' => $logFile]);
    }

    public function apiLogsClear()
    {
        $logFile = storage_path('logs/laravel.log');
        if (file_exists($logFile)) {
            file_put_contents($logFile, '');
        }
        return response()->json(['message' => 'Logs effaces.']);
    }

    public function apiDeploymentIndex()
    {
        return response()->json(['message' => 'Deployment panel ready.']);
    }

    public function apiAffectationsIndex(Request $request)
    {
        if (!Schema::hasTable('affectations')) {
            return response()->json(['data' => [], 'total' => 0]);
        }
        $q = Affectation::with(['agent', 'fonction', 'department', 'section', 'cellule', 'province', 'localite'])
            ->orderByDesc('created_at');
        if ($request->search) {
            $q->whereHas('agent', function($aq) use ($request) {
                $aq->where('nom', 'like', "%{$request->search}%")
                   ->orWhere('prenom', 'like', "%{$request->search}%");
            });
        }
        return response()->json($q->paginate($request->per_page ?? 25));
    }

    public function apiAffectationsFormData()
    {
        return response()->json([
            'agents' => Agent::orderBy('nom')->get(['id', 'nom', 'prenom']),
            'fonctions' => Fonction::orderBy('nom')->get(['id', 'nom', 'niveau_administratif', 'type_poste']),
            'departments' => Department::orderBy('nom')->get(['id', 'nom', 'code']),
            'sections' => Section::orderBy('nom')->get(['id', 'nom', 'code', 'department_id']),
            'cellules' => Cellule::orderBy('nom')->get(['id', 'nom', 'code', 'section_id']),
            'provinces' => Province::orderBy('nom')->get(['id', 'nom', 'code']),
            'localites' => Localite::orderBy('nom')->get(['id', 'nom', 'code', 'province_id']),
        ]);
    }

    public function apiAffectationsStore(Request $request)
    {
        $validated = $request->validate([
            'agent_id' => 'required|exists:agents,id',
            'fonction_id' => 'required|exists:fonctions,id',
            'niveau_administratif' => 'required|in:SEN,SEP,SEL',
            'niveau' => 'required|in:direction,service_rattache,departement,section,cellule,province,local',
            'department_id' => 'nullable|exists:departments,id',
            'section_id' => 'nullable|exists:sections,id',
            'cellule_id' => 'nullable|exists:cellules,id',
            'province_id' => 'nullable|exists:provinces,id',
            'localite_id' => 'nullable|exists:localites,id',
            'date_debut' => 'nullable|date',
            'date_fin' => 'nullable|date|after_or_equal:date_debut',
            'actif' => 'boolean',
            'remarque' => 'nullable|string',
        ]);
        $affectation = Affectation::create($validated);
        return response()->json($affectation->load(['agent', 'fonction']), 201);
    }

    public function apiAffectationsUpdate(Request $request, Affectation $affectation)
    {
        $validated = $request->validate([
            'agent_id' => 'required|exists:agents,id',
            'fonction_id' => 'required|exists:fonctions,id',
            'niveau_administratif' => 'required|in:SEN,SEP,SEL',
            'niveau' => 'required|in:direction,service_rattache,departement,section,cellule,province,local',
            'department_id' => 'nullable|exists:departments,id',
            'section_id' => 'nullable|exists:sections,id',
            'cellule_id' => 'nullable|exists:cellules,id',
            'province_id' => 'nullable|exists:provinces,id',
            'localite_id' => 'nullable|exists:localites,id',
            'date_debut' => 'nullable|date',
            'date_fin' => 'nullable|date|after_or_equal:date_debut',
            'actif' => 'boolean',
            'remarque' => 'nullable|string',
        ]);
        $affectation->update($validated);
        return response()->json($affectation->load(['agent', 'fonction']));
    }

    public function apiAffectationsDestroy(Affectation $affectation)
    {
        $affectation->delete();
        return response()->json(['message' => 'Affectation supprimee.']);
    }
}
