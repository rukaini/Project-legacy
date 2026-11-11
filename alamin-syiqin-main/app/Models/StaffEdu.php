<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class StaffEdu extends Authenticatable
{
    use Notifiable;

    protected $table = 'staff_edu';

    protected $primaryKey = 'staffID';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'staffID',
        'staffName',
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
        'password_changed_required',
        'role',
        'credit_hour',
        'department',
        'status',
    ];

    protected $hidden = [
        'password',
    ];
}