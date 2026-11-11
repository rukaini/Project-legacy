<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\EnrollOutsider;
use App\Models\EnrollGuruBaru;

class Application extends Model
{
    public const ENROLLMENT_APPLIED  = 'applied';
    public const ENROLLMENT_ASSIGNED = 'assigned';

    protected $table = 'enroll_teacher';

    protected $primaryKey = null;
    public $incrementing = false;

    protected $fillable = [
        'teacherID',
        'courseID',
        'status',
        'enrollment_type',
        'rejection_reason',
        'applied_at',
    ];

    protected function casts(): array
    {
        return [
            'applied_at' => 'datetime',
        ];
    }

    // alamin_main_database column is `course_id` (kept as-is: its audit
    // trigger references the column by that name); the rest of this app
    // consistently uses `courseID` — map it here so only this integration
    // boundary needs to know about the discrepancy.
    public function getCourseIDAttribute()
    {
        return $this->attributes['course_id'] ?? null;
    }

    public function setCourseIDAttribute($value): void
    {
        $this->attributes['course_id'] = $value;
    }

    // ── Route model binding via composite key ─────────────────────────────────

    public function getRouteKey(): string
    {
        return $this->teacherID . '_' . $this->courseID;
    }

    public function resolveRouteBinding($value, $field = null): ?static
    {
        $parts = explode('_', (string) $value, 2);
        if (count($parts) < 2) return null;
        return static::where('teacherID', $parts[0])->where('course_id', $parts[1])->first();
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'teacherID', 'teacherID');
    }

    public function training(): BelongsTo
    {
        return $this->belongsTo(Training::class, 'course_id', 'courseID');
    }

    // ── Virtual accessor — resolves user_id via teacher email ─────────────────
    // Used by queued listeners for single-record notifications.

    public function getUserIdAttribute(): ?int
    {
        $this->loadMissing('teacher');
        return $this->teacher
            ? User::where('email', $this->teacher->email)->value('id')
            : null;
    }

    // ── Helper for bulk user-id lookups (used in listeners) ───────────────────

    public static function enrolledUserIds(int|string $courseId, string|array $status = 'approved'): \Illuminate\Support\Collection
    {
        $statuses = (array) $status;

        // Teachers
        $teacherIds = static::where('enroll_teacher.course_id', $courseId)
            ->whereIn('status', $statuses)
            ->join('teacher', 'enroll_teacher.teacherID', '=', 'teacher.teacherID')
            ->join('users', 'teacher.email', '=', 'users.email')
            ->pluck('users.id');

        // Outsiders
        $outsiderIds = EnrollOutsider::where('course_id', $courseId)
            ->whereIn('status', $statuses)
            ->join('outsider', 'enroll_outsider.outsider_id', '=', 'outsider.outsider_id')
            ->join('users', 'outsider.user_id', '=', 'users.id')
            ->pluck('users.id');

        // Guru Baru
        $guruBaruIds = EnrollGuruBaru::where('course_id', $courseId)
            ->whereIn('status', $statuses)
            ->join('guru_new', 'enroll_guru_baru.gn_id', '=', 'guru_new.gn_id')
            ->join('users', 'guru_new.email', '=', 'users.email')
            ->pluck('users.id');

        return $teacherIds->concat($outsiderIds)->concat($guruBaruIds)->unique()->values();
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', 'approved');
    }
}
