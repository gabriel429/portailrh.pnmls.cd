<?php

namespace App\Events;

use App\Models\Holiday;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CongeRequested
{
    use Dispatchable, SerializesModels;

    public function __construct(public Holiday $holiday)
    {
    }
}
