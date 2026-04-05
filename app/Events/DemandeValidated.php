<?php

namespace App\Events;

use App\Models\Request as RequestModel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DemandeValidated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public RequestModel $demande,
        public string $step,
        public string $action, // 'approved' | 'rejected'
    ) {
    }
}
