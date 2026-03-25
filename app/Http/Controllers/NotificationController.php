<?php

namespace App\Http\Controllers;

use App\Models\NotificationPortail;

class NotificationController extends Controller
{
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

    public function markAllRead()
    {
        NotificationPortail::pourUser(auth()->id())
            ->nonLues()
            ->update(['lu' => true, 'lu_at' => now()]);

        return back()->with('success', 'Toutes les notifications ont été marquées comme lues.');
    }
}
