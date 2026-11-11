<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnrollOutsider extends Model
{
    protected $table = 'enroll_outsider';
    protected $primaryKey = null;
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'outsider_id',
        'courseID',
        'status',
        'rejection_reason',
        'applied_at',
    ];

    protected $casts = [
        'applied_at' => 'datetime',
    ];

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

    public function outsider(): BelongsTo
    {
        return $this->belongsTo(Outsider::class, 'outsider_id', 'outsider_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Training::class, 'course_id', 'courseID');
    }
}
