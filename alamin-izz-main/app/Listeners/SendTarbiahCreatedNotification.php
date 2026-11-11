<?php

namespace App\Listeners;

use App\Events\TarbiahCreated;
use App\Models\Notification;
use App\Services\NotificationRecipients;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendTarbiahCreatedNotification implements ShouldQueue
{
    public function __construct(private NotificationRecipients $recipients) {}

    public function handle(TarbiahCreated $event): void
    {
        $tarbiah = $event->tarbiah;

        Notification::sendMany(
            $this->recipients->teachersOfSchool((string) $tarbiah->schoolID),
            "New Tarbiah: {$tarbiah->title} on {$tarbiah->session_date->format('j M Y')}.",
            'tarbiah.created',
            route('tarbiah.index'),
        );
    }
}
