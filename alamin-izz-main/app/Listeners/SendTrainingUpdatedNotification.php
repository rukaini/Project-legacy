<?php

namespace App\Listeners;

use App\Events\TrainingUpdated;
use App\Models\Notification;
use App\Services\NotificationRecipients;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendTrainingUpdatedNotification implements ShouldQueue
{
    public function __construct(private NotificationRecipients $recipients) {}

    public function handle(TrainingUpdated $event): void
    {
        Notification::sendMany(
            $this->recipients->enrolledIn($event->training->getKey()),
            "Training '{$event->training->courseName}' details have changed.",
            'training.updated',
            route('trainings.show', $event->training->getKey()),
        );
    }
}
