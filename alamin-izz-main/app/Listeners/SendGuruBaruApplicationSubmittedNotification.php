<?php

namespace App\Listeners;

use App\Events\GuruBaruApplicationSubmitted;
use App\Models\Notification;
use App\Services\NotificationRecipients;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendGuruBaruApplicationSubmittedNotification implements ShouldQueue
{
    public function __construct(private NotificationRecipients $recipients) {}

    public function handle(GuruBaruApplicationSubmitted $event): void
    {
        $event->enrollment->loadMissing(['applicant', 'course']);

        $name     = $event->enrollment->applicant?->gn_name ?? 'A guru baru';
        $training = $event->enrollment->course?->courseName ?? 'a training';

        Notification::sendMany(
            $this->recipients->hr(),
            "New Guru Baru application from {$name} for {$training}.",
            'application.submitted',
            route('hr.applications.index', ['tab' => 'guru_baru', 'status' => 'pending']),
        );
    }
}
