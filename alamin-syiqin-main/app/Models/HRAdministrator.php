<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class HRAdministrator extends Authenticatable
{
    use Notifiable;

    protected $table = 'hr_administrator';
    protected $primaryKey = 'hrid';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'hrid',
        'username',
        'email',
        'password',
        'phone_number',
        'role',
        'password_change_required',
    ];

    protected $hidden = [
        'password',
    ];
}