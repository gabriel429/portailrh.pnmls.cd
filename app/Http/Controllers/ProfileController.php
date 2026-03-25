<?php

namespace App\Http\Controllers;

class ProfileController extends Controller
{
    /**
     * Get profile via API
     */
    public function apiShow()
    {
        return response()->json([
            'user' => auth()->user(),
        ]);
    }
}
