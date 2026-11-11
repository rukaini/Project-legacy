<?php

namespace App\Listeners;

use App\Events\PenyelarasLadapSessionCreated;
use App\Models\Notification;
use App\Services\NotificationRecipients;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendPenyelarasLadapSessionCreatedNotification implements ShouldQueue
{
    public function __construct(private NotificationRecipients $recipients) {}

    public function handle(PenyelarasLadapSessionCreated $event): void
    {
        $session  = $event->session;
        $date     = $session->session_date->format('j M Y');
        $url      = route('penyelaras-ladap.index');
        $assigned = $session->assigned_teacher_id;

        // The assigned teacher leads the session; the rest of the school can attend.
        Notification::send(
            $this->recipients->teacher($assigned),
            "You've been assigned to lead the LADAP session {$session->title} on {$date}.",
            'penyelaras_ladap.assigned',
            $url,
        );

        Notification::sendMany(
            $this->recipients->teachersOfSchool((string) $session->schoolID, array_filter([$assigned])),
            "New LADAP session: {$session->title} on {$date}.",
            'penyelaras_ladap.created',
            $url,
        );
    }
}
