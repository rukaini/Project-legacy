<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrainingModule extends Model
{
    protected $table      = 'training_module';
    protected $primaryKey = 'module_id';

    protected $fillable = [
        'courseID', 'module_title', 'module_type', 'module_order', 'cover_image',
    ];

    public function getCourseIDAttribute()
    {
        return $this->attributes['course_id'] ?? null;
    }

    public function setCourseIDAttribute($value): void
    {
        $this->attributes['course_id'] = $value;
    }

    public function training(): BelongsTo
    {
        return $this->belongsTo(Training::class, 'course_id', 'courseID');
    }

    public function contentBlocks(): HasMany
    {
        return $this->hasMany(TrainingContentBlock::class, 'module_id', 'module_id');
    }
}
