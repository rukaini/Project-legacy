<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TarbiahAttendance extends Model
{
    protected $table      = 'tarbiah_attendance';
    protected $primaryKey = 'tarbiah_attendance_id';

    protected $fillable = [
        'teacher_id',
        'tarbiah_id',
    ];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'teacher_id', 'teacherID');
    }

    public function tarbiah(): BelongsTo
    {
        return $this->belongsTo(Tarbiah::class, 'tarbiah_id', 'tarbiah_id');
    }
}
