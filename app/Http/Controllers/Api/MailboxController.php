<?php

namespace App\Http\Controllers\Api;

use App\Models\MailboxCredential;
use App\Models\SentMailHistory;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Throwable;

class MailboxController extends ApiController
{
    public function settings(Request $request): JsonResponse
    {
        $credential = MailboxCredential::query()
            ->where('user_id', $request->user()->id)
            ->first();

        return $this->success($this->settingsPayload($request->user(), $credential));
    }

    public function saveSettings(Request $request): JsonResponse
    {
        $user = $request->user();
        $credential = MailboxCredential::query()->where('user_id', $user->id)->first();

        $validator = Validator::make($request->all(), [
            'email' => ['nullable', 'email:rfc', 'max:255'],
            'username' => ['required', 'string', 'max:255'],
            'password' => [$credential ? 'nullable' : 'required', 'string', 'max:255'],
            'imap_host' => ['required', 'string', 'max:255'],
            'imap_port' => ['required', 'integer', 'between:1,65535'],
            'imap_encryption' => ['required', 'string', 'in:ssl,tls,none'],
            'signature' => ['nullable', 'string', 'max:5000'],
        ], [
            'password.required' => 'Le mot de passe de la boite mail est requis.',
        ]);

        $validated = $validator->validate();
        $password = $validated['password'] ?? $credential?->imap_password;

        if (!$password) {
            throw ValidationException::withMessages([
                'password' => ['Le mot de passe de la boite mail est requis.'],
            ]);
        }

        $stream = $this->openConnectionFor(
            $validated['imap_host'],
            (int) $validated['imap_port'],
            $validated['imap_encryption'],
            $validated['username'],
            $password,
        );
        imap_close($stream);

        $payload = [
            'email' => mb_strtolower($validated['email'] ?? $validated['username']),
            'imap_username' => $validated['username'],
            'imap_host' => $validated['imap_host'],
            'imap_port' => (int) $validated['imap_port'],
            'imap_encryption' => $validated['imap_encryption'],
            'smtp_host' => config('mailbox.smtp.host'),
            'smtp_port' => config('mailbox.smtp.port'),
            'smtp_encryption' => config('mailbox.smtp.encryption'),
            'signature' => $this->normalizeSignature(
                $validated['signature'] ?? $this->defaultSignatureFor($user, mb_strtolower($validated['email'] ?? $validated['username'])),
            ),
            'last_connected_at' => now(),
        ];

        if (filled($validated['password'] ?? null)) {
            $payload['imap_password'] = $validated['password'];
        }

        $credential = MailboxCredential::query()->updateOrCreate(
            ['user_id' => $user->id],
            $payload,
        );

        return $this->success($this->settingsPayload($user, $credential), [], [
            'message' => 'Boite mail connectee avec succes.',
        ]);
    }

    public function messages(Request $request): JsonResponse
    {
        $credential = $this->requireCredential($request);
        $perPage = min(max((int) $request->query('per_page', config('mailbox.per_page', 15)), 5), 50);
        $page = max((int) $request->query('page', 1), 1);
        $search = trim((string) $request->query('search', ''));
        $searchScope = (string) $request->query('search_scope', 'all');
        $filter = (string) $request->query('filter', '');
        $folder = $this->mailboxFromRequest($request);

        $stream = $this->openCredentialConnection($credential, $folder);

        try {
            $uids = $this->searchMessageUids($stream, $search, $filter, $searchScope);
            rsort($uids, SORT_NUMERIC);

            $total = count($uids);
            $slice = array_slice($uids, ($page - 1) * $perPage, $perPage);
            $messages = collect($slice)
                ->map(fn (int $uid) => $this->overviewPayload($stream, $uid))
                ->filter()
                ->values()
                ->all();

            $unreadCount = count(imap_search($stream, 'UNSEEN', SE_UID) ?: []);

            return $this->success($messages, [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => max((int) ceil($total / $perPage), 1),
                'unread_count' => $unreadCount,
                'folder' => $folder,
                'filter' => $filter,
                'search_scope' => $searchScope,
            ]);
        } finally {
            imap_close($stream);
        }
    }

    public function folders(Request $request): JsonResponse
    {
        $credential = $this->requireCredential($request);
        $stream = $this->openCredentialConnection($credential);

        try {
            return $this->success($this->folderPayloads($stream, $credential));
        } finally {
            imap_close($stream);
        }
    }

    public function show(Request $request, int $uid): JsonResponse
    {
        $credential = $this->requireCredential($request);
        $folder = $this->mailboxFromRequest($request);
        $stream = $this->openCredentialConnection($credential, $folder);

        try {
            $overview = $this->overviewPayload($stream, $uid);
            if (!$overview) {
                return response()->json(['message' => 'Message introuvable.'], 404);
            }

            $structure = @imap_fetchstructure($stream, $uid, FT_UID);
            $body = $this->messageBody($stream, $uid, $structure);
            $headers = $this->headerPayload($stream, $uid);
            $attachments = $structure ? $this->attachmentPayloads($structure) : [];

            imap_setflag_full($stream, (string) $uid, '\\Seen', ST_UID);

            return $this->success(array_merge($overview, $headers, [
                'seen' => true,
                'unread' => false,
                'body' => $body,
                'attachments' => $attachments,
                'has_attachments' => count($attachments) > 0,
            ]));
        } finally {
            imap_close($stream);
        }
    }

