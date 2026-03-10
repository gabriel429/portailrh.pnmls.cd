<?php

namespace App\Http\Controllers\RH;

use App\Http\Controllers\Controller;
use App\Models\Province;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class ProvinceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $provinces = Province::with('departments')
            ->paginate(15);

        return view('rh.provinces.index', compact('provinces'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('rh.provinces.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => 'required|unique:provinces',
            'nom' => 'required|unique:provinces',
            'description' => 'nullable|string',
        ]);

        Province::create($validated);

        return redirect()->route('provinces.index')
            ->with('success', 'Province créée avec succès');
    }

    /**
     * Display the specified resource.
     */
    public function show(Province $province): View
    {
        $province->load('departments');

        return view('rh.provinces.show', compact('province'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Province $province): View
    {
        return view('rh.provinces.edit', compact('province'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Province $province): RedirectResponse
    {
        $validated = $request->validate([
            'code' => 'required|unique:provinces,code,' . $province->id,
            'nom' => 'required|unique:provinces,nom,' . $province->id,
            'description' => 'nullable|string',
        ]);

        $province->update($validated);

        return redirect()->route('provinces.show', $province)
            ->with('success', 'Province modifiée avec succès');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Province $province): RedirectResponse
    {
        $province->delete();

        return redirect()->route('provinces.index')
            ->with('success', 'Province supprimée avec succès');
    }
}
