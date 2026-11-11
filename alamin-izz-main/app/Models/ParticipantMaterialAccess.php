<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class ParticipantMaterialAccess extends Model
{
    protected $table      = 'participant_material_access';
    protected $primaryKey = 'course_id';
    protected $keyType    = 'string';
    public $incrementing  = false;

    protected $fillable = [
        'course_id',
        'is_enabled',
        'access_until',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled'   => 'boolean',
            'access_until' => 'datetime',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Training::class, 'course_id', 'courseID');
    }

    /** Enabled, and either no end date or the end date hasn't passed. */
    public function isOpen(): bool
    {
        return $this->is_enabled && ($this->access_until === null || now()->lte($this->access_until));
    }

    /**
     * Access state for each course, keyed by course ID:
     * ['can_view' => bool, 'access_until' => ?string].
     * A course with no row has no restriction set, so it stays viewable.
     *
     * @param  iterable<string>  $courseIds
     * @return Collection<string, array{can_view: bool, access_until: ?string}>
     */
    public static function forCourses(iterable $courseIds): Collection
    {
        $ids  = collect($courseIds)->filter()->unique()->values();
        $rows = static::whereIn('course_id', $ids)->get()->keyBy('course_id');

        return $ids->mapWithKeys(function ($id) use ($rows) {
            $row = $rows->get($id);

            return [$id => [
                'can_view'     => $row ? $row->isOpen() : true,
                'access_until' => $row?->is_enabled ? $row->access_until?->toISOString() : null,
            ]];
        });
    }
}
