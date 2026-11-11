<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class CourseSession extends Model
{
    protected $table      = 'course_session';
    // Real column is `sessionID` (camelCase) — must match exactly so
    // fresh()/refresh()/save()/route-model-binding key lookups work; the
    // `session_id` snake_case name is kept only as a virtual read/write
    // alias below, for the rest of the app's naming convention.
    protected $primaryKey = 'sessionID';
    public    $incrementing = false;
    protected $keyType    = 'string';
    public    $timestamps = false;

    protected $fillable = [
        'sessionID',
        'courseID',
        'session_name',
        'session_date',
        'start_time',
        'end_time',
        'location',
    ];

    // These are virtual accessors (not real columns — see below), so
    // Eloquent won't include them in JSON output to the frontend unless
    // explicitly appended.
    protected $appends = ['session_id', 'session_name', 'session_date', 'start_time', 'end_time'];


    protected static function booted(): void
    {
        static::creating(function (CourseSession $session) {
            if (! $session->sessionID) {
                $session->sessionID = self::nextId();
            }
        });
    }

    private static function nextId(): string
    {
        $max = DB::table('course_session')->max(DB::raw('CAST(SUBSTRING(sessionID, 2) AS UNSIGNED)'));

        return 'S' . str_pad((string) (($max ?? 0) + 1), 4, '0', STR_PAD_LEFT);
    }

    // alamin_main_database uses camelCase column names for this table; the
    // rest of this app consistently uses snake_case — map each one here so
    // only this integration boundary needs to know about the discrepancy.
    public function getSessionIdAttribute() { return $this->attributes['sessionID'] ?? null; }
    public function setSessionIdAttribute($value): void { $this->attributes['sessionID'] = $value; }

    public function getSessionNameAttribute() { return $this->attributes['sessionName'] ?? null; }
    public function setSessionNameAttribute($value): void { $this->attributes['sessionName'] = $value; }

    public function getSessionDateAttribute()
    {
        return isset($this->attributes['sessionDate']) ? \Illuminate\Support\Carbon::parse($this->attributes['sessionDate']) : null;
    }

    public function setSessionDateAttribute($value): void
    {
        $this->attributes['sessionDate'] = $value instanceof \DateTimeInterface ? $value->format('Y-m-d') : $value;
    }

    public function getStartTimeAttribute() { return $this->attributes['startTime'] ?? null; }
    public function setStartTimeAttribute($value): void { $this->attributes['startTime'] = $value; }

    public function getEndTimeAttribute() { return $this->attributes['endTime'] ?? null; }
    public function setEndTimeAttribute($value): void { $this->attributes['endTime'] = $value; }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Training::class, 'courseID', 'courseID');
    }

    public function trainers(): BelongsToMany
    {
        return $this->belongsToMany(Trainer::class, 'session_trainer', 'sessionID', 'trainerID', 'sessionID', 'trainerID');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class, 'session_id', 'sessionID');
    }

    public function materials(): HasMany
    {
        return $this->hasMany(LearningMaterial::class, 'session_id', 'sessionID');
    }
}
