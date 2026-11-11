<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class School extends Model
{
    protected $table = 'school';
    protected $primaryKey = 'schoolID';
    public    $incrementing = false;
    protected $keyType    = 'string';
    public    $timestamps = false;

    protected $fillable = [
        'schoolID',
        'schoolName',
        'schoolAddress',
        'registerDate',
        'phoneNumber',
        'totalTeacher',
        'vacancy',
        'capacity',
    ];

    protected function casts(): array
    {
        return [
            'registerDate' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (School $school) {
            if (! $school->schoolID) {
                $school->schoolID = self::nextId();
            }
        });
    }

    private static function nextId(): string
    {
        $max = DB::table('school')->max(DB::raw('CAST(SUBSTRING(schoolID, 4) AS UNSIGNED)'));

        return 'SCH' . str_pad((string) (($max ?? 0) + 1), 4, '0', STR_PAD_LEFT);
    }

    public function teachers()
    {
        // Teacher-school assignment lives in the `assign` table, not a
        // direct column on teacher.
        return $this->hasManyThrough(
            Teacher::class,
            Assign::class,
            'schoolID',   // FK on assign referencing school
            'teacherID',  // FK on teacher referencing assign.teacherID
            'schoolID',   // local key on school
            'teacherID',  // local key on assign
        )->whereRaw('LOWER(TRIM(`assign`.`status`)) IN (?, ?)', ['active', 'aktif']);
    }

    public function principals()
    {
        return $this->hasMany(Principal::class, 'schoolID', 'schoolID');
    }

    public function assigns()
    {
        return $this->hasMany(Assign::class, 'schoolID', 'schoolID');
    }
}
