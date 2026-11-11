<?php

namespace App\Services;

use App\Events\PenyelarasLadapAttendanceApproved;
use App\Events\PenyelarasLadapAttendanceMarked;
use App\Events\PenyelarasLadapSessionCreated;
use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\CanonicalSsoUser;
use App\Models\PenyelarasLadap;
use App\Models\Teacher;
use App\Models\User;
use Carbon\Carbon;

class PenyelarasLadapService
{
    public function register(User $guruBesar, string $schoolId, array $data): PenyelarasLadap
    {
        abort_if(
            Carbon::parse($data['end_time'])->lte(Carbon::parse($data['start_time'])),
            422,
            'End time must be after start time.'
        );

        $session = PenyelarasLadap::create([
            'title'               => $data['title'],
            'description'         => $data['description'] ?? null,
            'schoolID'            => $schoolId,
            'session_date'        => $data['session_date'],
            'start_time'          => $data['start_time'],
            'end_time'            => $data['end_time'],
            'location'            => $data['location'] ?? null,
            'assigned_teacher_id' => $data['assigned_teacher_id'],
            'created_by'          => $this->actorId($guruBesar),
        ]);

        event(new PenyelarasLadapSessionCreated($session));

        return $session;
    }

    /**
     * A teacher marks their own attendance while the session is active. No
     * LADAP hours yet — Guru Besar sets the credit hours on approval.
     */
    public function markAttendance(User $user, PenyelarasLadap $session): Attendance
    {
        abort_if($session->isCertificate(), 404);

        $teacher = Teacher::where('email', $user->email)->first();
        abort_unless($teacher, 403, 'No teacher profile found for your account.');

        abort_if(
            (string) $teacher->schoolID !== (string) $session->schoolID,
            403,
            'This session is not available for your school.'
        );

        abort_if($this->status($session) !== 'active', 422, 'This session is not currently active.');

        $alreadyMarked = Attendance::where('attribute_type', Attendance::TYPE_PENYELARAS_LADAP)
            ->where('teacher_id', $teacher->teacherID)
            ->where('penyelaras_ladap_id', $session->id)
            ->exists();
        abort_if($alreadyMarked, 422, 'You have already marked attendance for this session.');

        $attendance = Attendance::create([
            'attribute_type'      => Attendance::TYPE_PENYELARAS_LADAP,
            'penyelaras_ladap_id' => $session->id,
            'teacher_id'          => $teacher->teacherID,
            'session_id'          => null,
            'timestamp_status'    => $this->sessionHours($session),
            'hours_ladap'         => 0,
            'attendance_status'   => 'pending',
        ]);

        AuditLog::create([
            'user_id'     => $this->actorId($user),
            'action'      => 'penyelaras_ladap.attendance_marked',
            'target_type' => PenyelarasLadap::class,
            'target_id'   => $session->id,
            'metadata'    => ['teacher_id' => $teacher->teacherID, 'assigned' => $session->assigned_teacher_id === $teacher->teacherID],
            'created_at'  => now(),
        ]);

        event(new PenyelarasLadapAttendanceMarked($session, $attendance));

        return $attendance;
    }

    /**
     * Guru Besar approves one teacher's marked attendance, setting the LADAP
     * credit hours and optional remarks. Only then does it count toward the
     * teacher's LADAP total / My History.
     */
    public function approveAttendance(User $guruBesar, Attendance $attendance, string $schoolId, array $data): void
    {
        abort_unless($attendance->attribute_type === Attendance::TYPE_PENYELARAS_LADAP, 404);

        $session = PenyelarasLadap::find($attendance->penyelaras_ladap_id);
        abort_unless($session && (string) $session->schoolID === (string) $schoolId, 403, 'This attendance record does not belong to your school.');

        abort_if($attendance->attendance_status === 'approved', 422, 'This attendance is already approved.');

        $creditHours = (float) $data['credit_hours'];

        // LADAP hours = session length (start → end) × credit hours.
        $attendance->update([
            'attendance_status' => 'approved',
            'hours_ladap'       => round($this->sessionHours($session) * $creditHours, 2),
            'remarks'           => $data['remarks'] ?? null,
            'approved_at'       => now(),
        ]);

        AuditLog::create([
            'user_id'     => $this->actorId($guruBesar),
            'action'      => 'penyelaras_ladap.attendance_approved',
            'target_type' => PenyelarasLadap::class,
            'target_id'   => $session->id,
            'metadata'    => [
                'attendance_id' => $attendance->attendance_id,
                'teacher_id'    => $attendance->teacher_id,
                'credit_hours'  => $creditHours,
                'ladap_hours'   => (float) $attendance->hours_ladap,
            ],
            'created_at'  => now(),
        ]);

        event(new PenyelarasLadapAttendanceApproved($session, $attendance));
    }

