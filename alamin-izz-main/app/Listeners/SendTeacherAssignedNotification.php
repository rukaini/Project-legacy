<?php

namespace App\Listeners;

use App\Events\TeacherAssigned;
use App\Models\Notification;
use App\Services\NotificationRecipients;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendTeacherAssignedNotification implements ShouldQueue
{
    public function __construct(private NotificationRecipients $recipients) {}

    public function handle(TeacherAssigned $event): void
    {
        $event->application->loadMissing('training');

        Notification::send(
            $this->recipients->teacher($event->application->teacherID),
            "You've been assigned to {$event->application->training->courseName}.",
            'teacher.assigned',
            route('trainings.show', $event->application->courseID),
        );
    }
}
