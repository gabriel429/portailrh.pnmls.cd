<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

use App\Traits\Syncable;

class TacheCommentaire extends Model
{
    use Syncable;

    protected $table = 'tache_commentaires';

    protected $fillable = [
        'tache_id',
        'agent_id',
        'contenu',
        'type_commentaire',
        'ancien_statut',
        'nouveau_statut',
    ];

    public function tache(): BelongsTo
    {
        return $this->belongsTo(Tache::class);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(TacheDocument::class);
    }
}
