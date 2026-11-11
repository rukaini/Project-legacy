<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnrollGuruBaru extends Model
{
    protected $table = 'enroll_guru_baru';
    protected $primaryKey = 'enrollment_id';
    public $timestamps = false;

    protected $fillable = [
        'gn_id',
        'courseID',
        'status',
        'rejection_reason',
        'applied_at',
    ];

    protected $casts = [
        'applied_at' => 'datetime',
    ];

    public function getCourseIDAttribute()
    {
        return $this->attributes['course_id'] ?? null;
    }

    public function setCourseIDAttribute($value): void
    {
        $this->attributes['course_id'] = $value;
    }

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(GuruNew::class, 'gn_id', 'gn_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Training::class, 'course_id', 'courseID');
    }

    public function accessLogs(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(TrainingAccessLog::class, 'enrollment_id', 'enrollment_id');
    }
}
