<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CongeConflit extends Model
{
    protected $table = 'conges_conflits';

    protected $fillable = [
        'holiday_id',
        'activite_plan_id',
        'type_conflit',
        'description',
        'resolue',
        'resolue_par',
        'resolue_le',
    ];

    protected $casts = [
        'resolue'    => 'boolean',
        'resolue_le' => 'datetime',
    ];

    public function holiday(): BelongsTo
    {
        return $this->belongsTo(Holiday::class);
    }

    public function activitePlan(): BelongsTo
    {
        return $this->belongsTo(ActivitePlan::class);
    }

    public function resoluPar(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'resolue_par');
    }
}
