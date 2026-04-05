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
use App\Models\AuditLog;
use App\Services\NotificationService;
use App\Services\UserDataScope;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class ParametresController extends Controller
{
    private function scopeService(): UserDataScope
    {
        return app(UserDataScope::class);
    }

    /**
     * Parse user-agent to extract device type and model.
     */
    private function parseDevice(string $ua): array
    {
        $type = 'Ordinateur';
        $model = '';

        if (preg_match('/iPhone/i', $ua)) {
            $type = 'Telephone';
            if (preg_match('/iPhone OS ([\d_]+)/i', $ua, $m)) {
                $model = 'iPhone (iOS ' . str_replace('_', '.', $m[1]) . ')';
            } else {
                $model = 'iPhone';
            }
        } elseif (preg_match('/iPad/i', $ua)) {
            $type = 'Tablette';
            $model = 'iPad';
        } elseif (preg_match('/Android/i', $ua)) {
            if (preg_match('/Mobile/i', $ua)) {
                $type = 'Telephone';
            } else {
                $type = 'Tablette';
            }
            if (preg_match('/;\s*([^;)]+)\s+Build/i', $ua, $m)) {
                $model = trim($m[1]);
            } elseif (preg_match('/Android\s[\d.]+;\s*([^;)]+)/i', $ua, $m)) {
                $model = trim($m[1]);
            } else {
                $model = 'Android';
            }
        } else {
            // Desktop browser detection
            if (preg_match('/Edg\/([\d.]+)/i', $ua)) {
                $model = 'Microsoft Edge';
            } elseif (preg_match('/Chrome\/([\d.]+)/i', $ua)) {
                $model = 'Google Chrome';
            } elseif (preg_match('/Firefox\/([\d.]+)/i', $ua)) {
                $model = 'Mozilla Firefox';
            } elseif (preg_match('/Safari\/([\d.]+)/i', $ua) && !preg_match('/Chrome/i', $ua)) {
                $model = 'Safari';
            } else {
                $model = 'Navigateur';
            }

            if (preg_match('/Windows NT/i', $ua)) {
                $model .= ' (Windows)';
            } elseif (preg_match('/Macintosh/i', $ua)) {
                $model .= ' (Mac)';
            } elseif (preg_match('/Linux/i', $ua)) {
                $model .= ' (Linux)';
            }
        }

        return ['type' => $type, 'model' => $model];
    }

    /**
     * Record an audit log entry.
     */
    private function recordAudit(string $action, string $tableName, $recordId, ?array $before = null, ?array $after = null): void
    {
        $user = request()->user();
        if (!$user) return;

        // Exclude sensitive fields from audit data
        $sensitive = ['password', 'remember_token'];
        if ($before) {
            $before = array_diff_key($before, array_flip($sensitive));
        }
        if ($after) {
            $after = array_diff_key($after, array_flip($sensitive));
        }

        try {
            AuditLog::create([
                'user_id'       => $user->id,
                'user_name'     => $user->name,
                'table_name'    => $tableName,
                'record_id'     => $recordId,
                'action'        => $action,
                'donnees_avant' => $before,
                'donnees_apres' => $after,
                'ip_address'    => request()->ip(),
                'user_agent'    => request()->userAgent(),
            ]);
        } catch (\Throwable $e) {
            \Log::warning('Audit log failed: ' . $e->getMessage());
        }
    }

    // ─── API METHODS (JSON) ─────────────────────────────────────

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
            'users'       => User::where('is_super_admin', false)->count(),
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
                    if (!$user || !$user->agent || $user->is_super_admin) return null;

                    $ua = $session->user_agent ?? '';
                    $device = $this->parseDevice($ua);

                    return [
                        'id' => $user->id,
                        'nom_complet' => trim(($user->agent->prenom ?? '') . ' ' . ($user->agent->nom ?? $user->name)),
                        'province' => $user->agent->province?->nom ?? 'Non definie',
                        'role' => $user->role?->nom_role ?? 'Agent',
                        'last_activity' => \Carbon\Carbon::createFromTimestamp($session->last_activity)->toIso8601String(),
                        'ip_address' => $session->ip_address,
                        'device_type' => $device['type'],
                        'device_model' => $device['model'],
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

    // ─── PROVINCES ───────────────────────────────────────────────

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
        $this->recordAudit('CREATE', 'provinces', $province->id, null, $province->toArray());
        return response()->json($province, 201);
    }

    public function apiProvincesUpdate(Request $request, Province $province)
    {
        $before = $province->toArray();
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
        $this->recordAudit('UPDATE', 'provinces', $province->id, $before, $province->fresh()->toArray());
        return response()->json($province);
    }

    public function apiProvincesDestroy(Province $province)
    {
        if ($province->agents()->count() > 0) {
            return response()->json(['message' => 'Impossible de supprimer: des agents sont rattaches a cette province.'], 422);
        }
        $before = $province->toArray();
        $province->delete();
        $this->recordAudit('DELETE', 'provinces', $province->id, $before);
        return response()->json(['message' => 'Province supprimee.']);
    }

    // ─── GRADES ──────────────────────────────────────────────────

    public function apiGradesIndex()
    {
        $grades = Grade::orderBy('ordre')->get();
        return response()->json(['data' => $grades, 'grouped' => $grades->groupBy('categorie')]);
    }

    public function apiGradesShow(Grade $grade)
    {
        return response()->json($grade);
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
        $this->recordAudit('CREATE', 'grades', $grade->id, null, $grade->toArray());
        return response()->json($grade, 201);
    }

    public function apiGradesUpdate(Request $request, Grade $grade)
    {
        $before = $grade->toArray();
        $validated = $request->validate([
            'categorie' => 'required|in:A,B,C',
            'nom_categorie' => 'required|string|max:255',
            'ordre' => 'required|integer',
            'libelle' => 'required|string|max:255',
        ]);
        $grade->update($validated);
        $this->recordAudit('UPDATE', 'grades', $grade->id, $before, $grade->fresh()->toArray());
        return response()->json($grade);
    }

    public function apiGradesDestroy(Grade $grade)
    {
        if ($grade->agents()->count() > 0) {
            return response()->json(['message' => 'Impossible de supprimer: des agents utilisent ce grade.'], 422);
        }
        $before = $grade->toArray();
        $grade->delete();
        $this->recordAudit('DELETE', 'grades', $grade->id, $before);
        return response()->json(['message' => 'Grade supprime.']);
    }

    // ─── ROLES ───────────────────────────────────────────────────

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
        $this->recordAudit('CREATE', 'roles', $role->id, null, $role->toArray());
        return response()->json($role, 201);
    }

    public function apiRolesUpdate(Request $request, Role $role)
    {
        $before = $role->toArray();
        $validated = $request->validate([
            'nom_role' => 'required|string|max:255|unique:roles,nom_role,' . $role->id,
            'description' => 'nullable|string',
        ]);
        $role->update($validated);
        $this->recordAudit('UPDATE', 'roles', $role->id, $before, $role->fresh()->toArray());
        return response()->json($role);
    }

    public function apiRolesDestroy(Role $role)
    {
        if ($role->agents()->count() > 0) {
            return response()->json(['message' => 'Impossible de supprimer: des agents utilisent ce role.'], 422);
        }
        $before = $role->toArray();
        $role->delete();
        $this->recordAudit('DELETE', 'roles', $role->id, $before);
        return response()->json(['message' => 'Role supprime.']);
    }

    // ─── DEPARTMENTS ─────────────────────────────────────────────

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
        $this->recordAudit('CREATE', 'departments', $dept->id, null, $dept->toArray());
        return response()->json($dept, 201);
    }

    public function apiDepartmentsUpdate(Request $request, Department $department)
    {
        $before = $department->toArray();
        $validated = $request->validate([
            'code' => 'required|string|max:10|unique:departments,code,' . $department->id,
            'nom' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);
        $department->update($validated);
        $this->recordAudit('UPDATE', 'departments', $department->id, $before, $department->fresh()->toArray());
        return response()->json($department);
    }

    public function apiDepartmentsDestroy(Department $department)
    {
        if ($department->sections()->count() > 0) {
            return response()->json(['message' => 'Impossible de supprimer: des sections sont rattachees a ce departement.'], 422);
        }
        $before = $department->toArray();
        $department->delete();
        $this->recordAudit('DELETE', 'departments', $department->id, $before);
        return response()->json(['message' => 'Departement supprime.']);
    }

    // ─── FONCTIONS ───────────────────────────────────────────────

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

    public function apiFonctionsShow(Fonction $fonction)
    {
        return response()->json($fonction);
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
        $this->recordAudit('CREATE', 'fonctions', $fonction->id, null, $fonction->toArray());
        return response()->json($fonction, 201);
    }

    public function apiFonctionsUpdate(Request $request, Fonction $fonction)
    {
        $before = $fonction->toArray();
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'niveau_administratif' => 'required|in:SEN,SEP,SEL,TOUS',
            'type_poste' => 'nullable|string|max:100',
            'est_chef' => 'boolean',
            'description' => 'nullable|string',
        ]);
        $fonction->update($validated);
        $this->recordAudit('UPDATE', 'fonctions', $fonction->id, $before, $fonction->fresh()->toArray());
        return response()->json($fonction);
    }

    public function apiFonctionsDestroy(Fonction $fonction)
    {
        if (Schema::hasTable('affectations') && Affectation::where('fonction_id', $fonction->id)->exists()) {
            return response()->json(['message' => 'Impossible: des affectations utilisent cette fonction.'], 422);
        }
        $before = $fonction->toArray();
        $fonction->delete();
        $this->recordAudit('DELETE', 'fonctions', $fonction->id, $before);
        return response()->json(['message' => 'Fonction supprimee.']);
    }

    // ─── SECTIONS ────────────────────────────────────────────────

    public function apiSectionsIndex(Request $request)
    {
        $q = Section::with('department')->withCount('cellules')->orderBy('nom');
        if ($request->search) {
            $q->where('nom', 'like', "%{$request->search}%");
        }
        return response()->json($q->paginate($request->per_page ?? 25));
    }

    public function apiSectionsShow(Section $section)
    {
        return response()->json($section->load('department'));
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
        $this->recordAudit('CREATE', 'sections', $section->id, null, $section->toArray());
        return response()->json($section->load('department'), 201);
    }

    public function apiSectionsUpdate(Request $request, Section $section)
    {
        $before = $section->toArray();
        $validated = $request->validate([
            'code' => 'required|string|max:10|unique:sections,code,' . $section->id,
            'nom' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:section,service_rattache',
            'department_id' => 'nullable|exists:departments,id',
        ]);
        $section->update($validated);
        $this->recordAudit('UPDATE', 'sections', $section->id, $before, $section->fresh()->toArray());
        return response()->json($section->load('department'));
    }

    public function apiSectionsDestroy(Section $section)
    {
        if ($section->cellules()->count() > 0) {
            return response()->json(['message' => 'Impossible de supprimer: des cellules sont rattachees a cette section.'], 422);
        }
        $before = $section->toArray();
        $section->delete();
        $this->recordAudit('DELETE', 'sections', $section->id, $before);
        return response()->json(['message' => 'Section supprimee.']);
    }

    // ─── CELLULES ────────────────────────────────────────────────

    public function apiCellulesIndex(Request $request)
    {
        $q = Cellule::with('section.department')->orderBy('nom');
        if ($request->search) {
            $q->where('nom', 'like', "%{$request->search}%");
        }
        return response()->json($q->paginate($request->per_page ?? 25));
    }

    public function apiCellulesShow(Cellule $cellule)
    {
        return response()->json($cellule->load('section.department'));
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
        $this->recordAudit('CREATE', 'cellules', $cellule->id, null, $cellule->toArray());
        return response()->json($cellule->load('section'), 201);
    }

    public function apiCellulesUpdate(Request $request, Cellule $cellule)
    {
        $before = $cellule->toArray();
        $validated = $request->validate([
            'code' => 'required|string|max:10|unique:cellules,code,' . $cellule->id,
            'nom' => 'required|string|max:255',
            'description' => 'nullable|string',
            'section_id' => 'required|exists:sections,id',
        ]);
        $cellule->update($validated);
        $this->recordAudit('UPDATE', 'cellules', $cellule->id, $before, $cellule->fresh()->toArray());
        return response()->json($cellule->load('section'));
    }

    public function apiCellulesDestroy(Cellule $cellule)
    {
        if ($cellule->affectations()->count() > 0) {
            return response()->json(['message' => 'Impossible de supprimer: des affectations utilisent cette cellule.'], 422);
        }
        $before = $cellule->toArray();
        $cellule->delete();
        $this->recordAudit('DELETE', 'cellules', $cellule->id, $before);
        return response()->json(['message' => 'Cellule supprimee.']);
    }

    // ─── LOCALITES ───────────────────────────────────────────────

    public function apiLocalitesIndex(Request $request)
    {
        $q = Localite::with('province')->withCount('affectations')->orderBy('nom');
        if ($request->search) {
            $q->where('nom', 'like', "%{$request->search}%");
        }
        return response()->json($q->paginate($request->per_page ?? 25));
    }

    public function apiLocalitesShow(Localite $localite)
    {
        return response()->json($localite->load('province'));
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
        $this->recordAudit('CREATE', 'localites', $localite->id, null, $localite->toArray());
        return response()->json($localite->load('province'), 201);
    }

    public function apiLocalitesUpdate(Request $request, Localite $localite)
    {
        $before = $localite->toArray();
        $validated = $request->validate([
            'code' => 'required|string|max:10|unique:localites,code,' . $localite->id,
            'nom' => 'required|string|max:255',
            'type' => 'required|in:territoire,zone_de_sante,commune,ville,autre',
            'description' => 'nullable|string',
            'province_id' => 'required|exists:provinces,id',
        ]);
        $localite->update($validated);
        $this->recordAudit('UPDATE', 'localites', $localite->id, $before, $localite->fresh()->toArray());
        return response()->json($localite->load('province'));
    }

    public function apiLocalitesDestroy(Localite $localite)
    {
        if ($localite->affectations()->count() > 0) {
            return response()->json(['message' => 'Impossible de supprimer: des affectations utilisent cette localite.'], 422);
        }
        $before = $localite->toArray();
        $localite->delete();
        $this->recordAudit('DELETE', 'localites', $localite->id, $before);
        return response()->json(['message' => 'Localite supprimee.']);
    }

    // ─── ORGANES ─────────────────────────────────────────────────

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
        $this->recordAudit('CREATE', 'organes', $organe->id, null, $organe->toArray());
        return response()->json($organe, 201);
    }

    public function apiOrganesUpdate(Request $request, Organe $organe)
    {
        $before = $organe->toArray();
        $validated = $request->validate([
            'code' => 'required|string|max:10|unique:organes,code,' . $organe->id,
            'nom' => 'required|string|max:255',
            'sigle' => 'required|string|max:20',
            'description' => 'nullable|string',
            'actif' => 'boolean',
        ]);
        $organe->update($validated);
        $this->recordAudit('UPDATE', 'organes', $organe->id, $before, $organe->fresh()->toArray());
        return response()->json($organe);
    }

    public function apiOrganesDestroy(Organe $organe)
    {
        $before = $organe->toArray();
        $organe->delete();
        $this->recordAudit('DELETE', 'organes', $organe->id, $before);
        return response()->json(['message' => 'Organe supprime.']);
    }

    // ─── UTILISATEURS ────────────────────────────────────────────

    public function apiUtilisateursIndex(Request $request)
    {
        $q = User::with(['agent', 'role'])->where('is_super_admin', false)->orderByDesc('id');
        if ($request->search) {
            $q->where('name', 'like', "%{$request->search}%")
              ->orWhere('email', 'like', "%{$request->search}%");
        }
        return response()->json($q->paginate($request->per_page ?? 15));
    }

    public function apiUtilisateursFormData()
    {
        $agentsWithoutUser = Agent::whereDoesntHave('user')->orderBy('nom')->get(['id', 'nom', 'postnom', 'prenom']);
        $roles = Role::orderBy('nom_role')->get(['id', 'nom_role']);
        return response()->json(['agents' => $agentsWithoutUser, 'roles' => $roles]);
    }

    public function apiUtilisateursShow(User $user)
    {
        if ($user->is_super_admin) {
            return response()->json(['message' => 'Utilisateur non trouve.'], 404);
        }
        return response()->json($user->load(['agent', 'role']));
    }

    public function apiUtilisateursStore(Request $request)
    {
        $validated = $request->validate([
            'agent_id' => 'required|exists:agents,id|unique:users,agent_id',
            'role_id' => 'required|exists:roles,id',
            'password' => 'required|string|min:6|confirmed',
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
        $this->recordAudit('CREATE', 'users', $user->id, null, $user->toArray());
        return response()->json($user->load(['agent', 'role']), 201);
    }

    public function apiUtilisateursUpdate(Request $request, User $user)
    {
        $before = $user->toArray();
        $validated = $request->validate([
            'role_id' => 'required|exists:roles,id',
            'password' => 'nullable|string|min:6|confirmed',
        ]);
        $user->role_id = $validated['role_id'];
        if (!empty($validated['password'])) {
            $user->password = bcrypt($validated['password']);
        }
        $user->save();
        $this->recordAudit('UPDATE', 'users', $user->id, $before, $user->fresh()->toArray());
        return response()->json($user->load(['agent', 'role']));
    }

    public function apiUtilisateursDestroy(Request $request, User $user)
    {
        if ($user->is_super_admin) {
            return response()->json(['message' => 'Cet utilisateur ne peut pas etre supprime.'], 403);
        }
        if ($user->id === $request->user()->id) {
            return response()->json(['message' => 'Vous ne pouvez pas supprimer votre propre compte.'], 422);
        }
        $before = $user->toArray();
        $user->delete();
        $this->recordAudit('DELETE', 'users', $user->id, $before);
        return response()->json(['message' => 'Utilisateur supprime.']);
    }

    /**
     * SuperAdmin: list all users with IP and frozen status visible
     */
    public function apiSuperAdminUtilisateurs(Request $request)
    {
        $q = User::with(['agent', 'role'])->where('is_super_admin', false)->orderByDesc('id');
        if ($request->search) {
            $search = $request->search;
            $q->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
            });
        }
        if ($request->frozen === 'true') {
            $q->where('is_frozen', true);
        }
        $users = $q->paginate($request->per_page ?? 15);

        // Expose is_frozen, last_login_ip, last_login_at (normally hidden or not shown)
        $users->getCollection()->transform(function ($user) {
            $data = $user->toArray();
            $data['is_frozen'] = (bool) $user->is_frozen;
            $data['last_login_ip'] = $user->last_login_ip;
            $data['last_login_at'] = $user->last_login_at?->format('d/m/Y H:i');
            return $data;
        });

        return response()->json($users);
    }

    /**
     * SuperAdmin: freeze a user account
     */
    public function apiUtilisateurFreeze(User $user)
    {
        if ($user->is_super_admin) {
            return response()->json(['message' => 'Action non autorisee.'], 403);
        }

        $user->update(['is_frozen' => true]);

        // Kill all active sessions for this user
        DB::table('sessions')->where('user_id', $user->id)->delete();

        $this->recordAudit('UPDATE', 'users', $user->id, ['is_frozen' => false], ['is_frozen' => true]);

        return response()->json(['message' => "Le compte de {$user->name} a ete gele."]);
    }

    /**
     * SuperAdmin: unfreeze a user account
     */
    public function apiUtilisateurUnfreeze(User $user)
    {
        if ($user->is_super_admin) {
            return response()->json(['message' => 'Action non autorisee.'], 403);
        }

        $user->update(['is_frozen' => false]);
        $this->recordAudit('UPDATE', 'users', $user->id, ['is_frozen' => true], ['is_frozen' => false]);

        return response()->json(['message' => "Le compte de {$user->name} a ete degele."]);
    }

    // ─── DOCUMENTS DE TRAVAIL ────────────────────────────────────

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
        $this->recordAudit('CREATE', 'documents_travail', $doc->id, null, $doc->toArray());
        return response()->json($doc, 201);
    }

    public function apiDocsTravailUpdate(Request $request, DocumentTravail $documentTravail)
    {
        $before = $documentTravail->toArray();
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
        $this->recordAudit('UPDATE', 'documents_travail', $documentTravail->id, $before, $documentTravail->fresh()->toArray());
        return response()->json($documentTravail);
    }

    public function apiDocsTravailDestroy(DocumentTravail $documentTravail)
    {
        $before = $documentTravail->toArray();
        $documentTravail->delete();
        $this->recordAudit('DELETE', 'documents_travail', $documentTravail->id, $before);
        return response()->json(['message' => 'Document supprime.']);
    }

    // ─── CATEGORIES DOCUMENTS ────────────────────────────────────

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
        $this->recordAudit('CREATE', 'categories_documents', $cat->id, null, $cat->toArray());
        return response()->json($cat, 201);
    }

    public function apiCategoriesDocsUpdate(Request $request, CategorieDocument $categorieDocument)
    {
        $before = $categorieDocument->toArray();
        $validated = $request->validate([
            'nom' => 'required|string|max:255|unique:categorie_documents,nom,' . $categorieDocument->id,
            'icone' => 'nullable|string|max:50',
            'actif' => 'boolean',
        ]);
        $categorieDocument->update($validated);
        $this->recordAudit('UPDATE', 'categories_documents', $categorieDocument->id, $before, $categorieDocument->fresh()->toArray());
        return response()->json($categorieDocument);
    }

    public function apiCategoriesDocsDestroy(CategorieDocument $categorieDocument)
    {
        $before = $categorieDocument->toArray();
        $categorieDocument->delete();
        $this->recordAudit('DELETE', 'categories_documents', $categorieDocument->id, $before);
        return response()->json(['message' => 'Categorie supprimee.']);
    }

    // ─── LOGS ────────────────────────────────────────────────────

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

    // ─── AFFECTATIONS ────────────────────────────────────────────

    public function apiAffectationsIndex(Request $request)
    {
        if (!Schema::hasTable('affectations')) {
            return response()->json(['data' => [], 'total' => 0]);
        }
        $q = Affectation::with(['agent', 'fonction', 'department', 'section', 'cellule', 'province', 'localite'])
            ->orderByDesc('created_at');
        $this->scopeService()->applyAffectationScope($q, $request->user());
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
        $scope = $this->scopeService();
        $user = request()->user();

        $agentsQuery = Agent::query()->orderBy('nom');
        $scope->applyAgentScope($agentsQuery, $user);

        $agents = Schema::hasTable('agents')
            ? $agentsQuery->get(['id', 'nom', 'prenom'])->map(function ($a) {
                return [
                    'id' => $a->id,
                    'nom' => $a->nom,
                    'prenom' => $a->prenom,
                    'id_agent' => $a->id_agent,
                ];
            })
            : [];

        return response()->json([
            'agents' => $agents,
            'fonctions' => Schema::hasTable('fonctions') ? Fonction::orderBy('nom')->get(['id', 'nom', 'niveau_administratif', 'type_poste']) : [],
            'departments' => Schema::hasTable('departments') ? $scope->filterDepartments(Department::query(), $user)->orderBy('nom')->get(['id', 'nom', 'code']) : [],
            'sections' => Schema::hasTable('sections') ? Section::orderBy('nom')->get(['id', 'nom', 'code', 'department_id']) : [],
            'cellules' => Schema::hasTable('cellules') ? Cellule::orderBy('nom')->get(['id', 'nom', 'code', 'section_id']) : [],
            'provinces' => Schema::hasTable('provinces') ? $scope->filterProvinces(Province::query(), $user)->orderBy('nom')->get(['id', 'nom', 'code']) : [],
            'localites' => Schema::hasTable('localites') ? Localite::orderBy('nom')->get(['id', 'nom', 'code', 'province_id']) : [],
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

        $agent = Agent::find($validated['agent_id']);
        if (!$this->scopeService()->canAccessAgent($request->user(), $agent)) {
            abort(403, 'Acces refuse pour cet agent.');
        }

        $affectation = Affectation::create($validated);
        $this->recordAudit('CREATE', 'affectations', $affectation->id, null, $affectation->toArray());
        return response()->json($affectation->load(['agent', 'fonction']), 201);
    }

    public function apiAffectationsUpdate(Request $request, Affectation $affectation)
    {
        if (!$this->scopeService()->canAccessAffectation($request->user(), $affectation)) {
            abort(403, 'Acces refuse pour cette affectation.');
        }

        $before = $affectation->toArray();
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

        $agent = Agent::find($validated['agent_id']);
        if (!$this->scopeService()->canAccessAgent($request->user(), $agent)) {
            abort(403, 'Acces refuse pour cet agent.');
        }

        $affectation->update($validated);
        $this->recordAudit('UPDATE', 'affectations', $affectation->id, $before, $affectation->fresh()->toArray());
        return response()->json($affectation->load(['agent', 'fonction']));
    }

    public function apiAffectationsDestroy(Affectation $affectation)
    {
        if (!$this->scopeService()->canAccessAffectation(request()->user(), $affectation)) {
            abort(403, 'Acces refuse pour cette affectation.');
        }

        $before = $affectation->toArray();
        $affectation->delete();
        $this->recordAudit('DELETE', 'affectations', $affectation->id, $before);
        return response()->json(['message' => 'Affectation supprimee.']);
    }
}
