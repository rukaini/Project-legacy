<?php

namespace App\Services;

use App\Enums\Role;
use App\Models\Application;
use App\Models\EnrollGuruBaru;
use App\Models\EnrollOutsider;
use App\Models\GuruNew;
use App\Models\HrAdministrator;
use App\Models\Notification;
use App\Models\Outsider;
use App\Models\Principal;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Who a notification goes to, as recipient identities for Notification::send()
 * / sendMany(). Canonical people (teachers, principals, Guru Baru, HR) are
 * addressed by their own IDs so SSO users without a users row are reached;
 * local accounts (outsiders, seeded demo users) by user_id.
 */
class NotificationRecipients
{
    public function teacher(?string $teacherId): array
    {
        return $teacherId ? ['teacherID' => $teacherId] : [];
    }

    public function guruNew(?string $gnId): array
    {
        return $gnId ? ['gn_id' => $gnId] : [];
    }

    public function user(?User $user): array
    {
        return $user ? Notification::identityOf($user) : [];
    }

    /**
     * Guru Besar of a school: its active principals, plus local Guru Besar
     * accounts tied to that school (or to no principal record at all).
     */
    public function guruBesarOfSchool(?string $schoolId): Collection
    {
        $principals = $this->activePrincipals()
            ->when($schoolId, fn ($q) => $q->where('schoolID', $schoolId))
            ->pluck('principalID')
            ->map(fn ($id) => ['principalID' => $id]);

        $localGb = User::role(Role::GuruBesar->value)->get()
            ->filter(function (User $u) use ($schoolId) {
                $principalSchool = Principal::where('email', $u->email)->value('schoolID');
                return $principalSchool === null || ! $schoolId || (string) $principalSchool === (string) $schoolId;
            })
            ->map(fn (User $u) => Notification::identityOf($u));

        return $principals->concat($localGb)->values();
    }

    /**
     * Active teachers of a school, optionally leaving some out.
     *
     * @param array<string> $except teacherIDs to skip
     */
    public function teachersOfSchool(?string $schoolId, array $except = []): Collection
    {
        if (! $schoolId) {
            return collect();
        }

        return Teacher::whereHas('assigns', fn ($q) => $q->where('schoolID', $schoolId)->active())
            ->whereNotIn('teacherID', $except)
            ->pluck('teacherID')
            ->map(fn ($id) => ['teacherID' => $id]);
    }

    /** All HR administrators, plus local HR accounts. */
    public function hr(): Collection
    {
        return HrAdministrator::pluck('hrid')
            ->map(fn ($id) => ['hrid' => $id])
            ->concat($this->localUsers([Role::HR->value]));
    }

    /** Everyone who can take part in training: teachers, Guru Baru, outsiders. */
    public function allParticipants(): Collection
    {
        $teachers = Teacher::whereHas('assigns', fn ($q) => $q->active())
            ->pluck('teacherID')
            ->map(fn ($id) => ['teacherID' => $id]);

        $guruNew = GuruNew::whereRaw('LOWER(TRIM(`role`)) = ?', ['new_teacher'])
            ->whereRaw('LOWER(TRIM(`current_status`)) = ?', ['active'])
            ->pluck('gn_id')
            ->map(fn ($id) => ['gn_id' => $id]);

        return $teachers->concat($guruNew)
            ->concat($this->localUsers([Role::Teacher->value, Role::Outsider->value, Role::GuruBaru->value]));
    }

    /**
     * Participants enrolled in a course with one of the given statuses.
     *
     * @param string|array<string> $status
     */
    public function enrolledIn(int|string $courseId, string|array $status = 'approved'): Collection
    {
        $statuses = (array) $status;

        $teachers = Application::where('course_id', $courseId)
            ->whereIn('status', $statuses)
            ->pluck('teacherID')
            ->map(fn ($id) => ['teacherID' => $id]);

        $outsiders = User::whereIn('id', Outsider::whereIn('outsider_id',
                EnrollOutsider::where('course_id', $courseId)->whereIn('status', $statuses)->pluck('outsider_id')
            )->pluck('user_id'))
            ->get()
            ->map(fn (User $u) => Notification::identityOf($u));

        $guruNew = EnrollGuruBaru::where('course_id', $courseId)
            ->whereIn('status', $statuses)
            ->pluck('gn_id')
            ->map(fn ($id) => ['gn_id' => $id]);

        return $teachers->concat($outsiders)->concat($guruNew)->values();
    }

    private function activePrincipals()
    {
        return Principal::whereRaw('LOWER(TRIM(`role`)) = ?', ['principal'])
            ->whereRaw('LOWER(TRIM(`status`)) IN (?, ?)', ['active', 'aktif']);
    }

    /** @param array<string> $roles */
    private function localUsers(array $roles): Collection
    {
        return User::role($roles)->get()->map(fn (User $u) => Notification::identityOf($u));
    }
}
