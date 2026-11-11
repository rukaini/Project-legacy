<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Carbon\Carbon;

class Teacher extends Authenticatable
{
    use HasFactory;
    
    protected $table = 'teacher';

    protected $primaryKey = 'teacherID';
    public $incrementing = false;
    protected $keyType = 'string';

    public $timestamps = false; // Add this if teacher table doesn't have timestamps

    protected $fillable = [
        'teacherID',
        'teacherName',
        'ICNumber',
        'phoneNumber',
        'email',
        'maritalStatus',
        'gender',
        'address',
        'race',
        'appointedDate',
        'serviceDate',
        'pensionDate',
        'latestAge',
        'password',
        'role',
        'password_change_required',
        'resignation_request_status',
        'resignation_request_date',
        'resignation_request_reason',
        'resignation_approved_date',
        'resignation_remarks'
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'appointedDate' => 'date',
        'pensionDate' => 'date',
        'serviceDate' => 'integer',
        'latestAge' => 'integer',
        'password_change_required' => 'boolean',
        'resignation_request_date' => 'datetime',
        'resignation_approved_date' => 'datetime',
    ];

    // Accessor for formatted service years
    public function getServiceYearsFormattedAttribute()
    {
        if ($this->serviceDate) {
            return $this->serviceDate . ' years';
        }
        return 'N/A';
    }

    // Accessor for service years (just the number)
    public function getServiceYearsAttribute()
    {
        return $this->serviceDate;
    }

    // Calculate years of service on the fly if not stored
    public function getCalculatedServiceYearsAttribute()
    {
        if ($this->appointedDate) {
            return Carbon::parse($this->appointedDate)->diffInYears(now());
        }
        return null;
    }

    /**
     * Get all assignments for this teacher
     */
    public function assignments()
    {
        return $this->hasMany(Assign::class, 'teacherID', 'teacherID')
            ->orderBy('assignDate', 'desc');
    }

    /**
     * Get the current/latest assignment (most recent by assignDate)
     */
    public function currentAssignment()
    {
        return $this->hasOne(Assign::class, 'teacherID', 'teacherID')
            ->orderBy('assignDate', 'desc');
    }

    /**
     * Get the school through the current assignment
     * Uses hasOneThrough relationship
     */
    public function school()
    {
        return $this->hasOneThrough(
            School::class,
            Assign::class,
            'teacherID',  // Foreign key on Assign table
            'schoolID',   // Foreign key on School table
            'teacherID',  // Local key on Teacher table
            'schoolID'    // Local key on Assign table
        )->orderBy('assign.assignDate', 'desc');
    }

    /**
     * Accessor to get school name directly
     */
    public function getSchoolNameAttribute()
    {
        if ($this->school) {
            return $this->school->schoolName;
        }
        return null;
    }

    /**
     * Accessor to get school ID directly
     */
    public function getSchoolIdAttribute()
    {
        if ($this->school) {
            return $this->school->schoolID;
        }
        return null;
    }

    /**
     * Check if teacher has a school assigned
     */
    public function hasSchool()
    {
        return $this->school()->exists();
    }
}