    public function downloadAttachment(Request $request, int $uid, string $part)
    {
        $credential = $this->requireCredential($request);
        $folder = $this->mailboxFromRequest($request);
        $stream = $this->openCredentialConnection($credential, $folder);

        try {
            $structure = @imap_fetchstructure($stream, $uid, FT_UID);
            $attachmentPart = $structure ? $this->findAttachmentPart($structure, $part) : null;

            if (!$attachmentPart) {
                return response()->json(['message' => 'Piece jointe introuvable.'], 404);
            }

            $info = $this->attachmentInfo($attachmentPart, $part);
            if (!$info) {
                return response()->json(['message' => 'Piece jointe introuvable.'], 404);
            }

            $raw = @imap_fetchbody($stream, $uid, $part, FT_UID | FT_PEEK);
            if (($raw === false || $raw === '') && $part === '1') {
                $raw = @imap_body($stream, $uid, FT_UID | FT_PEEK);
            }

            $content = $this->decodeContent($raw ?: '', (int) ($attachmentPart->encoding ?? 0));
            $filename = $this->safeAttachmentFilename($info['name']);
            $fallbackName = preg_replace('/[^\x20-\x7E]/', '_', $filename) ?: 'piece-jointe';

            return response($content, 200, [
                'Content-Type' => $info['mime'] ?: 'application/octet-stream',
                'Content-Length' => (string) strlen($content),
                'Content-Disposition' => 'attachment; filename="'.$fallbackName.'"; filename*=UTF-8\'\''.rawurlencode($filename),
            ]);
        } finally {
            imap_close($stream);
        }
    }

    public function markRead(Request $request, int $uid): JsonResponse
    {
        $credential = $this->requireCredential($request);
        $validated = $request->validate([
            'seen' => ['sometimes', 'boolean'],
        ]);
        $seen = (bool) ($validated['seen'] ?? true);
        $folder = $this->mailboxFromRequest($request);

        $stream = $this->openCredentialConnection($credential, $folder);

        try {
            if ($seen) {
                imap_setflag_full($stream, (string) $uid, '\\Seen', ST_UID);
            } else {
                imap_clearflag_full($stream, (string) $uid, '\\Seen', ST_UID);
            }

            return $this->success([
                'uid' => $uid,
                'seen' => $seen,
                'unread' => !$seen,
            ]);
        } finally {
            imap_close($stream);
        }
    }

    public function flag(Request $request, int $uid): JsonResponse
    {
        $credential = $this->requireCredential($request);
        $validated = $request->validate([
            'flagged' => ['sometimes', 'boolean'],
        ]);
        $flagged = (bool) ($validated['flagged'] ?? true);
        $folder = $this->mailboxFromRequest($request);
        $stream = $this->openCredentialConnection($credential, $folder);

        try {
            if ($flagged) {
                imap_setflag_full($stream, (string) $uid, '\\Flagged', ST_UID);
            } else {
                imap_clearflag_full($stream, (string) $uid, '\\Flagged', ST_UID);
            }

            return $this->success([
                'uid' => $uid,
                'flagged' => $flagged,
            ]);
        } finally {
            imap_close($stream);
        }
    }

    public function move(Request $request, int $uid): JsonResponse
    {
        $credential = $this->requireCredential($request);
        $validated = $request->validate([
            'folder' => ['required', 'string', 'max:255'],
        ]);

        $sourceFolder = $this->mailboxFromRequest($request);
        $targetFolder = $this->sanitizeMailboxName($validated['folder']);
        $stream = $this->openCredentialConnection($credential, $sourceFolder);

        try {
            if (!@imap_mail_move($stream, (string) $uid, $targetFolder, CP_UID)) {
                $errors = imap_errors() ?: [];

                throw ValidationException::withMessages([
                    'folder' => [Arr::first($errors) ?: 'Deplacement du mail impossible.'],
                ]);
            }

            imap_expunge($stream);

            return $this->success([
                'uid' => $uid,
                'source_folder' => $sourceFolder,
                'target_folder' => $targetFolder,
            ], [], [
                'message' => 'Mail deplace.',
            ]);
        } finally {
            imap_close($stream);
        }
    }

