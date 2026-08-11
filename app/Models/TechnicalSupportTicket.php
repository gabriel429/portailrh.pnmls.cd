<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TechnicalSupportTicket extends Model
{
    use HasFactory;

    public const PRIORITIES = ['faible', 'normal', 'urgent'];
    public const STATUSES = ['nouveau', 'en_cours', 'resolu', 'ferme'];

    protected $fillable = [
        'requester_user_id',
        'requester_name',
        'requester_email',
        'requester_phone',
        'requester_ip',
        'requester_user_agent',
        'subject',
        'description',
        'module',
        'priority',
        'status',
        'attachment_disk',
        'attachment_path',
        'attachment_name',
        'attachment_mime',
        'attachment_size',
        'resolved_at',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'resolved_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_user_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(TechnicalSupportMessage::class, 'ticket_id')->oldest();
    }
}
