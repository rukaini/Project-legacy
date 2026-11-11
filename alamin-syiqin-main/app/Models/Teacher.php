<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Teacher extends Authenticatable
{
    use Notifiable;

    protected $table = 'teacher';

    protected $primaryKey = 'teacherID';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

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
        'password_change_required',
        'schoolID',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'appointedDate' => 'date',
        'serviceDate' => 'date',
        'pensionDate' => 'date',
    ];

    public function observer()
    {
        return $this->hasOne(Observer::class, 'teacherID', 'teacherID');
    }

    public function externalObserver()
    {
        return $this->hasOne(ExternalObserver::class, 'teacherID', 'teacherID');
    }
}
