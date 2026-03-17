<?php

namespace App\Http\Controllers;

use App\Models\NotificationPortail;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $query = NotificationPortail::pourUser(auth()->id());

        $filtre = $request->query('filtre');
        if ($filtre === 'non_lues') {
            $query->nonLues();
        } elseif ($filtre && $filtre !== 'non_lues') {
            // Filter by type or type prefix (demande covers demande, demande_modifiee, etc.)
            $query->where('type', 'like', $filtre . '%');
        }

        $notifications = $query->latest()->paginate(30);

        $nonLuesCount = NotificationPortail::pourUser(auth()->id())->nonLues()->count();

        return view('notifications.index', compact('notifications', 'nonLuesCount'));
    }

    public function markRead(NotificationPortail $notification)
    {
        if ($notification->user_id !== auth()->id()) {
            abort(403);
        }

        $notification->marquerCommeLue();

        if ($notification->lien) {
            return redirect($notification->lien);
        }

        return back();
    }

    public function markAllRead()
    {
        NotificationPortail::pourUser(auth()->id())
            ->nonLues()
            ->update(['lu' => true, 'lu_at' => now()]);

        return back()->with('success', 'Toutes les notifications ont été marquées comme lues.');
    }

    public function destroy(NotificationPortail $notification)
    {
        if ($notification->user_id !== auth()->id()) {
            abort(403);
        }

        $notification->delete();

        return back()->with('success', 'Notification supprimée.');
    }

    /**
     * API: get unread count + recent notifications (for AJAX)
     */
    public function unreadCount()
    {
        $count = NotificationPortail::pourUser(auth()->id())->nonLues()->count();
        $recent = NotificationPortail::pourUser(auth()->id())
            ->latest()
            ->take(5)
            ->get()
            ->map(fn($n) => [
                'id' => $n->id,
                'titre' => \Illuminate\Support\Str::limit($n->titre, 40),
                'icone' => $n->icone,
                'couleur' => $n->couleur,
                'lu' => $n->lu,
                'temps' => $n->created_at->diffForHumans(),
                'lien' => url('/notifications/' . $n->id . '/read'),
            ]);

        return response()->json(['count' => $count, 'recent' => $recent]);
    }
}
