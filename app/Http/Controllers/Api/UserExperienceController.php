<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\CommuniqueResource;
use App\Http\Resources\ForumPostResource;
use App\Models\Communique;
use App\Models\ForumPost;
use App\Models\UserGuidedTour;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserExperienceController extends ApiController
{
    private const TOUR_VERSION = '2026-05-ux-v1';

    public function bootstrap(Request $request): JsonResponse
    {
        $user = $request->user();

        $tour = UserGuidedTour::firstOrCreate(
            ['user_id' => $user->id],
            ['version' => self::TOUR_VERSION]
        );

        $communiques = Communique::query()
            ->visibles()
            ->with(['attachments', 'auteur.role', 'auteur.agent.departement', 'auteur.agent.province'])
            ->withCount('reads')
            ->whereDoesntHave('reads', fn ($query) => $query->where('user_id', $user->id))
            ->latest()
            ->limit(5)
            ->get();

        $forums = ForumPost::query()
            ->active()
            ->with([
                'user.role',
                'user.agent.role',
                'user.agent.departement.province',
                'user.agent.province',
                'agent.role',
                'agent.departement.province',
                'agent.province',
            ])
            ->withCount('comments')
            ->where('user_id', '!=', $user->id)
            ->whereDoesntHave('reads', fn ($query) => $query->where('user_id', $user->id))
            ->latest()
            ->limit(3)
            ->get();

        return $this->success([
            'guided_tour' => [
                'version' => $tour->version,
                'completed_at' => optional($tour->completed_at)?->toIso8601String(),
                'skipped_at' => optional($tour->skipped_at)?->toIso8601String(),
                'should_prompt' => ! $tour->completed_at && ! $tour->skipped_at,
            ],
            'communiques' => CommuniqueResource::collection($communiques)->resolve(),
            'forums' => ForumPostResource::collection($forums)->resolve(),
        ]);
    }

    public function saveTour(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'action' => 'required|in:completed,skipped',
        ]);

        $tour = UserGuidedTour::updateOrCreate(
            ['user_id' => $request->user()->id],
            ['version' => self::TOUR_VERSION]
        );

        if ($validated['action'] === 'completed') {
            $tour->forceFill([
                'completed_at' => now(),
                'skipped_at' => null,
                'version' => self::TOUR_VERSION,
            ])->save();
        }

        if ($validated['action'] === 'skipped') {
            $tour->forceFill([
                'skipped_at' => now(),
                'version' => self::TOUR_VERSION,
            ])->save();
        }

        return $this->success([
            'guided_tour' => [
                'version' => $tour->version,
                'completed_at' => optional($tour->completed_at)?->toIso8601String(),
                'skipped_at' => optional($tour->skipped_at)?->toIso8601String(),
                'should_prompt' => false,
            ],
        ]);
    }
}
