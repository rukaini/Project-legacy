<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssessmentSubmission extends Model
{
    protected $table      = 'assessment_submission';
    protected $primaryKey = 'submission_id';

    protected $fillable = [
        'assessment_id', 'enrollment_id', 'submitted_file_path',
        'original_file_name', 'answers_json', 'submitted_at',
        'status', 'grade', 'feedback', 'graded_by_trainer_id', 'graded_at',
    ];

    protected $casts = [
        'answers_json' => 'array',
        'submitted_at' => 'datetime',
        'graded_at'    => 'datetime',
        'grade'        => 'decimal:2',
    ];

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class, 'assessment_id', 'assessment_id');
    }
}
