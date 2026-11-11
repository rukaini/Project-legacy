<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceGuruBaru extends Model
{
    protected $table      = 'attendance_guru_baru';
    protected $primaryKey = 'attendGuruBaru_id';

    protected $fillable = [
        'gn_id',
        'session_id',
        'timestamp_status',
        'hours_ladap',
        'remarks',
        'attendance_status',
        'scanned_at',
        'approvedByStaff',
        'approved_at',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'scanned_at'  => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(GuruNew::class, 'gn_id', 'gn_id');
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(CourseSession::class, 'session_id', 'sessionID');
    }
}
