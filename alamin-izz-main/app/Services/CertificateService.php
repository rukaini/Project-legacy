<?php

namespace App\Services;

use App\Events\CertificateReviewed;
use App\Events\CertificateUploaded;
use App\Integration\DTOs\TeacherDTO;
use App\Models\AuditLog;
use App\Models\CanonicalSsoUser;
use App\Models\PenyelarasLadap;
use App\Models\Teacher;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Teacher-uploaded certificates. Stored in the penyelaras_ladap table with
 * attribute_type = 'certificate'; assigned_teacher_id is the owner. Record
 * only — certificates earn no LADAP hours and need no approval.
 */
class CertificateService
{
    public function store(User $user, TeacherDTO $teacher, array $data, UploadedFile $file): PenyelarasLadap
    {
        abort_unless($teacher->schoolID, 422, 'No school found for your account.');
        abort_if(
            Carbon::parse($data['end_time'])->lte(Carbon::parse($data['start_time'])),
            422,
            'End time must be after start time.'
        );

        $path = $file->store('certificates', 'public');

        $certificate = PenyelarasLadap::create([
            'attribute_type'            => PenyelarasLadap::TYPE_CERTIFICATE,
            'title'                     => $data['title'],
            'description'               => $data['description'] ?? null,
            'schoolID'                  => $teacher->schoolID,
            'session_date'              => $data['session_date'],
            'start_time'                => $data['start_time'],
            'end_time'                  => $data['end_time'],
            'location'                  => $data['location'] ?? null,
            'certificate_path'          => $path,
            'certificate_original_name' => $file->getClientOriginalName(),
            'assigned_teacher_id'       => $teacher->id,
            'created_by'                => $this->actorId($user),
        ]);

        AuditLog::create([
            'user_id'     => $this->actorId($user),
            'action'      => 'certificate.uploaded',
            'target_type' => PenyelarasLadap::class,
            'target_id'   => $certificate->id,
            'metadata'    => ['teacher_id' => $teacher->id],
            'created_at'  => now(),
        ]);

        event(new CertificateUploaded($certificate));

        return $certificate;
    }

    /**
     * Teacher edits their own certificate while it is still pending review.
     * A new PDF, if given, replaces the stored one.
     */
    public function update(User $user, PenyelarasLadap $certificate, TeacherDTO $teacher, array $data, ?UploadedFile $file): PenyelarasLadap
    {
        $this->ensureOwner($certificate, $teacher);
        abort_if($certificate->approval !== null, 422, 'A reviewed certificate cannot be edited.');
        abort_if(
            Carbon::parse($data['end_time'])->lte(Carbon::parse($data['start_time'])),
            422,
            'End time must be after start time.'
        );

        $attributes = [
            'title'        => $data['title'],
            'description'  => $data['description'] ?? null,
            'session_date' => $data['session_date'],
            'start_time'   => $data['start_time'],
            'end_time'     => $data['end_time'],
            'location'     => $data['location'] ?? null,
        ];

        $oldPath = $certificate->certificate_path;
        if ($file) {
            $attributes['certificate_path']          = $file->store('certificates', 'public');
            $attributes['certificate_original_name'] = $file->getClientOriginalName();
        }

        $certificate->update($attributes);

        if ($file && $oldPath) {
            Storage::disk('public')->delete($oldPath);
        }

        AuditLog::create([
            'user_id'     => $this->actorId($user),
            'action'      => 'certificate.updated',
            'target_type' => PenyelarasLadap::class,
            'target_id'   => $certificate->id,
            'metadata'    => ['teacher_id' => $teacher->id, 'file_replaced' => (bool) $file],
            'created_at'  => now(),
        ]);

        return $certificate;
    }

    /**
     * Guru Besar approves or rejects a certificate, recording credit hours and
     * remarks either way. A decision is final.
     */
    public function review(User $guruBesar, PenyelarasLadap $certificate, ?string $schoolId, array $data): void
    {
        $this->ensureSameSchool($certificate, $schoolId);
        abort_if($certificate->approval !== null, 422, 'This certificate has already been reviewed.');

        $certificate->update([
            'approval'     => $data['approval'],
            'credit_hours' => $data['credit_hours'],
            'remarks'      => $data['remarks'] ?? null,
            'reviewed_by'  => $this->actorId($guruBesar),
            'reviewed_at'  => now(),
        ]);

        AuditLog::create([
            'user_id'     => $this->actorId($guruBesar),
            'action'      => 'certificate.' . $data['approval'],
            'target_type' => PenyelarasLadap::class,
            'target_id'   => $certificate->id,
            'metadata'    => [
                'teacher_id'   => $certificate->assigned_teacher_id,
                'credit_hours' => $certificate->credit_hours,
                'ladap_hours'  => $certificate->certificateLadapHours(),
            ],
            'created_at'  => now(),
        ]);

        event(new CertificateReviewed($certificate));
    }

    /**
     * LADAP hours from a teacher's approved certificates (duration × credit hours),
     * by certificate date — this year unless given.
     */
    public function totalHours(Teacher $teacher, ?int $year = null): float
    {
        return round((float) $this->approvedFor($teacher, $year ?? now()->year)
            ->get()
            ->sum(fn (PenyelarasLadap $c) => $c->certificateLadapHours()), 2);
    }

    /** Approved certificates for My History. Null year = all years. */
    public function approvedFor(Teacher $teacher, ?int $year = null): Builder
    {
        return PenyelarasLadap::certificates()
            ->where('assigned_teacher_id', $teacher->teacherID)
            ->where('approval', PenyelarasLadap::APPROVAL_APPROVED)
            ->when($year, fn ($q) => $q->whereYear('session_date', $year));
    }

    public function delete(PenyelarasLadap $certificate, TeacherDTO $teacher): void
    {
        $this->ensureOwner($certificate, $teacher);
        abort_if($certificate->approval !== null, 422, 'A reviewed certificate cannot be deleted.');

        if ($certificate->certificate_path) {
            Storage::disk('public')->delete($certificate->certificate_path);
        }

        $certificate->delete();
    }

    public function ensureOwner(PenyelarasLadap $certificate, TeacherDTO $teacher): void
    {
        abort_unless($certificate->isCertificate(), 404);
        abort_unless($certificate->assigned_teacher_id === $teacher->id, 403, 'This certificate does not belong to you.');
    }

    /** Guru Besar may only view certificates uploaded by teachers in their own school. */
    public function ensureSameSchool(PenyelarasLadap $certificate, ?string $schoolId): void
    {
        abort_unless($certificate->isCertificate(), 404);
        abort_unless($schoolId && (string) $certificate->schoolID === (string) $schoolId, 403, 'This certificate does not belong to your school.');
    }

    /** Canonical (SSO-only) identities have no users.id, so store their raw domain ID. */
    private function actorId(User $actor): string
    {
        return $actor instanceof CanonicalSsoUser ? $actor->external_id : (string) $actor->id;
    }
}
