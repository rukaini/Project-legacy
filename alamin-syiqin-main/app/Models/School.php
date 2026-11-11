<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class School extends Model
{
    protected $table = 'school';

    protected $primaryKey = 'schoolID';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

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

    public function guruNews()
    {
        return $this->hasMany(
            GuruNew::class,
            'schoolID',
            'schoolID'
        );
    }

}
