<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class FeedbackResponse extends Model
{
    protected $table        = 'feedback_response';
    protected $primaryKey   = 'responseID';
    public    $incrementing = false;
    protected $keyType      = 'string';
    public    $timestamps   = false;

    // alamin_main_database's feedback_response has no formID, user_id,
    // answer_value, teacherID, or gn_id columns — responses are identified
    // by participantID/staffID only, and store a rating or a comment (not
    // both, and not a generic "answer" field).
    protected $fillable = [
        'responseID', 'questionID', 'rating', 'comment', 'responseDate',
        'participantID', 'staffID', 'responseType',
    ];

    protected $casts = [
        'responseDate' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (FeedbackResponse $response) {
            if (! $response->responseID) {
                $response->responseID = self::nextId();
            }
        });
    }

    private static function nextId(): string
    {
        $max = DB::table('feedback_response')->max(DB::raw('CAST(SUBSTRING(responseID, 3) AS UNSIGNED)'));

        return 'FR' . str_pad((string) (($max ?? 0) + 1), 4, '0', STR_PAD_LEFT);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(FeedbackQuestion::class, 'questionID', 'questionID');
    }

    public function participant(): BelongsTo
    {
        return $this->belongsTo(CourseParticipant::class, 'participantID', 'participantID');
    }
}