    public function send(Request $request): JsonResponse
    {
        $credential = $this->requireCredential($request);
        $validated = $request->validate([
            'to' => ['required', 'array', 'min:1', 'max:25'],
            'to.*' => ['required', 'email:rfc'],
            'cc' => ['nullable', 'array', 'max:25'],
            'cc.*' => ['required', 'email:rfc'],
            'bcc' => ['nullable', 'array', 'max:25'],
            'bcc.*' => ['required', 'email:rfc'],
            'subject' => ['required', 'string', 'max:180'],
            'body' => ['required', 'string', 'max:10000'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'max:10240'],
        ]);

        $to = collect($validated['to'])->map(fn ($email) => mb_strtolower(trim($email)))->unique()->values();
        $cc = collect($validated['cc'] ?? [])->map(fn ($email) => mb_strtolower(trim($email)))->unique()->diff($to)->values();
        $bcc = collect($validated['bcc'] ?? [])->map(fn ($email) => mb_strtolower(trim($email)))->unique()->diff($to)->diff($cc)->values();
        $attachments = collect($request->file('attachments', []))->filter()->values();
        $senderName = $this->senderDisplayName($request->user());
        $mailBody = $this->bodyWithSignature(
            (string) $validated['body'],
            $credential->signature ?: $this->defaultSignatureFor($request->user(), $credential->email),
        );
        $htmlBody = $this->mailHtmlBody($mailBody);

        config([
            'mail.mailers.mailbox' => [
                'transport' => 'smtp',
                'host' => $credential->smtp_host ?: $credential->imap_host,
                'port' => $credential->smtp_port ?: 465,
                'encryption' => $credential->smtp_encryption === 'none'
                    ? null
                    : ($credential->smtp_encryption ?: 'ssl'),
                'username' => $credential->imap_username,
                'password' => $credential->imap_password,
                'timeout' => 30,
            ],
        ]);

        try {
            Mail::mailer('mailbox')->html($htmlBody, function ($message) use ($credential, $validated, $to, $cc, $bcc, $attachments, $senderName) {
                $message
                    ->from($credential->email, $senderName)
                    ->to($to->all())
                    ->subject($validated['subject']);

                if ($cc->isNotEmpty()) {
                    $message->cc($cc->all());
                }

                if ($bcc->isNotEmpty()) {
                    $message->bcc($bcc->all());
                }

                foreach ($attachments as $attachment) {
                    $message->attach($attachment->getRealPath(), [
                        'as' => $attachment->getClientOriginalName(),
                        'mime' => $attachment->getMimeType(),
                    ]);
                }
            });
        } catch (Throwable $exception) {
            report($exception);

            throw ValidationException::withMessages([
                'mailbox' => ['Envoi impossible avec ce compte mail. Verifiez le serveur SMTP ou le mot de passe.'],
            ]);
        }

        foreach ($to->merge($cc)->merge($bcc)->unique()->values() as $recipientEmail) {
            SentMailHistory::create([
                'sender_id' => $request->user()->id,
                'agent_id' => null,
                'recipient_name' => $recipientEmail,
                'recipient_email' => mb_strtolower($recipientEmail),
                'subject' => $validated['subject'],
                'body' => $mailBody,
                'attachment_name' => $this->attachmentHistoryLabel($attachments),
                'sent_at' => now(),
            ]);
        }

        return $this->success([
            'sent' => $to->count(),
            'cc' => $cc->count(),
            'bcc' => $bcc->count(),
            'attachments' => $attachments->count(),
        ], [], [
            'message' => 'Mail envoye avec succes.',
        ], 201);
    }

    private function attachmentHistoryLabel($attachments): ?string
    {
        $attachments = collect($attachments);

        if ($attachments->isEmpty()) {
            return null;
        }

        $firstName = (string) $attachments->first()->getClientOriginalName();

        if ($attachments->count() === 1) {
            return mb_strimwidth($firstName, 0, 180, '...');
        }

        return mb_strimwidth($firstName.' (+'.($attachments->count() - 1).')', 0, 180, '...');
    }

    public function destroy(Request $request, int $uid): JsonResponse
    {
        $credential = $this->requireCredential($request);
        $folder = $this->mailboxFromRequest($request);
        $stream = $this->openCredentialConnection($credential, $folder);

        try {
            imap_delete($stream, (string) $uid, FT_UID);
            imap_expunge($stream);

            return $this->success([
                'deleted' => true,
                'uid' => $uid,
            ], [], [
                'message' => 'Message supprime.',
            ]);
        } finally {
            imap_close($stream);
        }
    }

    private function settingsPayload(User $user, ?MailboxCredential $credential): array
    {
        $defaultEmail = $this->defaultEmailFor($user);
        $accountEmail = $credential?->email ?: $defaultEmail;
        $defaultSignature = $this->defaultSignatureFor($user, $accountEmail);

        return [
            'has_credentials' => (bool) $credential,
            'account_email' => $accountEmail,
            'username' => $credential?->imap_username ?: $defaultEmail,
            'sender_name' => $this->senderDisplayName($user),
            'signature' => $credential?->signature ?: $defaultSignature,
            'default_signature' => $defaultSignature,
            'imap' => [
                'host' => $credential?->imap_host ?: config('mailbox.imap.host'),
                'port' => $credential?->imap_port ?: config('mailbox.imap.port'),
                'encryption' => $credential?->imap_encryption ?: config('mailbox.imap.encryption'),
            ],
            'last_connected_at' => $credential?->last_connected_at?->toIso8601String(),
        ];
    }

    private function senderDisplayName(User $user): string
    {
        $user->loadMissing('agent');
        $agent = $user->agent;

        $agentName = trim(collect([
            $agent?->prenom,
            $agent?->nom,
            $agent?->postnom,
        ])->filter(fn ($part) => filled($part))->join(' '));

        if ($agentName !== '') {
            return mb_strimwidth($agentName, 0, 120, '...');
        }

        $userName = trim((string) $user->name);

        if ($userName !== '') {
            return mb_strimwidth($userName, 0, 120, '...');
        }

        return config('app.name', 'E-PNMLS');
    }

