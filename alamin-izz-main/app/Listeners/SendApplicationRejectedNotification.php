<?php

namespace App\Listeners;

use App\Events\ApplicationRejected;
use App\Models\Notification;
use App\Services\NotificationRecipients;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendApplicationRejectedNotification implements ShouldQueue
{
    public function __construct(private NotificationRecipients $recipients) {}

    public function handle(ApplicationRejected $event): void
    {
        $event->application->loadMissing('training');

        Notification::send(
            $this->recipients->teacher($event->application->teacherID),
            "Your application for {$event->application->training->courseName} was rejected.",
            'application.rejected',
            route('applications.mine'),
        );
    }
}
