<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainingContentInteractionLog extends Model
{
    protected $table      = 'training_content_interaction_log';
    protected $primaryKey = 'interaction_id';

    protected $fillable = [
        'courseID',
        'module_id',
        'block_id',
        'enrollment_id',
        'first_opened_at',
        'last_accessed_at',
        'open_count',
        'total_seconds',
        'last_event_type',
    ];

    protected $casts = [
        'first_opened_at' => 'datetime',
        'last_accessed_at' => 'datetime',
    ];

    public function block(): BelongsTo
    {
        return $this->belongsTo(TrainingContentBlock::class, 'block_id', 'block_id');
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(TrainingModule::class, 'module_id', 'module_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Training::class, 'courseID', 'courseID');
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(EnrollGuruBaru::class, 'enrollment_id', 'enrollment_id');
    }
}
