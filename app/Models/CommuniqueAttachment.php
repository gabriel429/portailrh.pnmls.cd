<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class CommuniqueAttachment extends Model
{
    protected $fillable = [
        'communique_id',
        'disk',
        'path',
        'original_name',
        'mime_type',
        'size',
    ];

    protected static function booted(): void
    {
        static::deleting(function (CommuniqueAttachment $attachment) {
            if ($attachment->path) {
                Storage::disk($attachment->disk ?: 'public')->delete($attachment->path);
            }
        });
    }

    public function communique(): BelongsTo
    {
        return $this->belongsTo(Communique::class);
    }
}
