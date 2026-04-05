<?php

namespace App\Events;

use App\Models\Holiday;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CongeApproved
{
    use Dispatchable, SerializesModels;

    public function __construct(public Holiday $holiday)
    {
    }
}
