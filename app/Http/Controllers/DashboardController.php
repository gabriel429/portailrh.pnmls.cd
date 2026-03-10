<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\Request as RequestModel;
use App\Models\Document;
use App\Models\Pointage;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display the dashboard.
     */
    public function index(): View
    {
        $user = auth()->user();

        $stats = [
            'agents_total' => Agent::count(),
            'agents_actifs' => Agent::actifs()->count(),
            'requests_pending' => RequestModel::enAttente()->count(),
            'documents_total' => Document::count(),
            'pointages_today' => Pointage::byDate(now()->toDateString())->count(),
        ];

        $recent_requests = RequestModel::with(['agent'])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        $recent_pointages = Pointage::with(['agent'])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        return view('dashboard', compact('user', 'stats', 'recent_requests', 'recent_pointages'));
    }
}
