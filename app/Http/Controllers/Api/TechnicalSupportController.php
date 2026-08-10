<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TechnicalSupportMessage;
use App\Models\TechnicalSupportTicket;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TechnicalSupportController extends Controller
{
    private const TECHNICAL_ROLES = [
        'Section Nouvelle Technologie',
        'Chef Section Nouvelle Technologie',
        'Chef de Section Nouvelle Technologie',
    ];

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = TechnicalSupportTicket::query()
            ->with(['requester.agent:id,prenom,nom,photo', 'messages' => fn($query) => $query->latest()->limit(1)])
            ->withCount('messages')
            ->latest('updated_at');

        if (!$user->isAdminNT()) {
            $query->where('requester_user_id', $user->id);
        }

        $this->applyFilters($query, $request);

        return response()->json([
            'data' => $query->paginate(20),
            'is_technician' => $user->isAdminNT(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:180'],
            'description' => ['required', 'string', 'max:10000'],
            'module' => ['required', 'string', 'max:80'],
            'priority' => ['required', Rule::in(TechnicalSupportTicket::PRIORITIES)],
            'attachment' => ['nullable', 'file', 'max:10240', 'mimes:jpg,jpeg,png,webp,pdf,doc,docx,xls,xlsx,txt,zip'],
        ]);

        $ticket = DB::transaction(function () use ($request, $validated) {
            $ticket = TechnicalSupportTicket::create([
                'requester_user_id' => $request->user()->id,
                'subject' => $validated['subject'],
                'description' => $validated['description'],
                'module' => $validated['module'],
                'priority' => $validated['priority'],
            ]);

            if ($request->hasFile('attachment')) {
                $ticket->update($this->storeAttachment($request->file('attachment'), "technical-support/{$ticket->id}", 'attachment'));
            }

            return $ticket;
        });

        $requesterName = $request->user()->agent
            ? trim(($request->user()->agent->prenom ?? '').' '.($request->user()->agent->nom ?? ''))
            : $request->user()->name;

        NotificationService::envoyerMultiple(
            $this->technicianUserIds(),
            'technical_support_new',
            'Nouveau problème technique',
            "L’agent {$requesterName} a signalé un problème concernant {$ticket->module}. Objet : {$ticket->subject}. Priorité : ".ucfirst($ticket->priority).'.',
            "/support-technique/{$ticket->id}",
            $request->user()->id,
            false
        );

        return response()->json([
            'message' => 'Votre demande a été envoyée à la section Nouvelle Technologie. Vous serez informé dès qu’un technicien vous répondra.',
            'data' => $this->ticketPayload($ticket->fresh()),
        ], 201);
    }

    public function show(Request $request, TechnicalSupportTicket $technicalSupportTicket): JsonResponse
    {
        $this->authorizeTicket($request->user(), $technicalSupportTicket);

        $technicalSupportTicket->load([
            'requester.agent:id,prenom,nom,photo,email_professionnel',
            'messages.user.agent:id,prenom,nom,photo',
        ]);

        return response()->json([
            'data' => $this->ticketPayload($technicalSupportTicket, true),
            'is_technician' => $request->user()->isAdminNT(),
        ]);
    }

    public function reply(Request $request, TechnicalSupportTicket $technicalSupportTicket): JsonResponse
    {
        $this->authorizeTicket($request->user(), $technicalSupportTicket);
        $validated = $request->validate([
            'body' => ['nullable', 'string', 'max:10000', 'required_without:attachment'],
            'attachment' => ['nullable', 'file', 'max:10240', 'mimes:jpg,jpeg,png,webp,pdf,doc,docx,xls,xlsx,txt,zip'],
        ]);

        $attributes = [
            'ticket_id' => $technicalSupportTicket->id,
            'user_id' => $request->user()->id,
            'body' => $validated['body'] ?? null,
        ];
        if ($request->hasFile('attachment')) {
            $attributes = array_merge($attributes, $this->storeAttachment(
                $request->file('attachment'),
                "technical-support/{$technicalSupportTicket->id}/messages",
                'attachment'
            ));
        }

        $message = TechnicalSupportMessage::create($attributes);
        $technicalSupportTicket->touch();
        $this->notifyReply($request->user(), $technicalSupportTicket);

        return response()->json([
            'message' => 'Réponse envoyée.',
            'data' => $this->messagePayload($message->load('user.agent')),
        ], 201);
    }

    public function updateStatus(Request $request, TechnicalSupportTicket $technicalSupportTicket): JsonResponse
    {
        abort_unless($request->user()->isAdminNT(), 403, 'Action réservée à la section Nouvelle Technologie.');
        $validated = $request->validate([
            'status' => ['required', Rule::in(TechnicalSupportTicket::STATUSES)],
        ]);

        $from = $technicalSupportTicket->status;
        $to = $validated['status'];
        if ($from !== $to) {
            DB::transaction(function () use ($request, $technicalSupportTicket, $from, $to) {
                $technicalSupportTicket->update([
                    'status' => $to,
                    'resolved_at' => $to === 'resolu' ? now() : ($technicalSupportTicket->resolved_at),
                    'closed_at' => $to === 'ferme' ? now() : null,
                ]);
                TechnicalSupportMessage::create([
                    'ticket_id' => $technicalSupportTicket->id,
                    'user_id' => $request->user()->id,
                    'type' => 'status_change',
                    'status_from' => $from,
                    'status_to' => $to,
                ]);
            });

            NotificationService::envoyer(
                $technicalSupportTicket->requester_user_id,
                'technical_support_status',
                'Mise à jour de votre demande technique',
                "Le statut de « {$technicalSupportTicket->subject} » est maintenant : ".$this->statusLabel($to).'.',
                "/support-technique/{$technicalSupportTicket->id}",
                $request->user()->id,
                false
            );
        }

        return response()->json([
            'message' => 'Statut mis à jour.',
            'data' => $this->ticketPayload($technicalSupportTicket->fresh()),
        ]);
    }

    public function dashboard(Request $request): JsonResponse
    {
        abort_unless($request->user()->isAdminNT(), 403, 'Accès réservé à la section Nouvelle Technologie.');

        $counts = TechnicalSupportTicket::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return response()->json(['data' => [
            'total' => TechnicalSupportTicket::count(),
            'nouveau' => (int) ($counts['nouveau'] ?? 0),
            'en_cours' => (int) ($counts['en_cours'] ?? 0),
            'resolu' => (int) ($counts['resolu'] ?? 0),
            'ferme' => (int) ($counts['ferme'] ?? 0),
            'urgent' => TechnicalSupportTicket::where('priority', 'urgent')->whereNotIn('status', ['resolu', 'ferme'])->count(),
        ]]);
    }

    public function downloadTicketAttachment(Request $request, TechnicalSupportTicket $technicalSupportTicket): StreamedResponse
    {
        $this->authorizeTicket($request->user(), $technicalSupportTicket);
        abort_unless($technicalSupportTicket->attachment_path, 404);

        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk($technicalSupportTicket->attachment_disk ?: 'public');

        return $disk->download($technicalSupportTicket->attachment_path, $technicalSupportTicket->attachment_name);
    }

    public function downloadMessageAttachment(Request $request, TechnicalSupportMessage $technicalSupportMessage): StreamedResponse
    {
        $this->authorizeTicket($request->user(), $technicalSupportMessage->ticket);
        abort_unless($technicalSupportMessage->attachment_path, 404);

        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk($technicalSupportMessage->attachment_disk ?: 'public');

        return $disk->download($technicalSupportMessage->attachment_path, $technicalSupportMessage->attachment_name);
    }

    private function authorizeTicket(User $user, TechnicalSupportTicket $ticket): void
    {
        abort_unless($user->isAdminNT() || $ticket->requester_user_id === $user->id, 403, 'Vous ne pouvez pas consulter cette demande.');
    }

    private function applyFilters(Builder $query, Request $request): void
    {
        if (in_array($request->string('status')->toString(), TechnicalSupportTicket::STATUSES, true)) {
            $query->where('status', $request->string('status')->toString());
        }
        if (in_array($request->string('priority')->toString(), TechnicalSupportTicket::PRIORITIES, true)) {
            $query->where('priority', $request->string('priority')->toString());
        }
        if ($search = trim($request->string('search')->toString())) {
            $query->where(fn($subQuery) => $subQuery
                ->where('subject', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%")
                ->orWhere('module', 'like', "%{$search}%"));
        }
    }

    private function storeAttachment($file, string $directory, string $prefix): array
    {
        return [
            "{$prefix}_disk" => 'public',
            "{$prefix}_path" => $file->store($directory, 'public'),
            "{$prefix}_name" => $file->getClientOriginalName(),
            "{$prefix}_mime" => $file->getMimeType(),
            "{$prefix}_size" => $file->getSize(),
        ];
    }

    private function technicianUserIds(): array
    {
        return User::query()
            ->whereHas('role', fn($query) => $query->whereIn('nom_role', self::TECHNICAL_ROLES))
            ->pluck('id')
            ->all();
    }

    private function notifyReply(User $sender, TechnicalSupportTicket $ticket): void
    {
        if ($sender->isAdminNT()) {
            NotificationService::envoyer(
                $ticket->requester_user_id,
                'technical_support_reply',
                'Réponse du support technique',
                "Un technicien a répondu à votre demande « {$ticket->subject} ».",
                "/support-technique/{$ticket->id}",
                $sender->id,
                false
            );
            return;
        }

        NotificationService::envoyerMultiple(
            $this->technicianUserIds(),
            'technical_support_reply',
            'Nouvelle réponse à une demande technique',
            "L’agent a répondu à la demande « {$ticket->subject} ».",
            "/support-technique/{$ticket->id}",
            $sender->id,
            false
        );
    }

    private function ticketPayload(TechnicalSupportTicket $ticket, bool $withMessages = false): array
    {
        $requester = $ticket->requester;
        $payload = [
            'id' => $ticket->id,
            'subject' => $ticket->subject,
            'description' => $ticket->description,
            'module' => $ticket->module,
            'priority' => $ticket->priority,
            'status' => $ticket->status,
            'requester' => $requester ? [
                'id' => $requester->id,
                'name' => $requester->agent
                    ? trim(($requester->agent->prenom ?? '').' '.($requester->agent->nom ?? ''))
                    : $requester->name,
                'photo' => $requester->agent?->photo,
            ] : null,
            'attachment' => $ticket->attachment_path ? [
                'name' => $ticket->attachment_name,
                'mime' => $ticket->attachment_mime,
                'size' => $ticket->attachment_size,
                'url' => "/api/technical-support/{$ticket->id}/attachment",
            ] : null,
            'messages_count' => $ticket->messages_count ?? $ticket->messages->count(),
            'created_at' => $ticket->created_at,
            'updated_at' => $ticket->updated_at,
            'resolved_at' => $ticket->resolved_at,
            'closed_at' => $ticket->closed_at,
        ];

        if ($withMessages) {
            $payload['messages'] = $ticket->messages->map(fn($message) => $this->messagePayload($message))->values();
        }

        return $payload;
    }

    private function messagePayload(TechnicalSupportMessage $message): array
    {
        return [
            'id' => $message->id,
            'type' => $message->type,
            'body' => $message->body,
            'status_from' => $message->status_from,
            'status_to' => $message->status_to,
            'author' => [
                'id' => $message->user->id,
                'name' => $message->user->agent
                    ? trim(($message->user->agent->prenom ?? '').' '.($message->user->agent->nom ?? ''))
                    : $message->user->name,
                'is_technician' => $message->user->isAdminNT(),
            ],
            'attachment' => $message->attachment_path ? [
                'name' => $message->attachment_name,
                'mime' => $message->attachment_mime,
                'size' => $message->attachment_size,
                'url' => "/api/technical-support/messages/{$message->id}/attachment",
            ] : null,
            'created_at' => $message->created_at,
        ];
    }

    private function statusLabel(string $status): string
    {
        return [
            'nouveau' => 'Nouveau',
            'en_cours' => 'En cours',
            'resolu' => 'Résolu',
            'ferme' => 'Fermé',
        ][$status] ?? $status;
    }
}