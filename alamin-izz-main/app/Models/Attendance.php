<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    public const TYPE_TRAINING         = 'training';
    public const TYPE_PENYELARAS_LADAP = 'penyelaras ladap';

    protected $table      = 'attendance';
    protected $primaryKey = 'attendance_id';

    protected $fillable = [
        'attribute_type',
        'penyelaras_ladap_id',
        'timestamp_status',
        'hours_ladap',
        'teacher_id',
        'session_id',
        'remarks',
        'attendance_status',
        'scanned_at',
        'approvedByStaff',
        'approved_at',
        'rejection_reason',
    ];

    protected $casts = [
        'timestamp_status' => 'decimal:2',
        'hours_ladap'      => 'decimal:2',
        'scanned_at'       => 'datetime',
        'approved_at'      => 'datetime',
    ];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'teacher_id', 'teacherID');
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(CourseSession::class, 'session_id', 'sessionID');
    }

    public function penyelarasLadap(): BelongsTo
    {
        return $this->belongsTo(PenyelarasLadap::class, 'penyelaras_ladap_id', 'id');
    }
}
