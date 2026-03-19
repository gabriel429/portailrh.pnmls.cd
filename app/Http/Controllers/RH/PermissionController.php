<?php

namespace App\Http\Controllers\RH;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class PermissionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $permissions = Permission::paginate(15);

        return view('rh.permissions.index', compact('permissions'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('rh.permissions.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nom' => 'required|unique:permissions',
            'code' => 'required|unique:permissions',
            'description' => 'nullable|string',
        ]);

        Permission::create($validated);

        return redirect()->route('rh.permissions.index')
            ->with('success', 'Permission créée avec succès');
    }

    /**
     * Display the specified resource.
     */
    public function show(Permission $permission): View
    {
        return view('rh.permissions.show', compact('permission'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Permission $permission): View
    {
        return view('rh.permissions.edit', compact('permission'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Permission $permission): RedirectResponse
    {
        $validated = $request->validate([
            'nom' => 'required|unique:permissions,nom,' . $permission->id,
            'code' => 'required|unique:permissions,code,' . $permission->id,
            'description' => 'nullable|string',
        ]);

        $permission->update($validated);

        return redirect()->route('rh.permissions.show', $permission)
            ->with('success', 'Permission modifiée avec succès');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Permission $permission): RedirectResponse
    {
        $permission->roles()->detach();
        $permission->agents()->detach();
        $permission->delete();

        return redirect()->route('rh.permissions.index')
            ->with('success', 'Permission supprimée avec succès');
    }
}
