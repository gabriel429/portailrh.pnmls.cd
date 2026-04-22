<?php

namespace App\Http\Controllers\Api;

use App\Mail\AgentContactMail;
use App\Http\Resources\MessageResource;
use App\Models\Agent;
use App\Models\Message;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class MessageController extends ApiController
{
    /**
     * Compose and send an email to one or more agents, with optional attachment.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'primary_agent_id' => ['nullable', 'integer', 'exists:agents,id'],
            'recipient_agent_ids' => ['nullable', 'array'],
            'recipient_agent_ids.*' => ['integer', 'distinct', 'exists:agents,id'],
            'manual_emails' => ['nullable', 'array'],
            'manual_emails.*' => ['email:rfc'],
            'subject' => ['required', 'string', 'max:180'],
            'body' => ['required', 'string', 'max:5000'],
            'attachment' => ['nullable', 'file', 'max:10240'],
        ]);

        $recipientIds = collect([$validated['primary_agent_id'] ?? null])
            ->merge($validated['recipient_agent_ids'] ?? [])
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $agentRecipients = Agent::query()
            ->whereIn('id', $recipientIds)
            ->get(['id', 'nom', 'prenom', 'email', 'email_professionnel']);

        $recipientPayloads = $agentRecipients
            ->map(function (Agent $agent) {
                $email = $agent->email_professionnel ?: $agent->email;

                return [
                    'agent_id' => $agent->id,
                    'name' => trim(($agent->prenom ?? '') . ' ' . ($agent->nom ?? '')) ?: 'Agent',
                    'email' => $email ? mb_strtolower(trim($email)) : null,
                ];
            })
            ->filter(fn (array $recipient) => filled($recipient['email']))
            ->values();

        $manualRecipients = collect($validated['manual_emails'] ?? [])
            ->map(fn ($email) => mb_strtolower(trim($email)))
            ->filter()
            ->unique()
            ->map(fn ($email) => [
                'agent_id' => null,
                'name' => $email,
                'email' => $email,
            ])
            ->values();

        $allRecipients = $recipientPayloads
            ->concat($manualRecipients)
            ->unique('email')
            ->values();

        if ($allRecipients->isEmpty()) {
            throw ValidationException::withMessages([
                'manual_emails' => ['Ajoutez au moins un destinataire valide.'],
            ]);
        }

        $user = $request->user();
        $senderName = trim(($user->agent?->prenom ?? '') . ' ' . ($user->agent?->nom ?? '')) ?: ($user->name ?? 'E-PNMLS');
        $senderEmail = $user->email;

        $attachmentMeta = $this->storeAttachment($request->file('attachment'));

        foreach ($allRecipients as $recipient) {
            Mail::to($recipient['email'])->send(new AgentContactMail(
                subject: $validated['subject'],
                body: $validated['body'],
                senderName: $senderName,
                senderEmail: $senderEmail,
                recipientName: $recipient['name'],
                attachmentPath: $attachmentMeta['path'] ?? null,
                attachmentName: $attachmentMeta['name'] ?? null,
            ));

            if ($recipient['agent_id']) {
                Message::create([
                    'agent_id' => $recipient['agent_id'],
                    'sender_id' => $user->id,
                    'sujet' => $validated['subject'],
                    'contenu' => $validated['body'],
                    'lu' => false,
                ]);
            }
        }

        return $this->success([
            'sent' => $allRecipients->count(),
            'saved_messages' => $recipientPayloads->count(),
        ], [], [
            'message' => 'Email envoye avec succes.',
        ], 201);
    }

    /**
     * Display a single message. Marks it as read automatically.
     */
    public function show(Request $request, Message $message): JsonResponse
    {
        $user = $request->user();

        // Ensure the message belongs to this user
        $agent = $user->agent;
        if ($agent && $message->agent_id !== $agent->id) {
            return response()->json(['message' => 'Acces refuse.'], 403);
        }

        // Auto-mark as read
        if (!$message->lu) {
            $message->update(['lu' => true]);
        }

        $message->load('sender');

        $resource = MessageResource::make($message);
        $resolved = $resource->resolve();

        return response()->json(array_merge($resolved, [
            'data' => $resolved,
            'message_resource' => $resolved,
        ]));
    }

    private function storeAttachment(?UploadedFile $file): array
    {
        if (!$file) {
            return [];
        }

        $storedPath = $file->store('contact-mails');

        return [
            'path' => $storedPath,
            'name' => $file->getClientOriginalName(),
        ];
    }
}
