<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SendDeadlineReminders extends Command
{
    protected $signature = 'edutrain:send-deadline-reminders';
    protected $description = 'Send 24-hour deadline reminders.';

    public function handle(): int
    {
        $this->info('No reminders configured.');
        return self::SUCCESS;
    }
}
