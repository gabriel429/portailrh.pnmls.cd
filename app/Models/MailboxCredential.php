<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MailboxCredential extends Model
{
    protected $fillable = [
        'user_id',
        'email',
        'imap_username',
        'imap_password',
        'imap_host',
        'imap_port',
        'imap_encryption',
        'smtp_host',
        'smtp_port',
        'smtp_encryption',
        'last_connected_at',
    ];

    protected $hidden = [
        'imap_password',
    ];

    protected function casts(): array
    {
        return [
            'imap_username' => 'encrypted',
            'imap_password' => 'encrypted',
            'imap_port' => 'integer',
            'smtp_port' => 'integer',
            'last_connected_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
