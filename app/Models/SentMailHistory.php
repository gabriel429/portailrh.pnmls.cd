<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SentMailHistory extends Model
{
    protected $fillable = [
        'sender_id',
        'agent_id',
        'recipient_name',
        'recipient_email',
        'subject',
        'body',
        'attachment_name',
        'attachment_path',
        'sent_at',
        'inbound_uid',
        'response_from_email',
        'response_subject',
        'response_body_preview',
        'response_received_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'response_received_at' => 'datetime',
    ];

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }
}
