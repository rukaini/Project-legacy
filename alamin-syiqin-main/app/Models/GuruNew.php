<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class GuruNew extends Authenticatable
{
    use Notifiable;

    protected $table = 'guru_new';

    protected $primaryKey = 'gn_id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = true;

    protected $fillable = [
        'gn_id',
        'ic_number',
        'gn_name',
        'phone_number',
        'email',
        'marital_status',
        'gender',
        'address',
        'race',
        'appointed_date',
        'role',
        'current_status',
        'hrid',
        'schoolID',
        'password',
    ];

    protected $hidden = [
        'password',
    ];

    public function school()
    {
        return $this->belongsTo(
            School::class,
            'schoolID',
            'schoolID'
        );
    }

    public function hr()
    {
        return $this->belongsTo(
            HRAdministrator::class,
            'hrID',
            'hrid'
        );
    }
}
