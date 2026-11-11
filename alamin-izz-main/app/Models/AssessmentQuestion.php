<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssessmentQuestion extends Model
{
    protected $table      = 'assessment_question';
    protected $primaryKey = 'question_id';

    protected $fillable = [
        'assessment_id', 'question_text', 'question_image_path',
        'question_order', 'question_type', 'options_json',
        'correct_answer', 'marks', 'grading_rubric',
    ];

    protected $casts = [
        'options_json' => 'array',
        'marks'        => 'decimal:2',
    ];

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class, 'assessment_id', 'assessment_id');
    }
}
