<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Assessment extends Model
{
    protected $table      = 'assessment';
    protected $primaryKey = 'assessment_id';

    protected $fillable = [
        'courseID', 'type', 'title', 'status', 'module_id',
        'instruction_text', 'available_from', 'available_until',
        'accepted_format', 'time_limit_minutes', 'max_attempts',
        'total_marks', 'weight_percent',
    ];

    protected $casts = [
        'available_from'  => 'datetime',
        'available_until' => 'datetime',
        'total_marks'     => 'decimal:2',
        'weight_percent'  => 'decimal:2',
    ];

    public function getCourseIDAttribute()
    {
        return $this->attributes['course_id'] ?? null;
    }

    public function setCourseIDAttribute($value): void
    {
        $this->attributes['course_id'] = $value;
    }

    public function questions(): HasMany
    {
        return $this->hasMany(AssessmentQuestion::class, 'assessment_id', 'assessment_id')
            ->orderBy('question_order');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(AssessmentSubmission::class, 'assessment_id', 'assessment_id');
    }
}
