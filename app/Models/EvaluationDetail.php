<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EvaluationDetail extends Model
{
    protected $fillable = [
        'evaluation_id',
        'evaluation_critere_id',
        'note',
        'poids_utilise',
        'commentaire',
    ];

    protected $casts = [
        'note' => 'float',
        'poids_utilise' => 'float',
    ];

    public function evaluation(): BelongsTo
    {
        return $this->belongsTo(Evaluation::class);
    }

    public function critere(): BelongsTo
    {
        return $this->belongsTo(EvaluationCritere::class, 'evaluation_critere_id');
    }
}
