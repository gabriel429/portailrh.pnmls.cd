<?php

namespace App\Events;

use App\Models\Request as RequestModel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DemandeCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(public RequestModel $demande)
    {
    }
}
