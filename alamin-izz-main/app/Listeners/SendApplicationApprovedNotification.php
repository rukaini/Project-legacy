<?php

namespace App\Listeners;

use App\Events\ApplicationApproved;
use App\Models\Notification;
use App\Services\NotificationRecipients;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendApplicationApprovedNotification implements ShouldQueue
{
    public function __construct(private NotificationRecipients $recipients) {}

    public function handle(ApplicationApproved $event): void
    {
        $event->application->loadMissing('training');

        Notification::send(
            $this->recipients->teacher($event->application->teacherID),
            "Your application for {$event->application->training->courseName} has been approved.",
            'application.approved',
            route('trainings.show', $event->application->courseID),
        );
    }
}
