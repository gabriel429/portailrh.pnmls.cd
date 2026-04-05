<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormationBeneficiaire extends Model
{
    protected $table = 'formation_beneficiaires';

    protected $fillable = [
        'formation_id',
        'agent_id',
        'statut',
        'note_evaluation',
        'certificat',
    ];

    public function formation(): BelongsTo
    {
        return $this->belongsTo(Formation::class);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }
}