    /** Session length, start to end, in decimal hours (shown as "Total Hrs"). */
    public function sessionHours(PenyelarasLadap $session): float
    {
        return $session->durationHours();
    }

    /** Penyelaras Ladap hours a teacher has earned — approved attendance only, this year. */
    public function totalHours(Teacher $teacher, ?int $year = null): float
    {
        $year = $year ?? now()->year;

        $sessionIds = PenyelarasLadap::sessions()->whereYear('session_date', $year)->pluck('id');

        return round((float) Attendance::where('attribute_type', Attendance::TYPE_PENYELARAS_LADAP)
            ->where('teacher_id', $teacher->teacherID)
            ->where('attendance_status', 'approved')
            ->whereIn('penyelaras_ladap_id', $sessionIds)
            ->sum('hours_ladap'), 2);
    }

    /** Guru Besar edits a session in their school. */
    public function update(User $guruBesar, PenyelarasLadap $session, string $schoolId, array $data): PenyelarasLadap
    {
        abort_if($session->isCertificate(), 404);
        abort_unless((string) $session->schoolID === (string) $schoolId, 403, 'This session does not belong to your school.');
        abort_if(
            Carbon::parse($data['end_time'])->lte(Carbon::parse($data['start_time'])),
            422,
            'End time must be after start time.'
        );

        $session->update([
            'title'               => $data['title'],
            'description'         => $data['description'] ?? null,
            'session_date'        => $data['session_date'],
            'start_time'          => $data['start_time'],
            'end_time'            => $data['end_time'],
            'location'            => $data['location'] ?? null,
            'assigned_teacher_id' => $data['assigned_teacher_id'],
        ]);

        // Keep each attendance's session length in step. Approved LADAP hours are
        // session length × credit hours, so rescale them to the new length while
        // keeping the credit hours the Guru Besar entered.
        $newHours = $this->sessionHours($session);
        Attendance::where('attribute_type', Attendance::TYPE_PENYELARAS_LADAP)
            ->where('penyelaras_ladap_id', $session->id)
            ->get()
            ->each(function (Attendance $a) use ($newHours) {
                $oldHours = (float) $a->timestamp_status;
                $changes  = ['timestamp_status' => $newHours];
                if ($a->attendance_status === 'approved' && $oldHours > 0) {
                    $changes['hours_ladap'] = round((float) $a->hours_ladap / $oldHours * $newHours, 2);
                }
                $a->update($changes);
            });

        AuditLog::create([
            'user_id'     => $this->actorId($guruBesar),
            'action'      => 'penyelaras_ladap.updated',
            'target_type' => PenyelarasLadap::class,
            'target_id'   => $session->id,
            'metadata'    => ['changes' => $session->getChanges()],
            'created_at'  => now(),
        ]);

        return $session;
    }

    public function delete(PenyelarasLadap $session, string $schoolId): void
    {
        abort_if($session->isCertificate(), 404);
        abort_unless((string) $session->schoolID === (string) $schoolId, 403, 'This session does not belong to your school.');

        $session->delete();
    }

    /** @return 'upcoming'|'active'|'completed' */
    public function status(PenyelarasLadap $session, ?Carbon $now = null): string
    {
        $now   = $now ?? Carbon::now();
        $start = Carbon::parse($session->session_date->format('Y-m-d') . ' ' . $session->start_time);
        $end   = Carbon::parse($session->session_date->format('Y-m-d') . ' ' . $session->end_time);

        return match (true) {
            $now->lt($start) => 'upcoming',
            $now->gt($end)   => 'completed',
            default          => 'active',
        };
    }

    /** Canonical (SSO-only) identities have no users.id, so store their raw domain ID. */
    private function actorId(User $actor): string
    {
        return $actor instanceof CanonicalSsoUser ? $actor->external_id : (string) $actor->id;
    }
}