    private function defaultSignatureFor(User $user, ?string $accountEmail = null): string
    {
        $user->loadMissing(['agent', 'role']);
        $agent = $user->agent;
        $name = $this->senderDisplayName($user);
        $function = trim((string) ($agent?->fonction ?: $agent?->poste_actuel ?: $user->role?->nom_role));
        $workEmail = collect([
            $accountEmail,
            $agent?->email_professionnel,
            $user->email,
        ])->map(fn ($email) => mb_strtolower(trim((string) $email)))
            ->first(fn ($email) => $email !== '');
        $privateEmail = collect([
            $agent?->email_prive,
            $agent?->email,
            $user->email,
        ])->map(fn ($email) => mb_strtolower(trim((string) $email)))
            ->first(fn ($email) => $email !== '' && $email !== $workEmail);
        $phones = collect([
            $agent?->telephone_professionnel,
            $agent?->telephone,
            $agent?->telephone_prive,
        ])->map(fn ($phone) => trim((string) $phone))
            ->filter()
            ->unique()
            ->values();

        return $this->normalizeSignature(implode("\n", array_filter([
            'Cordialement,',
            '',
            $name,
            $function,
            $workEmail ? 'Email professionnel : '.$workEmail : null,
            $privateEmail ? 'Email personnel : '.$privateEmail : null,
            $phones->isNotEmpty() ? 'Telephone : '.$phones->join(' / ') : null,
        ], fn ($line) => $line !== null)));
    }

    private function normalizeSignature(?string $signature): string
    {
        $signature = str_replace(["\r\n", "\r"], "\n", (string) $signature);
        $signature = preg_replace('/\A-- ?\n/', '', trim($signature)) ?? trim($signature);
        $signature = preg_replace("/\n{3,}/", "\n\n", $signature) ?? $signature;

        return trim($signature);
    }

    private function bodyWithSignature(string $body, ?string $signature): string
    {
        $body = trim(str_replace(["\r\n", "\r"], "\n", $body));
        $signature = $this->normalizeSignature($signature);

        if ($signature === '' || preg_match('/(?:^|\n)-- ?\n/', $body)) {
            return $body;
        }

        return trim($body."\n\n-- \n".$signature);
    }

    private function mailHtmlBody(string $body): string
    {
        [$message, $signature] = $this->splitOutgoingSignature($body);
        $contentHtml = $this->plainTextToHtml($message);
        $signatureHtml = $this->signatureHtml($signature);

        if ($contentHtml === '') {
            $contentHtml = '<div style="min-height:18px"></div>';
        }

        return '<!doctype html><html><body style="margin:0;padding:0;background:#ffffff;">'
            . '<div style="font-family:Arial,Helvetica,sans-serif;font-size:14px;line-height:1.6;color:#1f2937;padding:4px 0;">'
            . $contentHtml
            . $signatureHtml
            . '</div></body></html>';
    }

    private function splitOutgoingSignature(string $body): array
    {
        $body = str_replace(["\r\n", "\r"], "\n", $body);

        if (preg_match('/(?:^|\n)-- ?\n/', $body, $match, PREG_OFFSET_CAPTURE)) {
            $start = $match[0][1];
            $delimiterLength = strlen($match[0][0]);

            return [
                trim(substr($body, 0, $start)),
                $this->normalizeSignature(substr($body, $start + $delimiterLength)),
            ];
        }

        return [trim($body), ''];
    }

    private function plainTextToHtml(string $text): string
    {
        $text = trim(str_replace(["\r\n", "\r"], "\n", $text));

        if ($text === '') {
            return '';
        }

        $blocks = preg_split("/\n{2,}/", $text) ?: [];

        return collect($blocks)
            ->map(function (string $block) {
                $lines = array_map(fn ($line) => $this->linkifiedText($line), explode("\n", trim($block)));

                return '<p style="margin:0 0 12px;">'.implode('<br>', $lines).'</p>';
            })
            ->join('');
    }

    private function signatureHtml(string $signature): string
    {
        $lines = collect(explode("\n", $this->normalizeSignature($signature)))
            ->map(fn ($line) => trim($line))
            ->filter(fn ($line) => $line !== '')
            ->values();

        if ($lines->isEmpty()) {
            return '';
        }

        $greeting = null;
        $first = mb_strtolower($lines->first());

        if (str_starts_with($first, 'cordialement') || str_starts_with($first, 'bien a vous')) {
            $greeting = $lines->shift();
        }

        $name = $lines->shift();
        $role = $lines->isNotEmpty() && ! $this->isSignatureContactLine($lines->first())
            ? $lines->shift()
            : null;

        $html = '<div style="margin-top:24px;padding-top:14px;border-top:1px solid #dbe8ef;max-width:560px;">';

        if ($greeting) {
            $html .= '<div style="margin-bottom:10px;color:#475569;">'.$this->linkifiedText($greeting).'</div>';
        }

        if ($name) {
            $html .= '<div style="font-size:15px;font-weight:700;color:#075985;">'.$this->linkifiedText($name).'</div>';
        }

        if ($role) {
            $html .= '<div style="margin-top:2px;color:#334155;font-weight:600;">'.$this->linkifiedText($role).'</div>';
        }

        foreach ($lines as $line) {
            $html .= '<div style="margin-top:3px;color:#64748b;font-size:13px;">'.$this->linkifiedText($line).'</div>';
        }

        return $html.'</div>';
    }

