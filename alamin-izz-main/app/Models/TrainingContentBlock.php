<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrainingContentBlock extends Model
{
    protected $table      = 'training_content_block';
    protected $primaryKey = 'block_id';

    public function getRouteKeyName(): string
    {
        return 'block_id';
    }

    protected $fillable = [
        'module_id', 'block_type', 'block_title', 'block_content',
        'external_url', 'material_id', 'assessment_id', 'block_order', 'is_visible',
    ];

    protected $casts = [
        'is_visible' => 'boolean',
    ];

    public function module(): BelongsTo
    {
        return $this->belongsTo(TrainingModule::class, 'module_id', 'module_id');
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(LearningMaterial::class, 'material_id', 'material_id');
    }

    public function interactions(): HasMany
    {
        return $this->hasMany(TrainingContentInteractionLog::class, 'block_id', 'block_id');
    }
}
