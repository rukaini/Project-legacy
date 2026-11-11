<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Course extends Model
{
    protected $table = 'course';
    protected $primaryKey = 'courseID';
    public    $incrementing = false;
    protected $keyType    = 'string';
    public    $timestamps = false;

    protected $fillable = [
        'courseID',
        'courseName',
        'course_category',
        'description',
        'capacity',
        'price',
        'status',
        'poster',
        'mode',
        'whatsappGroup',
        'organiserName',
    ];

    protected static function booted(): void
    {
        static::creating(function (Course $course) {
            if (! $course->courseID) {
                $course->courseID = self::nextId();
            }
        });
    }

    private static function nextId(): string
    {
        $max = DB::table('course')->max(DB::raw('CAST(SUBSTRING(courseID, 2) AS UNSIGNED)'));

        return 'C' . str_pad((string) (($max ?? 0) + 1), 4, '0', STR_PAD_LEFT);
    }

    // alamin_main_database column is `courseCategory`; the rest of this app
    // consistently uses the `course_category` spelling — map it here so only
    // this integration boundary needs to know about the discrepancy.
    public function getCourseCategoryAttribute()
    {
        return $this->attributes['courseCategory'] ?? null;
    }

    public function setCourseCategoryAttribute($value): void
    {
        $this->attributes['courseCategory'] = $value;
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class, 'course_id', 'courseID');
    }

    public function outsiderEnrollments(): HasMany
    {
        return $this->hasMany(EnrollOutsider::class, 'course_id', 'courseID');
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', 'upcoming');
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }
}
