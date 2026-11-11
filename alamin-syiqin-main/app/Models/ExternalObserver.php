<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExternalObserver extends Model
{
    protected $table = 'external_observer';

    protected $primaryKey = 'externalObserverID';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'externalObserverID',
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
