<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;

class Staff extends Authenticatable
{
    use Notifiable;

    protected $table = 'Staff';
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
        'credit_hour',
        'department',
        'status',
        'password',
        'password_change_required',
        'role',
    ];

    protected $hidden = ['password'];

    protected $casts = [
        'password_change_required' => 'boolean',
        'appointedDate' => 'date',
        'serviceDate' => 'date',
        'pensionDate' => 'date',
    ];

    public function getDisplayName(): string
    {
        return $this->staffName ?? 'Staff';
    }

    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->password)) {
                $model->password = Hash::make('password');
                $model->password_change_required = true;
            }
        });
    }

    // Remove the setPasswordAttribute mutator completely
    // Let the controller handle hashing
}