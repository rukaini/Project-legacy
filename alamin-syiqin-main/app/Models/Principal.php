<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Principal extends Authenticatable
{
    use Notifiable;

    protected $table = 'principal';

    protected $primaryKey = 'principalID';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'principalID',
        'principalName',
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
        'role',
        'status',
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

    public function school()
    {
        return $this->belongsTo(
            School::class,
            'schoolID',
            'schoolID'
        );
    }
}