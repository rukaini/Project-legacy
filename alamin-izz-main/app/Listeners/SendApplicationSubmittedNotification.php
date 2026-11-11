<?php

namespace App\Listeners;

use App\Events\ApplicationSubmitted;
use App\Models\Notification;
use App\Services\NotificationRecipients;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendApplicationSubmittedNotification implements ShouldQueue
{
    public function __construct(private NotificationRecipients $recipients) {}

    public function handle(ApplicationSubmitted $event): void
    {
        $event->application->loadMissing(['teacher', 'training']);

        $teacher       = $event->application->teacher;
        $applicantName = $teacher?->teacherName ?? 'A teacher';
        $trainingName  = $event->application->training?->courseName ?? 'a training';

        // Only the Guru Besar of the applicant's school reviews the application.
        Notification::sendMany(
            $this->recipients->guruBesarOfSchool($teacher?->schoolID),
            "New application from {$applicantName} for {$trainingName}.",
            'application.submitted',
            route('applications.index'),
        );
    }
}
