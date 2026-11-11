<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SyncStaffCommand extends Command
{
    protected $signature = 'edutrain:sync-staff';
    protected $description = 'Sync teacher and principal data from the external system (stub).';

    public function handle(): int
    {
        $this->info('External integration not configured.');

        return self::SUCCESS;
    }
}
