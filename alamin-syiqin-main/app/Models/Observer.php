<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Observer extends Model
{
    protected $table = 'observer';

    protected $primaryKey = 'observerID';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'observerID',
        'teacherID',
        'startDate',
        'endDate',
        'status',
    ];


    public function teacher()
    {
        return $this->belongsTo(
            Teacher::class,
            'teacherID',
            'teacherID'
        );
    }
}
