<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;

class Principal extends Authenticatable
{
    use Notifiable;

    protected $table      = 'Principal';
    protected $primaryKey = 'principalID';
    public    $incrementing = false;
    protected $keyType    = 'string';
    public    $timestamps = false;

    protected $fillable = [
        'principalID', 'principalName', 'ICNumber', 'phoneNumber', 'email',
        'maritalStatus', 'gender', 'address', 'race',
        'appointedDate', 'serviceDate', 'pensionDate', 'latestAge',
        'credit_hour', 'department',
        'password', 'password_change_required', 'role', 'schoolID',
    ];

    protected $hidden = ['password'];

    protected $casts = [
        'password_change_required' => 'boolean',
        'appointedDate'            => 'date',
        'serviceDate'              => 'integer',
        'pensionDate'              => 'date',
    ];

    public function getDisplayName(): string
    {
        return $this->principalName ?? 'Principal';
    }

    // Relationship with School
    public function school()
    {
        return $this->belongsTo(School::class, 'schoolID', 'schoolID');
    }

    // Relationship to get teachers from the same school
    public function teachers()
    {
        return $this->hasMany(Teacher::class, 'schoolID', 'schoolID');
    }

    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->password)) {
                $model->password = Hash::make('password'); // Fixed: Hash the default password
                $model->password_change_required = true;
            }
        });
    }

    /**
     * FIX: Remove the mutator or fix it to prevent double hashing
     * Option 1: Remove the mutator entirely (recommended)
     */
    // public function setPasswordAttribute($value)
    // {
    //     $this->attributes['password'] = Hash::make($value);
    // }

    /**
     * Option 2: Keep the mutator but check if password is already hashed
     */
    public function setPasswordAttribute($value)
    {
        // Check if the value is already a bcrypt hash (starts with $2y$ or $2a$)
        if (is_string($value) && preg_match('/^\$2[ay]\$/', $value)) {
            $this->attributes['password'] = $value;
        } else {
            $this->attributes['password'] = Hash::make($value);
        }
    }
}