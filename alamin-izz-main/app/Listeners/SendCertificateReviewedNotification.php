<?php

namespace App\Listeners;

use App\Events\CertificateReviewed;
use App\Models\Notification;
use App\Models\PenyelarasLadap;
use App\Services\NotificationRecipients;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendCertificateReviewedNotification implements ShouldQueue
{
    public function __construct(private NotificationRecipients $recipients) {}

    public function handle(CertificateReviewed $event): void
    {
        $certificate = $event->certificate;
        $approved    = $certificate->approval === PenyelarasLadap::APPROVAL_APPROVED;

        $message = $approved
            ? "Your certificate {$certificate->title} was approved: +" . number_format((float) $certificate->certificateLadapHours(), 2) . ' LADAP hours.'
            : "Your certificate {$certificate->title} was rejected.";

        Notification::send(
            $this->recipients->teacher($certificate->assigned_teacher_id),
            $message,
            $approved ? 'certificate.approved' : 'certificate.rejected',
            route('certificates.index'),
        );
    }
}
