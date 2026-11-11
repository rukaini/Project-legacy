<?php

namespace App\Listeners;

use App\Events\CertificateUploaded;
use App\Models\Notification;
use App\Services\NotificationRecipients;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendCertificateUploadedNotification implements ShouldQueue
{
    public function __construct(private NotificationRecipients $recipients) {}

    public function handle(CertificateUploaded $event): void
    {
        $certificate = $event->certificate->loadMissing('assignedTeacher');
        $name        = $certificate->assignedTeacher?->teacherName ?? 'A teacher';

        Notification::sendMany(
            $this->recipients->guruBesarOfSchool((string) $certificate->schoolID),
            "{$name} uploaded a certificate for review: {$certificate->title}.",
            'certificate.uploaded',
            route('certificates.school', ['teacher_id' => $certificate->assigned_teacher_id]),
        );
    }
}
