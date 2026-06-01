<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MailboxSentMessage extends Model
{
    protected $fillable = [
        'user_id',
        'account_email',
        'sender_name',
        'to',
        'cc',
        'bcc',
        'subject',
        'body',
        'html_body',
        'attachments',
        'sent_at',
        'imap_synced',
        'imap_folder',
        'imap_sync_error',
        'imap_synced_at',
    ];

    protected $casts = [
        'to' => 'array',
        'cc' => 'array',
        'bcc' => 'array',
        'attachments' => 'array',
        'sent_at' => 'datetime',
        'imap_synced' => 'boolean',
        'imap_synced_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
