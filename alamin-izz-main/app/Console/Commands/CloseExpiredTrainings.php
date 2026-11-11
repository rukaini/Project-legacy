<?php

namespace App\Console\Commands;

use App\Services\TrainingService;
use Illuminate\Console\Command;

class CloseExpiredTrainings extends Command
{
    protected $signature   = 'edutrain:close-expired-trainings';
    protected $description = 'Auto-refresh course statuses based on course session dates.';

    public function handle(TrainingService $service): int
    {
        $service->syncStatuses();

        $this->info('Course statuses refreshed.');

        return self::SUCCESS;
    }
}
