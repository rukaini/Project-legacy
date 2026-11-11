<?php

namespace App\Listeners;

use App\Events\PenyelarasLadapAttendanceApproved;
use App\Models\Notification;
use App\Services\NotificationRecipients;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendPenyelarasLadapAttendanceApprovedNotification implements ShouldQueue
{
    public function __construct(private NotificationRecipients $recipients) {}

    public function handle(PenyelarasLadapAttendanceApproved $event): void
    {
        $hours = number_format((float) $event->attendance->hours_ladap, 2);

        Notification::send(
            $this->recipients->teacher($event->attendance->teacher_id),
            "Your attendance for the LADAP session {$event->session->title} was approved: +{$hours} LADAP hours.",
            'penyelaras_ladap.attendance_approved',
            route('penyelaras-ladap.index'),
        );
    }
}
