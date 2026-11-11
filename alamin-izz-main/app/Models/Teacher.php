<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\DB;

class Teacher extends Authenticatable
{
    protected $table = 'teacher';
    protected $primaryKey = 'teacherID';
    public    $incrementing = false;
    protected $keyType    = 'string';

    protected $fillable = [
        'teacherID',
        'teacherName',
        'ICNumber',
        'phoneNumber',
        'email',
        'martialStatus',
        'gender',
        'address',
        'race',
        'appointedDate',
        'serviceDate',
        'pensionDate',
        'latestAge',
        'password',
        'schoolID',
    ];

    protected $hidden = [
        'password',
    ];

    // Set only via setSchoolIDAttribute(); alamin_main_database has no
    // schoolID column on teacher, so this is never written to $attributes.
    private ?string $pendingSchoolID = null;

    protected function casts(): array
    {
        return [
            'appointedDate' => 'date',
            'serviceDate'   => 'date',
            'pensionDate'   => 'date',
            'password'      => 'hashed',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Teacher $teacher) {
            if (! $teacher->teacherID) {
                $teacher->teacherID = self::nextId();
            }
        });

        static::saved(function (Teacher $teacher) {
            if ($teacher->pendingSchoolID !== null) {
                // Assign has a composite PK (schoolID+teacherID), which Eloquent's
                // single-key updateOrCreate/save don't handle — use the query
                // builder directly instead.
                $updated = Assign::where('teacherID', $teacher->teacherID)
                    ->update(['schoolID' => $teacher->pendingSchoolID, 'assignDate' => now(), 'status' => 'active']);

                if (! $updated) {
                    Assign::create([
                        'schoolID'   => $teacher->pendingSchoolID,
                        'teacherID'  => $teacher->teacherID,
                        'assignDate' => now(),
                        'status'     => 'active',
                    ]);
                }
            }
        });
    }

    private static function nextId(): string
    {
        $max = DB::table('teacher')->max(DB::raw('CAST(SUBSTRING(teacherID, 3) AS UNSIGNED)'));

        return 'TC' . str_pad((string) (($max ?? 0) + 1), 4, '0', STR_PAD_LEFT);
    }

    // alamin_main_database column is `maritalStatus`; the rest of this app
    // consistently uses the `martialStatus` spelling — map it here so only
    // this integration boundary needs to know about the discrepancy.
    public function getMartialStatusAttribute()
    {
        return $this->attributes['maritalStatus'] ?? null;
    }

    public function setMartialStatusAttribute($value): void
    {
        $this->attributes['maritalStatus'] = $value;
    }

    // Teacher-school assignment lives in the `assign` table, not a column on
    // teacher. Reads/writes of $teacher->schoolID transparently go through
    // the active assign record so existing callers don't need to change.
    public function getSchoolIDAttribute()
    {
        if ($this->pendingSchoolID !== null) {
            return $this->pendingSchoolID;
        }

        if (! $this->teacherID) {
            return null;
        }

        return $this->currentAssign?->schoolID
            ?? Assign::where('teacherID', $this->teacherID)->active()->value('schoolID');
    }

    public function setSchoolIDAttribute($value): void
    {
        $this->pendingSchoolID = $value;
    }

    public function assigns()
    {
        return $this->hasMany(Assign::class, 'teacherID', 'teacherID');
    }

    public function currentAssign()
    {
        return $this->hasOne(Assign::class, 'teacherID', 'teacherID')
            ->active()
            ->latest('assignDate');
    }

    public function school()
    {
        return $this->hasOneThrough(
            School::class,
            Assign::class,
            'teacherID', // FK on assign referencing teacher
            'schoolID',  // FK on school referencing assign.schoolID
            'teacherID', // local key on teacher
            'schoolID',  // local key on assign
        )->whereRaw('LOWER(TRIM(`assign`.`status`)) IN (?, ?)', ['active', 'aktif']);
    }
}
