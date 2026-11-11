<?php

namespace App\Listeners;

use App\Models\Application;
use App\Models\CourseSession;
use App\Models\Notification;
use App\Models\Training;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendAttendanceReminderNotification implements ShouldQueue
{
    public function handle(object $event): void
    {
        // No-op: attendance reminders are now handled per course_session QR activation.
    }
}
