<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NotificationPortail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Display a listing of notifications for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $filtre = $request->input('filtre');

        $query = NotificationPortail::pourUser($user->id)
            ->with('emetteur')
            ->latest();

        if ($filtre === 'non_lues') {
            $query->nonLues();
        } elseif ($filtre) {
            $query->where('type', $filtre);
        }

        $notifications = $query->paginate(20);

        $nonLuesCount = NotificationPortail::pourUser($user->id)->nonLues()->count();

        return response()->json([
            'data' => $notifications->items(),
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
                'from' => $notifications->firstItem(),
                'to' => $notifications->lastItem(),
            ],
            'nonLuesCount' => $nonLuesCount,
        ]);
    }

    /**
     * Mark a notification as read and return its link if any.
     */
    public function markRead(Request $request, int $notification): JsonResponse
    {
        $notif = NotificationPortail::findOrFail($notification);

        if ($notif->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Acces refuse.'], 403);
        }

        $notif->update([
            'lu' => true,
            'lu_at' => now(),
        ]);

        return response()->json([
            'message' => 'Notification marquee comme lue.',
            'lien' => $notif->lien,
        ]);
    }

    /**
     * Delete a notification.
     */
    public function destroy(Request $request, int $notification): JsonResponse
    {
        $notif = NotificationPortail::findOrFail($notification);

        if ($notif->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Acces refuse.'], 403);
        }

        $notif->delete();

        return response()->json([
            'message' => 'Notification supprimee.',
        ]);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllRead(Request $request): JsonResponse
    {
        NotificationPortail::pourUser($request->user()->id)
            ->nonLues()
            ->update(['lu' => true, 'lu_at' => now()]);

        return response()->json([
            'message' => 'Toutes les notifications ont ete marquees comme lues.',
        ]);
    }
}
