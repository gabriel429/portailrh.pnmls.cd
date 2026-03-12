<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Province;
use App\Models\Grade;
use App\Models\Role;
use App\Models\Department;
use App\Models\Permission;
use Illuminate\Support\Facades\File;
use Illuminate\Http\Request;

class ParametresController extends Controller
{
    /**
     * Tableau de bord des paramètres.
     */
    public function dashboard()
    {
        $stats = [
            'provinces'   => Province::count(),
            'departments' => Department::count(),
            'grades'      => Grade::count(),
            'roles'       => Role::count(),
            'permissions' => Permission::count(),
            'agents'      => Agent::count(),
        ];

        return view('admin.dashboard', compact('stats'));
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
        $departments = Department::with('province')->withCount('agents')->orderBy('nom')->paginate(20);
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
}
