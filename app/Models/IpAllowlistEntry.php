<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IpAllowlistEntry extends Model
{
    protected $table = 'ip_allowlist';

    protected $fillable = [
        'ip_address',
        'label',
        'created_by',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
