<?php

namespace App\Listeners;

use App\Events\TrainingCreated;
use App\Models\Notification;
use App\Services\NotificationRecipients;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendTrainingCreatedNotification implements ShouldQueue
{
    public function __construct(private NotificationRecipients $recipients) {}

    public function handle(TrainingCreated $event): void
    {
        Notification::sendMany(
            $this->recipients->allParticipants(),
            "New training available: {$event->training->courseName}.",
            'training.created',
            route('trainings.show', $event->training->getKey()),
        );
    }
}