    private function isSignatureContactLine(string $line): bool
    {
        $line = mb_strtolower($line);

        return str_contains($line, '@')
            || str_contains($line, 'email')
            || str_contains($line, 'mail')
            || str_contains($line, 'tel')
            || str_contains($line, 'phone');
    }

    private function linkifiedText(string $text): string
    {
        $pattern = '/https?:\/\/[^\s<>()]+|www\.[^\s<>()]+|[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i';

        if (! preg_match_all($pattern, $text, $matches, PREG_OFFSET_CAPTURE)) {
            return e($text);
        }

        $html = '';
        $offset = 0;

        foreach ($matches[0] as [$match, $position]) {
            $html .= e(substr($text, $offset, $position - $offset));
            $tail = '';
            $value = $match;

            while ($value !== '' && preg_match('/[.,;:]$/', $value)) {
                $tail = substr($value, -1).$tail;
                $value = substr($value, 0, -1);
            }

            $href = str_contains($value, '@')
                ? 'mailto:'.$value
                : (str_starts_with(mb_strtolower($value), 'www.') ? 'https://'.$value : $value);

            $html .= '<a href="'.e($href).'" style="color:#0369a1;text-decoration:none;border-bottom:1px solid rgba(3,105,161,.28);">'.e($value).'</a>'.e($tail);
            $offset = $position + strlen($match);
        }

        return $html.e(substr($text, $offset));
    }

    private function defaultEmailFor(User $user): string
    {
        return $user->agent?->email_professionnel
            ?: $user->agent?->email
            ?: $user->email
            ?: '';
    }

    private function requireCredential(Request $request): MailboxCredential
    {
        $credential = MailboxCredential::query()
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$credential) {
            throw ValidationException::withMessages([
                'mailbox' => ['Connectez d abord votre boite mail professionnelle.'],
            ]);
        }

