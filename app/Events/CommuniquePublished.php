<?php

namespace App\Events;

use App\Models\Communique;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CommuniquePublished
{
    use Dispatchable, SerializesModels;

    public function __construct(public Communique $communique)
    {
    }
}
