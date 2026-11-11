<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PostResponse extends Model
{
    protected $table = 'post_response';
    protected $primaryKey = 'responseID';

    public $timestamps = false;

    protected $fillable = [
        'observation_stage',
        'attempt_no',
        'class_name',
        'subject_name',
        'observation_date',
        'observation_time',
        'status',
        'formID',
        'observerID',
        'externalObserverID',
        'gn_id',
    ];

    public function form()
    {
        return $this->belongsTo(
            PostForm::class,
            'formID',
            'formID'
        );
    }

    public function guruNew()
    {
        return $this->belongsTo(
            GuruNew::class,
            'gn_id',
            'gn_id'
        );
    }

    public function observer()
    {
        return $this->belongsTo(
            Observer::class,
            'observerID',
            'observerID'
        );
    }

    public function externalObserver()
    {
        return $this->belongsTo(
            ExternalObserver::class,
            'externalObserverID',
            'externalObserverID'
        );
    }

    public function answers()
    {
        return $this->hasMany(
            PostAnswer::class,
            'responseID',
            'responseID'
        );
    }
}