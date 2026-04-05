<?php

namespace App\Events;

use App\Models\Tache;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TacheAssigned
{
    use Dispatchable, SerializesModels;

    public function __construct(public Tache $tache)
    {
    }
}
