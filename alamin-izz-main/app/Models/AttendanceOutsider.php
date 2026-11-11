<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceOutsider extends Model
{
    protected $table = 'attendance_outsider';
    protected $primaryKey = 'attendOutsider_id';

    protected $fillable = [
        'outsider_id',
        'session_id',
        'timestamp_status',
        'remarks',
        'attendance_status',
        'scanned_at',
        'approvedByStaff',
        'approved_at',
        'rejection_reason',
    ];

    protected $casts = [
        'timestamp_status' => 'decimal:2',
        'scanned_at'       => 'datetime',
        'approved_at'      => 'datetime',
    ];

    public function outsider(): BelongsTo
    {
        return $this->belongsTo(Outsider::class, 'outsider_id', 'outsider_id');
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(CourseSession::class, 'session_id', 'sessionID');
    }
}
