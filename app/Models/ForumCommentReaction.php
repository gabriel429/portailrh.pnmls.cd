<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ForumCommentReaction extends Model
{
    public const LIKE = 'like';
    public const DISLIKE = 'dislike';

    protected $fillable = [
        'forum_comment_id',
        'user_id',
        'reaction',
    ];

    public function comment(): BelongsTo
    {
        return $this->belongsTo(ForumComment::class, 'forum_comment_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
