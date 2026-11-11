<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Assign extends Model
{
    public $timestamps = false;
    public $incrementing = false;

    protected $table = 'assign';
    protected $primaryKey = null;

    protected $fillable = [
        'schoolID',
        'teacherID',
        'assignDate',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'assignDate' => 'date',
        ];
    }

    public function school()
    {
        return $this->belongsTo(School::class, 'schoolID', 'schoolID');
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class, 'teacherID', 'teacherID');
    }
    /** Assignment is active — the database may store "active" (English) or "Aktif" (Malay). */
    public function scopeActive($query)
    {
        return $query->whereRaw("LOWER(TRIM(`status`)) IN (?, ?)", ["active", "aktif"]);
    }
}
