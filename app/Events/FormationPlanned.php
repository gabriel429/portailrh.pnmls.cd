<?php

namespace App\Events;

use App\Models\Formation;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FormationPlanned
{
    use Dispatchable, SerializesModels;

    public function __construct(public Formation $formation)
    {
    }
}
