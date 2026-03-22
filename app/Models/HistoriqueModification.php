<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use App\Traits\Syncable;

class HistoriqueModification extends Model
{
    use Syncable;

    protected $table = 'historique_modifications';

    protected $fillable = [
        'agent_id',
        'modifie_par',
        'table_name',
        'record_id',
        'action',
        'donnees_avant',
        'donnees_apres',
        'raison',
    ];

    protected $casts = [
        'donnees_avant' => 'json',
        'donnees_apres' => 'json',
    ];

    // Relations BelongsTo
    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function modifie_par_agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'modifie_par');
    }

    // Scopes
    public function scopeByAction($query, $action)
    {
        return $query->where('action', $action);
    }

    public function scopeByTable($query, $tableName)
    {
        return $query->where('table_name', $tableName);
    }

    public function scopeByAgent($query, $agentId)
    {
        return $query->where('agent_id', $agentId);
    }

    public function scopeByModifiedBy($query, $userId)
    {
        return $query->where('modifie_par', $userId);
    }
}
