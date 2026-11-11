<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class CourseParticipant extends Model
{
    protected $table        = 'course_participant';
    protected $primaryKey   = 'participantID';
    public    $incrementing = false;
    protected $keyType      = 'string';
    public    $timestamps   = false;

    protected $fillable = [
        'participantID', 'participantType', 'participantName', 'organisationName',
        'ICNumber', 'phoneNumber', 'email', 'paymentProof', 'RSVPStatus',
        'isFeedbackCompleted', 'replacementStatus', 'replacedByName', 'replacementReason',
        'courseID', 'gn_id', 'outsider_id', 'teacherID', 'staffID',
    ];

    protected $casts = [
        'isFeedbackCompleted' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (CourseParticipant $participant) {
            if (! $participant->participantID) {
                $participant->participantID = self::nextId();
            }
        });
    }

    private static function nextId(): string
    {
        $max = DB::table('course_participant')->max(DB::raw('CAST(SUBSTRING(participantID, 3) AS UNSIGNED)'));

        return 'CP' . str_pad((string) (($max ?? 0) + 1), 4, '0', STR_PAD_LEFT);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Training::class, 'courseID', 'courseID');
    }

    public function guruNew(): BelongsTo
    {
        return $this->belongsTo(GuruNew::class, 'gn_id', 'gn_id');
    }

    public function outsider(): BelongsTo
    {
        return $this->belongsTo(Outsider::class, 'outsider_id', 'outsider_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'teacherID', 'teacherID');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(FeedbackResponse::class, 'participantID', 'participantID');
    }

    // Finds (without creating) the participant record identifying $user for
    // $courseId, based on role. Returns null if the user has no matching
    // teacher/outsider/guru-baru profile or role.
    public static function findFor(User $user, string $courseId): ?self
    {
        if ($user->isTeacher()) {
            $teacher = Teacher::where('email', $user->email)->first();

            return $teacher
                ? static::where('courseID', $courseId)->where('teacherID', $teacher->teacherID)->first()
                : null;
        }

        if ($user->isOutsider()) {
            $outsider = Outsider::where('user_id', $user->id)->first();

            return $outsider
                ? static::where('courseID', $courseId)->where('outsider_id', $outsider->outsider_id)->first()
                : null;
        }

        if ($user->isGuruBaru()) {
            $guruNew = GuruNew::where('email', $user->email)->first();

            return $guruNew
                ? static::where('courseID', $courseId)->where('gn_id', $guruNew->gn_id)->first()
                : null;
        }

        return null;
    }
}
