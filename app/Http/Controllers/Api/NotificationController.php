<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\NotificationResource;
use App\Models\NotificationPortail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends ApiController
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

        return $this->paginated($notifications, NotificationResource::class, [], [
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

        return $this->success(null, ['lien' => $notif->lien], [
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

        return $this->success(null, [], [
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

        return $this->success(null, [], [
            'message' => 'Toutes les notifications ont ete marquees comme lues.',
        ]);
    }
}
