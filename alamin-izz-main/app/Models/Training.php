<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use App\Models\EnrollGuruBaru;
use App\Models\EnrollOutsider;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * Backward-compat alias of the `course` table.
 * Route model binding uses {training} → binds here.
 * Accessors expose old column names so controllers/views keep working.
 */
class Training extends Model
{
    protected $table      = 'course';
    protected $primaryKey = 'courseID';
    public    $incrementing = false;
    protected $keyType    = 'string';
    public    $timestamps = false;

    protected $fillable = [
        'courseID',
        'courseName',
        'course_category',
        'description',
        'capacity',
        'price',
        'targetAudience',
        'otherTargetAudience',
        'staffAttendeeIDs',
        'staffAttendeeAssignedBy',
        'staffAttendeeAssignedDate',
        'courseRating',
        'status',
        'poster',
        'mode',
        'onlineLink',
        'whatsappGroup',
        'closeDate',
        'organiserName',
        'courseType',
    ];

    // Expose accessors in JSON so frontend can read both old and new names.
    protected $appends = ['id', 'title', 'location', 'trainer_name', 'course_category'];

    protected static function booted(): void
    {
        static::creating(function (Training $training) {
            if (! $training->courseID) {
                $training->courseID = self::nextId();
            }
        });
    }

    private static function nextId(): string
    {
        $max = DB::table('course')->max(DB::raw('CAST(SUBSTRING(courseID, 2) AS UNSIGNED)'));

        return 'C' . str_pad((string) (($max ?? 0) + 1), 4, '0', STR_PAD_LEFT);
    }

    // alamin_main_database column is `courseCategory`; the rest of this app
    // consistently uses the `course_category` spelling — map it here so only
    // this integration boundary needs to know about the discrepancy.
    /**
     * Course type drives behaviour now: LMS = auto-enrolled (was 'series'),
     * Simple = applied/enrolled (was 'single' / 'multi_day').
     */
    public function isLms(): bool
    {
        return strtolower(trim((string) $this->attributes['courseType'] ?? '')) === 'lms';
    }

    /**
     * Organiser names that count as in-house for the LADAP multiplier
     * (compared case-insensitively). Anything else is an external course.
     */
    public const IN_HOUSE_ORGANISERS = ['EDU Al-amin', 'Al Amin Edu Oasis'];

    public function isInHouse(): bool
    {
        $organiser = trim((string) $this->organiserName);

        foreach (self::IN_HOUSE_ORGANISERS as $name) {
            if (strcasecmp($organiser, $name) === 0) {
                return true;
            }
        }

        return false;
    }

    public function isSimple(): bool
    {
        return strtolower(trim((string) ($this->attributes['courseType'] ?? ''))) === 'simple';
    }

    public function scopeLms($query)
    {
        return $query->whereRaw('LOWER(TRIM(courseType)) = ?', ['lms']);
    }

    public function scopeSimple($query)
    {
        return $query->whereRaw('LOWER(TRIM(courseType)) = ?', ['simple']);
    }

    /** Legacy name still read by the frontend: LMS → series, Simple → single. */
    public function getCourseCategoryAttribute()
    {
        if ($this->isLms())   return 'series';
        if ($this->isSimple()) return 'single';

        return $this->attributes['courseCategory'] ?? null;
    }

    public function setCourseCategoryAttribute($value): void
    {
        $this->attributes['courseCategory'] = $value;
    }

    // ── Backward-compat accessors ────────────────────────────────────────────

    protected function id(): Attribute
    {
        return Attribute::make(get: fn () => $this->courseID);
    }

    protected function title(): Attribute
    {
        return Attribute::make(get: fn () => $this->courseName);
    }

    protected function location(): Attribute
    {
        return Attribute::make(get: fn () => $this->mode);
    }

    protected function trainerName(): Attribute
    {
        return Attribute::make(get: fn () => $this->organiserName);
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function courseSessions(): HasMany
    {
        return $this->hasMany(CourseSession::class, 'courseID', 'courseID');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class, 'course_id', 'courseID');
    }

    public function outsiderEnrollments(): HasMany
    {
        return $this->hasMany(EnrollOutsider::class, 'course_id', 'courseID');
    }

    public function guruBaruEnrollments(): HasMany
    {
        return $this->hasMany(EnrollGuruBaru::class, 'course_id', 'courseID');
    }

    public function accessLogs(): HasMany
    {
        return $this->hasMany(TrainingAccessLog::class, 'courseID', 'courseID');
    }

    public function materials(): HasMany
    {
        return $this->hasMany(LearningMaterial::class, 'course_id', 'courseID');
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', 'upcoming');
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }

    /** targetAudience (SET column) values → accepted Malay/English words for each audience. */
    public const AUDIENCE_TOKENS = [
        'teacher'     => ['teacher', 'guru'],
        'new_teacher' => ['new_teacher', 'guru_baru'],
        'public'      => ['public', 'awam'],
        'staff'       => ['staff', 'kakitangan'],
        'other'       => ['other', 'lain-lain'],
    ];

    /** Trainings open to the given audience key (teacher|new_teacher|public|staff|other). */
    public function scopeForAudience($query, string $audience)
    {
        $tokens = self::AUDIENCE_TOKENS[$audience] ?? [$audience];

        return $query->where(function ($q) use ($tokens) {
            foreach ($tokens as $token) {
                $q->orWhereRaw("FIND_IN_SET(?, targetAudience) > 0", [$token]);
            }
        });
    }
}
