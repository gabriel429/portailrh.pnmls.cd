<?php

namespace App\Events;

use App\Models\ActivitePlan;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PtaModified
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public ActivitePlan $activitePlan,
        public string $changeType, // 'created' | 'updated' | 'status_changed'
    ) {
    }
}
