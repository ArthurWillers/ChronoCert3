<?php

namespace App\Actions\Audit;

use Spatie\Activitylog\Actions\CleanActivityLogAction;

class PreserveActivityLogAction extends CleanActivityLogAction
{
    /**
     * Keep the institutional audit trail immutable.
     */
    public function execute(int $maxAgeInDays, ?string $logName = null): int
    {
        return 0;
    }
}
