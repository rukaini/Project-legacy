<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainingAccessLog extends Model
{
    protected $table      = 'training_access_log';
    protected $primaryKey = 'access_id';

    protected $fillable = [
        'courseID',
        'enrollment_id',
        'accessed_at',
    ];

    protected $casts = [
        'accessed_at' => 'datetime',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Training::class, 'courseID', 'courseID');
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(EnrollGuruBaru::class, 'enrollment_id', 'enrollment_id');
    }
}
