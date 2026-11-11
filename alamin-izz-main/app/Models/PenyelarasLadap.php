<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PenyelarasLadap extends Model
{
    public const TYPE_PENYELARAS_LADAP = 'penyelaras_ladap';
    public const TYPE_CERTIFICATE      = 'certificate';

    protected $table = 'penyelaras_ladap';

    protected $attributes = [
        'attribute_type' => self::TYPE_PENYELARAS_LADAP,
    ];

    protected $fillable = [
        'attribute_type',
        'title',
        'description',
        'schoolID',
        'session_date',
        'start_time',
        'end_time',
        'location',
        'certificate_path',
        'certificate_original_name',
        'approval',
        'credit_hours',
        'remarks',
        'reviewed_by',
        'reviewed_at',
        'assigned_teacher_id',
        'created_by',
    ];

    public const APPROVAL_APPROVED = 'approved';
    public const APPROVAL_REJECTED = 'rejected';

    protected function casts(): array
    {
        return [
            'session_date' => 'date',
            'credit_hours' => 'float',
            'reviewed_at'  => 'datetime',
        ];
    }

    public function assignedTeacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'assigned_teacher_id', 'teacherID');
    }

    public function scopeSessions(Builder $query): Builder
    {
        return $query->where('attribute_type', self::TYPE_PENYELARAS_LADAP);
    }

    public function scopeCertificates(Builder $query): Builder
    {
        return $query->where('attribute_type', self::TYPE_CERTIFICATE);
    }

    public function isCertificate(): bool
    {
        return $this->attribute_type === self::TYPE_CERTIFICATE;
    }

    /** Start to end time, in decimal hours. */
    public function durationHours(): float
    {
        return round(Carbon::parse($this->start_time)->diffInMinutes(Carbon::parse($this->end_time)) / 60, 2);
    }

    /** Certificate LADAP hours: duration × Guru Besar's credit hours. Null until reviewed. */
    public function certificateLadapHours(): ?float
    {
        return $this->credit_hours === null ? null : round($this->durationHours() * $this->credit_hours, 2);
    }
}
