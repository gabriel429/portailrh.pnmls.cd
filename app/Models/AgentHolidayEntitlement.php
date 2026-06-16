<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentHolidayEntitlement extends Model
{
    protected $fillable = [
        'agent_id',
        'annee',
        'jours_autorises',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'annee' => 'integer',
        'jours_autorises' => 'integer',
    ];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'updated_by');
    }
}
