<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HolidayModificationRequest extends Model
{
    protected $fillable = [
        'holiday_id',
        'date_debut_proposee',
        'date_fin_proposee',
        'nombre_jours_proposes',
        'motif',
        'statut',
        'requested_by',
        'reviewed_by',
        'decision_comment',
        'reviewed_at',
    ];

    protected $casts = [
        'date_debut_proposee' => 'date',
        'date_fin_proposee' => 'date',
        'reviewed_at' => 'datetime',
    ];

    public function holiday(): BelongsTo
    {
        return $this->belongsTo(Holiday::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'requested_by');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'reviewed_by');
    }
}