<?php

namespace App\Events;

use App\Models\Signalement;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SignalementCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(public Signalement $signalement)
    {
    }
}
