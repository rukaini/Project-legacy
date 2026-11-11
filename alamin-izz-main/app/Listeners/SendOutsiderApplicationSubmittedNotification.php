<?php

namespace App\Listeners;

use App\Events\OutsiderApplicationSubmitted;
use App\Models\Notification;
use App\Services\NotificationRecipients;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendOutsiderApplicationSubmittedNotification implements ShouldQueue
{
    public function __construct(private NotificationRecipients $recipients) {}

    public function handle(OutsiderApplicationSubmitted $event): void
    {
        $event->enrollment->loadMissing(['outsider', 'course']);

        $name     = $event->enrollment->outsider?->name ?? 'An outsider';
        $training = $event->enrollment->course?->courseName ?? 'a training';

        Notification::sendMany(
            $this->recipients->hr(),
            "New outsider application from {$name} for {$training}.",
            'application.submitted',
            route('hr.applications.index', ['tab' => 'outsider', 'status' => 'pending']),
        );
    }
}
