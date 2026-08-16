<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\IpAllowlistEntry;
use App\Services\GeoRestrictionService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Manages exceptions to the login-time DRC/no-VPN restriction (see
 * GeoRestrictionService, AuthController::denyLoginForGeoRestriction).
 */
class IpAllowlistController extends Controller
{
    public function index()
    {
        return response()->json(
            IpAllowlistEntry::with('creator:id,name')->latest()->get()
        );
    }

    public function store(Request $request, GeoRestrictionService $geo)
    {
        $validated = $request->validate([
            'ip_address' => ['required', Rule::unique('ip_allowlist', 'ip_address'), function ($attribute, $value, $fail) {
                if (!filter_var($value, FILTER_VALIDATE_IP)) {
                    $fail("L'adresse IP n'est pas valide.");
                }
            }],
            'label' => 'nullable|string|max:255',
        ]);

        $entry = IpAllowlistEntry::create([
            'ip_address' => $validated['ip_address'],
            'label' => $validated['label'] ?? null,
            'created_by' => $request->user()->id,
        ]);

        $geo->forgetCaches();

        return response()->json($entry->load('creator:id,name'), 201);
    }

    public function destroy(IpAllowlistEntry $ipAllowlistEntry, GeoRestrictionService $geo)
    {
        $ipAllowlistEntry->delete();
        $geo->forgetCaches();

        return response()->json(['message' => 'Adresse IP retirée de la liste blanche.']);
    }
}
