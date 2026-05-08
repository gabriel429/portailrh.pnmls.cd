<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\ForumPostResource;
use App\Http\Resources\ForumCommentResource;
use App\Models\ForumComment;
use App\Models\ForumCommentReaction;
use App\Models\ForumPost;
use App\Models\ForumPostRead;
use App\Models\NotificationPortail;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ForumPostController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => 'nullable|string|max:80',
            'per_page' => 'nullable|integer|min:1|max:30',
        ]);

        $search = trim((string) ($validated['search'] ?? ''));
        $perPage = (int) ($validated['per_page'] ?? 12);
        $user = $request->user();

        $posts = ForumPost::query()
            ->active()
            ->with([
                'user.role',
                'user.agent.role',
                'user.agent.departement',
                'user.agent.departement.province',
                'user.agent.province',
                'agent.role',
                'agent.departement',
                'agent.departement.province',
                'agent.province',
                'comments' => fn ($query) => $query
                    ->with($this->commentRelations($user))
                    ->withCount($this->commentReactionCounts())
                    ->oldest(),
            ])
            ->withCount('comments')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->where('titre', 'like', '%' . $search . '%')
                        ->orWhere('contenu', 'like', '%' . $search . '%')
                        ->orWhereHas('user', fn ($userQuery) => $userQuery->where('name', 'like', '%' . $search . '%'))
                        ->orWhereHas('agent', function ($agentQuery) use ($search) {
                            $agentQuery
                                ->where('nom', 'like', '%' . $search . '%')
                                ->orWhere('prenom', 'like', '%' . $search . '%')
                                ->orWhere('postnom', 'like', '%' . $search . '%');
                        })
                        ->orWhereHas('comments', function ($commentQuery) use ($search) {
                            $commentQuery->where('contenu', 'like', '%' . $search . '%');
                        });
                });
            })
            ->latest()
            ->paginate($perPage);

        return $this->paginated($posts, ForumPostResource::class);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'titre' => 'nullable|string|max:160',
            'contenu' => 'required|string|min:2|max:3000',
        ]);

        $user = $request->user();

        $post = ForumPost::create([
            'user_id' => $user->id,
            'agent_id' => $user->agent_id,
            'titre' => $validated['titre'] ?? null,
            'contenu' => $validated['contenu'],
        ]);

        $post->load([
            'user.role',
            'user.agent.role',
            'user.agent.departement',
            'user.agent.departement.province',
            'user.agent.province',
            'agent.role',
            'agent.departement',
            'agent.departement.province',
            'agent.province',
            'comments',
        ]);
        $post->loadCount('comments');

        return $this->resource(ForumPostResource::make($post), [], [
            'message' => 'Message publié avec succès.',
        ], 201);
    }

    public function storeComment(Request $request, ForumPost $forumPost): JsonResponse
    {
        if ($forumPost->isExpired()) {
            return response()->json([
                'message' => 'Ce sujet a depasse sa duree de vie de deux semaines.',
            ], 422);
        }

        $validated = $request->validate([
            'contenu' => 'required|string|min:1|max:2000',
        ]);

        $user = $request->user();

        $comment = $forumPost->comments()->create([
            'user_id' => $user->id,
            'agent_id' => $user->agent_id,
            'contenu' => $validated['contenu'],
        ]);

        $comment->load($this->commentRelations($user));
        $comment->loadCount($this->commentReactionCounts());
        $this->notifyCommentParticipants($forumPost, $user);

        return $this->resource(ForumCommentResource::make($comment), [], [
            'message' => 'Commentaire publié.',
        ], 201);
    }

    public function markRead(Request $request, ForumPost $forumPost): JsonResponse
    {
        ForumPostRead::updateOrCreate(
            [
                'forum_post_id' => $forumPost->id,
                'user_id' => $request->user()->id,
            ],
            [
                'seen_at' => now(),
            ]
        );

        return $this->success([
            'seen' => true,
            'forum_post_id' => $forumPost->id,
        ], [], [
            'message' => 'Forum marqué comme vu.',
        ]);
    }

    public function reactToComment(Request $request, ForumComment $forumComment): JsonResponse
    {
        $validated = $request->validate([
            'reaction' => 'required|in:' . ForumCommentReaction::LIKE . ',' . ForumCommentReaction::DISLIKE,
        ]);

        $forumComment->loadMissing('forumPost');

        if ($forumComment->forumPost?->isExpired()) {
            return response()->json([
                'message' => 'Ce sujet est ferme, les reactions ne sont plus disponibles.',
            ], 422);
        }

        $user = $request->user();
        $reactionValue = $validated['reaction'];
        $reaction = $forumComment->reactions()
            ->where('user_id', $user->id)
            ->first();

        if ($reaction && $reaction->reaction === $reactionValue) {
            $reaction->delete();
            $message = 'Reaction retiree.';
        } else {
            $forumComment->reactions()->updateOrCreate(
                ['user_id' => $user->id],
                ['reaction' => $reactionValue]
            );
            $message = $reactionValue === ForumCommentReaction::LIKE
                ? 'Vous aimez ce commentaire.'
                : 'Vous n aimez pas ce commentaire.';
        }

        $forumComment->load($this->commentRelations($user));
        $forumComment->loadCount($this->commentReactionCounts());

        return $this->resource(ForumCommentResource::make($forumComment), [], [
            'message' => $message,
        ]);
    }

    public function destroyComment(Request $request, ForumComment $forumComment): JsonResponse
    {
        $forumComment->loadMissing('forumPost');

        if (! $this->canDeleteComment($request->user(), $forumComment)) {
            abort(403, 'Vous ne pouvez pas supprimer ce commentaire.');
        }

        $forumComment->delete();

        return $this->success(null, [], [
            'message' => 'Commentaire supprimé.',
        ]);
    }

    public function destroy(Request $request, ForumPost $forumPost): JsonResponse
    {
        if (! $this->canDelete($request->user(), $forumPost)) {
            abort(403, 'Vous ne pouvez pas supprimer ce message.');
        }

        $forumPost->delete();

        return $this->success(null, [], [
            'message' => 'Message supprimé.',
        ]);
    }

    private function canDelete(User $user, ForumPost $post): bool
    {
        return $user->id === $post->user_id
            || $user->isSuperAdmin()
            || $user->hasAdminAccess();
    }

    private function canDeleteComment(User $user, ForumComment $comment): bool
    {
        return $user->id === $comment->user_id
            || $user->id === $comment->forumPost?->user_id
            || $user->isSuperAdmin()
            || $user->hasAdminAccess();
    }

    private function notifyCommentParticipants(ForumPost $post, User $sender): void
    {
        $recipientIds = $post->comments()
            ->where('user_id', '!=', $sender->id)
            ->pluck('user_id')
            ->push($post->user_id)
            ->filter(fn ($userId) => $userId && (int) $userId !== (int) $sender->id)
            ->unique()
            ->values();

        if ($recipientIds->isEmpty()) {
            return;
        }

        $senderName = $sender->agent?->nom_complet ?: $sender->name;
        $subject = $post->titre ?: str($post->contenu)->limit(70)->toString();
        $now = now();

        $records = $recipientIds->map(fn ($userId) => [
            'user_id' => $userId,
            'type' => 'forum',
            'titre' => 'Nouveau commentaire forum',
            'message' => $senderName . ' a commente le sujet "' . $subject . '".',
            'icone' => 'fa-comments',
            'couleur' => '#0f766e',
            'lien' => '/forum',
            'emetteur_id' => $sender->id,
            'lu' => false,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        NotificationPortail::insert($records);
    }

    private function commentRelations(?User $user): array
    {
        return [
            'user.role',
            'user.agent.role',
            'user.agent.departement',
            'user.agent.departement.province',
            'user.agent.province',
            'agent.role',
            'agent.departement',
            'agent.departement.province',
            'agent.province',
            'forumPost',
            'reactions' => fn ($query) => $query->where('user_id', $user?->id),
        ];
    }

    private function commentReactionCounts(): array
    {
        return [
            'reactions as likes_count' => fn ($query) => $query->where('reaction', ForumCommentReaction::LIKE),
            'reactions as dislikes_count' => fn ($query) => $query->where('reaction', ForumCommentReaction::DISLIKE),
        ];
    }
}
