<?php

namespace App\Services;

use App\Events\TarbiahCreated;
use App\Models\AuditLog;
use App\Models\CanonicalSsoUser;
use App\Models\Tarbiah;
use App\Models\TarbiahAttendance;
use App\Models\Teacher;
use App\Models\User;
use Carbon\Carbon;

class TarbiahService
{
    public const LADAP_TARGET_HOURS = 10.0;

    public function register(User $guruBesar, int|string $schoolId, array $data): Tarbiah
    {
        abort_if(
            Carbon::parse($data['end_time'])->lte(Carbon::parse($data['start_time'])),
            422,
            'End time must be after start time.'
        );

        $tarbiah = Tarbiah::create([
            'title'        => $data['title'],
            'description'  => $data['description'] ?? null,
            'schoolID'     => $schoolId,
            'session_date' => $data['session_date'],
            'end_date'     => $data['end_date'] ?? $data['session_date'],
            'start_time'   => $data['start_time'],
            'end_time'     => $data['end_time'],
            'credit_hours' => $data['credit_hours'],
            'location'     => $data['location'] ?? null,
            'created_by'   => $this->actorId($guruBesar),
        ]);

        $this->writeAudit($guruBesar, 'tarbiah.registered', $tarbiah, [
            'school_id' => $schoolId,
        ]);

        event(new TarbiahCreated($tarbiah));

        return $tarbiah;
    }

    /**
     * Guru Besar edits a Tarbiah in their school. Attendance earns hours from
     * credit_hours at read time, so existing attendance picks up the change.
     */
    public function update(User $guruBesar, Tarbiah $tarbiah, int|string $schoolId, array $data): Tarbiah
    {
        abort_unless($tarbiah->schoolID == $schoolId, 403, 'This Tarbiah does not belong to your school.');
        abort_if(
            Carbon::parse($data['end_time'])->lte(Carbon::parse($data['start_time'])),
            422,
            'End time must be after start time.'
        );

        $tarbiah->update([
            'title'        => $data['title'],
            'description'  => $data['description'] ?? null,
            'session_date' => $data['session_date'],
            'end_date'     => $data['end_date'] ?? $data['session_date'],
            'start_time'   => $data['start_time'],
            'end_time'     => $data['end_time'],
            'credit_hours' => $data['credit_hours'],
            'location'     => $data['location'] ?? null,
        ]);

        $this->writeAudit($guruBesar, 'tarbiah.updated', $tarbiah, ['changes' => $tarbiah->getChanges()]);

        return $tarbiah;
    }

    /**
     * Only the Guru Besar of the school this Tarbiah belongs to may delete it.
     * Attendance records cascade-delete automatically at the database level.
     */
    public function delete(User $guruBesar, Tarbiah $tarbiah, int|string $schoolId): void
    {
        abort_unless($tarbiah->schoolID == $schoolId, 403, 'This Tarbiah does not belong to your school.');

        $this->writeAudit($guruBesar, 'tarbiah.deleted', $tarbiah, ['title' => $tarbiah->title]);

        $tarbiah->delete();
    }

    public function markAttendance(User $user, Tarbiah $tarbiah): TarbiahAttendance
    {
        $teacher = Teacher::where('email', $user->email)->first();
        abort_unless($teacher, 403, 'No teacher profile found for your account.');

        abort_if(
            $teacher->schoolID !== $tarbiah->schoolID,
            403,
            'This Tarbiah is not available for your school.'
        );

        $start = Carbon::parse($tarbiah->session_date->format('Y-m-d') . ' ' . $tarbiah->start_time);
        $end   = Carbon::parse(($tarbiah->end_date ?? $tarbiah->session_date)->format('Y-m-d') . ' ' . $tarbiah->end_time);
        $now   = Carbon::now();

        abort_if($now->lt($start) || $now->gt($end), 422, 'This Tarbiah is not currently active.');

        $alreadyMarked = TarbiahAttendance::where('teacher_id', $teacher->teacherID)
            ->where('tarbiah_id', $tarbiah->tarbiah_id)
            ->exists();

        abort_if($alreadyMarked, 422, 'You have already marked attendance for this Tarbiah.');

        $attendance = TarbiahAttendance::create([
            'teacher_id' => $teacher->teacherID,
            'tarbiah_id' => $tarbiah->tarbiah_id,
        ]);

        $this->writeAudit($user, 'tarbiah.attendance_marked', $tarbiah, [
            'teacher_id' => $teacher->teacherID,
            'tarbiah_id' => $tarbiah->tarbiah_id,
        ]);

        return $attendance;
    }

    /**
     * LADAP hours are set explicitly by Guru Besar at registration, not
     * derived from start/end time (start/end time only defines the window
     * during which attendance can be marked — see status()/markAttendance()).
     */
    public function sessionHours(Tarbiah $tarbiah): float
    {
        return (float) ($tarbiah->credit_hours ?? 0);
    }

    /**
     * Sums Tarbiah LADAP hours, scoped to the calendar year the session took
     * place in, defaulting to the current year. Resets every 1 January.
     */
    public function totalLadapHours(Teacher $teacher, ?int $year = null): float
    {
        $year = $year ?? now()->year;

        $tarbiahIds = TarbiahAttendance::where('teacher_id', $teacher->teacherID)
            ->pluck('tarbiah_id');

        $total = Tarbiah::whereIn('tarbiah_id', $tarbiahIds)
            ->whereYear('session_date', $year)
            ->get()
            ->sum(fn (Tarbiah $t) => $this->sessionHours($t));

        return round($total, 2);
    }

    /**
     * @return 'upcoming'|'active'|'completed'
     */
    public function status(Tarbiah $tarbiah, ?Carbon $now = null): string
    {
        $now   = $now ?? Carbon::now();
        $start = Carbon::parse($tarbiah->session_date->format('Y-m-d') . ' ' . $tarbiah->start_time);
        $end   = Carbon::parse(($tarbiah->end_date ?? $tarbiah->session_date)->format('Y-m-d') . ' ' . $tarbiah->end_time);

        return match (true) {
            $now->lt($start) => 'upcoming',
            $now->gt($end)   => 'completed',
            default          => 'active',
        };
    }

    /**
     * A signed-in-via-SSO Teacher/Principal/GuruBaru without a `users` row
     * (App\Models\CanonicalSsoUser) has no real `users.id` — use their raw
     * domain ID (e.g. "P20") instead so it matches the source table directly.
     */
    private function actorId(User $actor): string
    {
        return $actor instanceof CanonicalSsoUser ? $actor->external_id : (string) $actor->id;
    }

    private function writeAudit(User $actor, string $action, Tarbiah $tarbiah, array $metadata = []): void
    {
        AuditLog::create([
            'user_id'     => $this->actorId($actor),
            'action'      => $action,
            'target_type' => Tarbiah::class,
            'target_id'   => $tarbiah->tarbiah_id,
            'metadata'    => $metadata,
            'created_at'  => now(),
        ]);
    }
}
