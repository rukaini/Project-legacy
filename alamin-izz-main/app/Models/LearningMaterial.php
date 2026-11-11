<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LearningMaterial extends Model
{
    protected $table      = 'learning_material';
    protected $primaryKey = 'material_id';
    public $timestamps    = false;

    public function getRouteKeyName(): string
    {
        return 'material_id';
    }

    protected $fillable = [
        'courseID',
        'trainer_id',
        'session_id',
        'original_file_name',
        'file_path',
        'material_type',
        'metadata_cleared',
        'uploaded_at',
    ];

    protected $casts = [
        'metadata_cleared' => 'boolean',
        'uploaded_at'      => 'datetime',
    ];

    public function getCourseIDAttribute()
    {
        return $this->attributes['course_id'] ?? null;
    }

    public function setCourseIDAttribute($value): void
    {
        $this->attributes['course_id'] = $value;
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Training::class, 'course_id', 'courseID');
    }

    public function trainer(): BelongsTo
    {
        return $this->belongsTo(Trainer::class, 'trainer_id', 'trainerID');
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(CourseSession::class, 'session_id', 'sessionID');
    }
}
