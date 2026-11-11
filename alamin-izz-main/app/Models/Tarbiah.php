<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tarbiah extends Model
{
    protected $table      = 'tarbiah';
    protected $primaryKey = 'tarbiah_id';

    protected $fillable = [
        'title',
        'description',
        'schoolID',
        'session_date',
        'end_date',
        'start_time',
        'end_time',
        'credit_hours',
        'location',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'session_date' => 'date',
            'end_date'     => 'date',
            'credit_hours' => 'decimal:2',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class, 'schoolID', 'schoolID');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Only Guru Besar create Tarbiah sessions. `created_by` holds either a
     * real `users.id` (numeric) or, for a Guru Besar signed in via SSO
     * without a `users` row, their raw `principal.principalID` (e.g. "P20").
     * Resolves the creator's display name across both cases.
     */
    public function creatorName(): ?string
    {
        if ($this->created_by === null) {
            return null;
        }

        if (ctype_digit((string) $this->created_by)) {
            return $this->creator?->name;
        }

        return Principal::where('principalID', $this->created_by)->value('principalName');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(TarbiahAttendance::class, 'tarbiah_id', 'tarbiah_id');
    }
}
