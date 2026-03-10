<?php

namespace App\Http\Controllers\RH;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Province;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class DepartmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $departments = Department::with(['province', 'agents'])
            ->paginate(15);

        return view('rh.departments.index', compact('departments'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $provinces = Province::all();

        return view('rh.departments.create', compact('provinces'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => 'required|unique:departments',
            'nom' => 'required|unique:departments',
            'description' => 'nullable|string',
            'province_id' => 'required|exists:provinces,id',
        ]);

        Department::create($validated);

        return redirect()->route('departments.index')
            ->with('success', 'Département créé avec succès');
    }

    /**
     * Display the specified resource.
     */
    public function show(Department $department): View
    {
        $department->load(['province', 'agents']);

        return view('rh.departments.show', compact('department'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Department $department): View
    {
        $provinces = Province::all();

        return view('rh.departments.edit', compact('department', 'provinces'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Department $department): RedirectResponse
    {
        $validated = $request->validate([
            'code' => 'required|unique:departments,code,' . $department->id,
            'nom' => 'required|unique:departments,nom,' . $department->id,
            'description' => 'nullable|string',
            'province_id' => 'required|exists:provinces,id',
        ]);

        $department->update($validated);

        return redirect()->route('departments.show', $department)
            ->with('success', 'Département modifié avec succès');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Department $department): RedirectResponse
    {
        $department->delete();

        return redirect()->route('departments.index')
            ->with('success', 'Département supprimé avec succès');
    }
}