        return $credential;
    }

    private function mailboxFromRequest(Request $request): string
    {
        $folder = $request->query('folder', $request->input('source_folder', 'INBOX'));

        return $this->sanitizeMailboxName((string) $folder);
    }

    private function sanitizeMailboxName(string $folder): string
    {
        $folder = trim($folder) ?: 'INBOX';

        if (strlen($folder) > 255 || str_contains($folder, "\0") || str_contains($folder, "\r") || str_contains($folder, "\n") || str_contains($folder, '{') || str_contains($folder, '}')) {
            throw ValidationException::withMessages([
                'folder' => ['Dossier mail invalide.'],
            ]);
        }

        return $folder;
    }

    private function openCredentialConnection(MailboxCredential $credential, string $folder = 'INBOX')
    {
        return $this->openConnectionFor(
            $credential->imap_host,
            (int) $credential->imap_port,
            $credential->imap_encryption,
            $credential->imap_username,
            $credential->imap_password,
            $folder,
        );
    }

    private function openConnectionFor(string $host, int $port, string $encryption, string $username, string $password, string $folder = 'INBOX')
    {
        if (!function_exists('imap_open')) {
            throw ValidationException::withMessages([
                'mailbox' => ['Le module IMAP PHP n est pas active sur ce serveur.'],
            ]);
        }

        imap_errors();
        imap_alerts();

        $mailbox = $this->mailboxPath($host, $port, $encryption, $this->sanitizeMailboxName($folder));
        $stream = @imap_open($mailbox, $username, $password, 0, 1, [
            'DISABLE_AUTHENTICATOR' => 'GSSAPI',
        ]);

        if (!$stream) {
            $errors = imap_errors() ?: [];
            throw ValidationException::withMessages([
                'mailbox' => [Arr::first($errors) ?: 'Connexion a la boite mail impossible.'],
            ]);
        }

        return $stream;
    }

    private function mailboxRoot(MailboxCredential $credential): string
    {
        return $this->mailboxPath(
            $credential->imap_host,
            (int) $credential->imap_port,
            $credential->imap_encryption,
            '',
        );
    }

    private function mailboxPath(string $host, int $port, string $encryption, string $folder): string
    {
        return sprintf('{%s:%d/imap%s}%s', $host, $port, $this->imapFlags($encryption), $folder);
    }

    private function imapFlags(string $encryption): string
    {
        return match ($encryption) {
            'tls' => '/tls',
            'none' => '/notls',
            default => '/ssl',
        };
    }

    private function folderPayloads($stream, MailboxCredential $credential): array
    {
        $root = $this->mailboxRoot($credential);
        $mailboxes = @imap_getmailboxes($stream, $root, '*') ?: [];
        $folders = [];

        foreach ($mailboxes as $mailbox) {
            $rawName = str_starts_with($mailbox->name, $root)
                ? substr($mailbox->name, strlen($root))
                : $mailbox->name;

            $rawName = trim((string) $rawName);
            if ($rawName === '') {
                continue;
            }

            $decodedName = $this->decodeFolderName($rawName);
            $status = @imap_status($stream, $root . $rawName, SA_MESSAGES | SA_UNSEEN);
            $type = $this->specialFolderType($decodedName);

            $folders[] = [
                'name' => $rawName,
                'label' => $this->friendlyFolderLabel($decodedName, $type),
                'type' => $type,
                'total' => (int) ($status->messages ?? 0),
                'unread' => (int) ($status->unseen ?? 0),
            ];
        }

        if (!collect($folders)->contains(fn (array $folder) => $folder['type'] === 'inbox')) {
            array_unshift($folders, [
                'name' => 'INBOX',
                'label' => 'Boite de reception',
                'type' => 'inbox',
                'total' => 0,
                'unread' => 0,
            ]);
        }

        return collect($folders)
            ->unique('name')
            ->sortBy(fn (array $folder) => sprintf('%02d-%s', $this->folderSortWeight($folder['type']), $folder['label']))
            ->values()
            ->all();
    }

    private function specialFolderType(string $name): string
    {
        $normalized = mb_strtolower($name);

        return match (true) {
            $normalized === 'inbox' || str_ends_with($normalized, '.inbox') || str_ends_with($normalized, '/inbox') => 'inbox',
            str_contains($normalized, 'sent') || str_contains($normalized, 'envoy') => 'sent',
            str_contains($normalized, 'draft') || str_contains($normalized, 'brouillon') => 'drafts',
            str_contains($normalized, 'archive') => 'archive',
            str_contains($normalized, 'junk') || str_contains($normalized, 'spam') || str_contains($normalized, 'indesirable') => 'junk',
            str_contains($normalized, 'trash') || str_contains($normalized, 'deleted') || str_contains($normalized, 'corbeille') => 'trash',
            default => 'folder',
        };
    }

    private function folderSortWeight(string $type): int
    {
        return [
            'inbox' => 0,
            'sent' => 1,
            'drafts' => 2,
            'archive' => 3,
            'junk' => 4,
            'trash' => 5,
            'folder' => 10,
        ][$type] ?? 10;
    }

    private function friendlyFolderLabel(string $name, string $type): string
    {
        if ($type !== 'folder') {
            return [
                'inbox' => 'Boite de reception',
                'sent' => 'Envoyes',
                'drafts' => 'Brouillons',
                'archive' => 'Archives',
                'junk' => 'Indesirables',
                'trash' => 'Corbeille',
            ][$type];
        }

        $cleaned = preg_replace('/^INBOX[.\/]/i', '', $name) ?: $name;
        $parts = preg_split('/[.\/]/', $cleaned);

        return trim((string) end($parts)) ?: $name;
    }

    private function decodeFolderName(string $name): string
    {
        if (function_exists('imap_utf7_decode')) {
            $decoded = @imap_utf7_decode($name);
            if (is_string($decoded) && $decoded !== '') {
                return $decoded;
            }
        }

        return $name;
    }

    private function searchMessageUids($stream, string $search, string $filter = '', string $searchScope = 'all'): array
    {
        $criteria = match ($filter) {
            'unread' => 'UNSEEN',
            'flagged' => 'FLAGGED',
            'answered' => 'ANSWERED',
            default => 'ALL',
        };

        if ($search !== '') {
            $term = str_replace(['\\', '"'], ['\\\\', '\"'], $search);
            $searchCriteria = match ($searchScope) {
                'subject' => 'SUBJECT "' . $term . '"',
                'from' => 'FROM "' . $term . '"',
                'recipient' => 'OR TO "' . $term . '" CC "' . $term . '"',
                default => 'TEXT "' . $term . '"',
            };

            $criteria = trim(($criteria === 'ALL' ? '' : $criteria) . ' ' . $searchCriteria);
        }

        $uids = imap_search($stream, $criteria, SE_UID, 'UTF-8');

        return $uids ?: [];
    }

    private function overviewPayload($stream, int $uid): ?array
    {
        $overview = imap_fetch_overview($stream, (string) $uid, FT_UID);
        $item = $overview[0] ?? null;

        if (!$item) {
            return null;
        }

        $date = null;
        if (!empty($item->date)) {
            try {
                $date = Carbon::parse($item->date)->toIso8601String();
            } catch (Throwable) {
                $date = $item->date;
            }
        }

        return [
            'uid' => (int) ($item->uid ?? $uid),
            'message_id' => $item->message_id ?? null,
            'subject' => $this->decodeMimeHeader($item->subject ?? '(Sans objet)') ?: '(Sans objet)',
            'from' => $this->decodeMimeHeader($item->from ?? ''),
            'to' => $this->decodeMimeHeader($item->to ?? ''),
            'cc' => $this->decodeMimeHeader($item->cc ?? ''),
            'date' => $date,
            'size' => (int) ($item->size ?? 0),
            'seen' => (bool) ($item->seen ?? false),
            'unread' => !(bool) ($item->seen ?? false),
            'answered' => (bool) ($item->answered ?? false),
            'flagged' => (bool) ($item->flagged ?? false),
            'has_attachments' => $this->messageHasAttachments(@imap_fetchstructure($stream, $uid, FT_UID) ?: null),
        ];
    }

    private function headerPayload($stream, int $uid): array
    {
        $messageNumber = @imap_msgno($stream, $uid);
        if (!$messageNumber) {
            return [];
        }

        $header = @imap_headerinfo($stream, $messageNumber);
        if (!$header) {
            return [];
        }

        $from = $this->addressListPayload($header->from ?? []);
        $to = $this->addressListPayload($header->to ?? []);
        $cc = $this->addressListPayload($header->cc ?? []);
        $bcc = $this->addressListPayload($header->bcc ?? []);
        $replyTo = $this->addressListPayload($header->reply_to ?? []);

        $payload = [
            'from_addresses' => $from,
            'to_addresses' => $to,
            'cc_addresses' => $cc,
            'bcc_addresses' => $bcc,
            'reply_to_addresses' => $replyTo,
        ];

        foreach ([
            'from' => $from,
            'to' => $to,
            'cc' => $cc,
            'bcc' => $bcc,
            'reply_to' => $replyTo,
        ] as $key => $addresses) {
            $line = $this->formatAddressList($addresses);
            if ($line !== '') {
                $payload[$key] = $line;
            }
        }

        return $payload;
    }

    private function addressListPayload($addresses): array
    {
        if (!is_array($addresses)) {
            return [];
        }

        return collect($addresses)
            ->map(function ($address) {
                $mailbox = trim((string) ($address->mailbox ?? ''));
                $host = trim((string) ($address->host ?? ''));
                $email = $host !== '' ? $mailbox.'@'.$host : $mailbox;
                $email = mb_strtolower(trim(str_replace(' ', '', $email)));

                if ($email === '' || !str_contains($email, '@')) {
                    return null;
                }

                $name = $this->decodeMimeHeader($address->personal ?? '');
                $label = $name !== '' ? $name.' <'.$email.'>' : $email;

                return [
                    'name' => $name,
                    'email' => $email,
                    'label' => $label,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    private function formatAddressList(array $addresses): string
    {
        return collect($addresses)
            ->map(fn (array $address) => $address['label'] ?? $address['email'] ?? '')
            ->filter()
            ->implode(', ');
    }

    private function messageHasAttachments(?object $structure): bool
    {
        if (!$structure) {
            return false;
        }

        return count($this->attachmentPayloads($structure)) > 0;
    }

    private function attachmentPayloads(object $part, string $partNumber = ''): array
    {
        $section = $partNumber !== '' ? $partNumber : '1';
        $attachments = [];
        $info = $this->attachmentInfo($part, $section);

        if ($info) {
            $attachments[] = $info;
        }

        foreach (($part->parts ?? []) as $index => $childPart) {
            $nextPartNumber = $partNumber === ''
                ? (string) ($index + 1)
                : $partNumber.'.'.($index + 1);

            $attachments = array_merge($attachments, $this->attachmentPayloads($childPart, $nextPartNumber));
        }

        return $attachments;
    }

    private function findAttachmentPart(object $part, string $targetPart, string $partNumber = ''): ?object
    {
        $section = $partNumber !== '' ? $partNumber : '1';

        if ($section === $targetPart && $this->attachmentInfo($part, $section)) {
            return $part;
        }

        foreach (($part->parts ?? []) as $index => $childPart) {
            $nextPartNumber = $partNumber === ''
                ? (string) ($index + 1)
                : $partNumber.'.'.($index + 1);
            $found = $this->findAttachmentPart($childPart, $targetPart, $nextPartNumber);

            if ($found) {
                return $found;
            }
        }

        return null;
    }

    private function attachmentInfo(object $part, string $partNumber): ?array
    {
        if ((int) ($part->type ?? -1) === 1) {
            return null;
        }

        $attributes = $this->partAttributes($part);
        $filename = $attributes['filename*']
            ?? $attributes['filename']
            ?? $attributes['name*']
            ?? $attributes['name']
            ?? '';
        $disposition = strtolower((string) ($part->disposition ?? ''));

        if ($filename === '' && $disposition !== 'attachment') {
            return null;
        }

        $name = $filename !== ''
            ? $this->decodePartFilename($filename)
            : 'piece-jointe-'.$partNumber;

        return [
            'part' => $partNumber,
            'name' => $name ?: 'piece-jointe-'.$partNumber,
            'size' => (int) ($part->bytes ?? 0),
            'mime' => $this->partMimeType($part),
            'disposition' => $disposition ?: 'attachment',
        ];
    }

    private function partAttributes(object $part): array
    {
        $attributes = [];
        $segments = [];

        foreach (['parameters', 'dparameters'] as $parameterSet) {
            foreach (($part->{$parameterSet} ?? []) as $parameter) {
                $attribute = strtolower((string) ($parameter->attribute ?? ''));
                $value = (string) ($parameter->value ?? '');

                if ($attribute === '') {
                    continue;
                }

                if (preg_match('/^([^*]+)\*(\d+)\*?$/', $attribute, $matches)) {
                    $segments[$matches[1]][(int) $matches[2]] = $value;
                    continue;
                }

                $attributes[$attribute] = $value;
            }
        }

        foreach ($segments as $base => $values) {
            ksort($values);
            $attributes[$base.'*'] = implode('', $values);
        }

        return $attributes;
    }

    private function decodePartFilename(string $filename): string
    {
        $filename = trim($filename, "\"' \t\r\n");

        if (preg_match("/^([^']*)'[^']*'(.*)$/", $filename, $matches)) {
            $charset = $matches[1] ?: 'UTF-8';
            return $this->convertToUtf8(rawurldecode($matches[2]), $charset);
        }

        $decoded = $this->decodeMimeHeader($filename);

        return $decoded !== '' ? $decoded : rawurldecode($filename);
    }

    private function partMimeType(object $part): string
    {
        $primaryTypes = [
            0 => 'text',
            1 => 'multipart',
            2 => 'message',
            3 => 'application',
            4 => 'audio',
            5 => 'image',
            6 => 'video',
            7 => 'application',
        ];

        $type = (int) ($part->type ?? 3);
        $primary = $primaryTypes[$type] ?? 'application';
        $subtype = strtolower((string) ($part->subtype ?? 'octet-stream')) ?: 'octet-stream';

        return $primary.'/'.$subtype;
    }

    private function safeAttachmentFilename(string $filename): string
    {
        $filename = trim(preg_replace('/[\/\\\\\x00-\x1F\x7F]+/', '-', $filename) ?: '');
        $filename = str_replace('"', "'", $filename);

        return mb_strimwidth($filename ?: 'piece-jointe', 0, 180, '...');
    }

    private function messageBody($stream, int $uid, ?object $structure = null): string
    {
        $structure ??= @imap_fetchstructure($stream, $uid, FT_UID);
        $bodies = [
            'plain' => [],
            'html' => [],
        ];

        if ($structure) {
            $this->collectBodyParts($stream, $uid, $structure, '', $bodies);
        }

        $html = trim(implode("\n", $bodies['html']));
        $plain = trim(implode("\n", $bodies['plain']));

        if ($plain !== '') {
            return $plain;
        }

        if ($html !== '') {
            return $this->htmlToText($html);
        }

        $raw = imap_body($stream, $uid, FT_UID | FT_PEEK);

        return trim($this->decodeContent($raw ?: '', (int) ($structure->encoding ?? 0)));
    }

    private function collectBodyParts($stream, int $uid, object $part, string $partNumber, array &$bodies): void
    {
        $isText = (int) ($part->type ?? -1) === 0;
        $subtype = strtolower((string) ($part->subtype ?? ''));
        $section = $partNumber !== '' ? $partNumber : '1';

        if ($isText && in_array($subtype, ['plain', 'html'], true) && !$this->attachmentInfo($part, $section)) {
            $raw = imap_fetchbody($stream, $uid, $section, FT_UID | FT_PEEK);

            if ($raw === false || $raw === '') {
                $raw = imap_body($stream, $uid, FT_UID | FT_PEEK);
            }

            $decoded = $this->decodeContent($raw ?: '', (int) ($part->encoding ?? 0));
            $decoded = $this->convertToUtf8($decoded, $this->partCharset($part));
            $bodies[$subtype][] = $decoded;
        }

        foreach (($part->parts ?? []) as $index => $childPart) {
            $nextPartNumber = $partNumber === ''
                ? (string) ($index + 1)
                : $partNumber . '.' . ($index + 1);

            $this->collectBodyParts($stream, $uid, $childPart, $nextPartNumber, $bodies);
        }
    }

    private function partCharset(object $part): ?string
    {
        foreach (['parameters', 'dparameters'] as $parameterSet) {
            foreach (($part->{$parameterSet} ?? []) as $parameter) {
                if (strtolower((string) ($parameter->attribute ?? '')) === 'charset') {
                    return (string) ($parameter->value ?? '');
                }
            }
        }

        return null;
    }

    private function decodeContent(string $content, int $encoding): string
    {
        return match ($encoding) {
            3 => base64_decode($content, true) ?: '',
            4 => quoted_printable_decode($content),
            default => $content,
        };
    }

    private function decodeMimeHeader(?string $value): string
    {
        if (!$value) {
            return '';
        }

        $decoded = @imap_mime_header_decode($value);
        if (!$decoded) {
            return trim($value);
        }

        $chunks = [];
        foreach ($decoded as $part) {
            $charset = (string) ($part->charset ?? '');
            $chunks[] = $this->convertToUtf8((string) ($part->text ?? ''), $charset === 'default' ? null : $charset);
        }

        return trim(preg_replace('/\s+/', ' ', implode('', $chunks)));
    }

    private function convertToUtf8(string $content, ?string $charset): string
    {
        $charset = $charset ? strtoupper($charset) : 'UTF-8';

        if (in_array($charset, ['UTF-8', 'US-ASCII', 'ASCII'], true)) {
            return mb_convert_encoding($content, 'UTF-8', 'UTF-8');
        }

        try {
            return mb_convert_encoding($content, 'UTF-8', $charset);
        } catch (Throwable) {
            return mb_convert_encoding($content, 'UTF-8', 'UTF-8');
        }
    }

    private function htmlToText(string $html): string
    {
        $html = preg_replace_callback('/<a\b[^>]*href=["\']([^"\']+)["\'][^>]*>(.*?)<\/a>/is', function (array $matches) {
            $label = trim(html_entity_decode(strip_tags($matches[2]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            $href = trim(html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));

            if ($href === '') {
                return $label;
            }

            if ($label === '' || $this->normalizeUrlForDisplay($label) === $this->normalizeUrlForDisplay($href)) {
                return $href;
            }

            return $label . ' (' . $href . ')';
        }, $html) ?? $html;
        $html = preg_replace('/<(br|p|div|li|tr|h[1-6])\b[^>]*>/i', "\n", $html) ?? $html;
        $html = preg_replace('/<style\b[^>]*>.*?<\/style>/is', '', $html) ?? $html;
        $html = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $html) ?? $html;

        return trim(html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    private function normalizeUrlForDisplay(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = preg_replace('/^https?:\/\//', '', $value) ?? $value;
        $value = preg_replace('/^www\./', '', $value) ?? $value;

        return rtrim($value, '/');
    }
}
