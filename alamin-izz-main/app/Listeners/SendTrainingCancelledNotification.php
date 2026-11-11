<?php

namespace App\Listeners;

use App\Events\TrainingCancelled;
use App\Models\Notification;
use App\Services\NotificationRecipients;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendTrainingCancelledNotification implements ShouldQueue
{
    public function __construct(private NotificationRecipients $recipients) {}

    public function handle(TrainingCancelled $event): void
    {
        Notification::sendMany(
            $this->recipients->enrolledIn($event->training->getKey(), ['pending', 'approved']),
            "Training '{$event->training->courseName}' has been cancelled.",
            'training.cancelled',
            route('trainings.show', $event->training->getKey()),
        );
    }
}
