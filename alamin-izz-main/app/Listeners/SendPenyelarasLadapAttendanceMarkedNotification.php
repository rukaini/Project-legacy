<?php

namespace App\Listeners;

use App\Events\PenyelarasLadapAttendanceMarked;
use App\Models\Notification;
use App\Services\NotificationRecipients;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendPenyelarasLadapAttendanceMarkedNotification implements ShouldQueue
{
    public function __construct(private NotificationRecipients $recipients) {}

    public function handle(PenyelarasLadapAttendanceMarked $event): void
    {
        $event->attendance->loadMissing('teacher');

        $name = $event->attendance->teacher?->teacherName ?? 'A teacher';

        Notification::sendMany(
            $this->recipients->guruBesarOfSchool((string) $event->session->schoolID),
            "{$name} marked attendance for the LADAP session {$event->session->title} and is waiting for your approval.",
            'penyelaras_ladap.attendance_marked',
            route('penyelaras-ladap.index'),
        );
    }
}
