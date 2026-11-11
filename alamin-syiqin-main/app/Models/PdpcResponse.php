<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PdpcResponse extends Model
{
    protected $table = 'pdpc_response';
    protected $primaryKey = 'responseID';

    protected $fillable = [
        'formID',
        'gn_id',
        'observerID',
        'externalObserverID',
        'observation_stage',
        'attempt_no',
        'class_name',
        'subject_name',
        'observation_date',
        'observation_time',
        'total_score',
        'percentage',
        'achievement_level',
        'result',
        'status',
    ];

    protected $casts = [
        'observation_date' => 'date',
        'percentage' => 'decimal:2',
    ];

    public function form()
    {
        return $this->belongsTo(
            PdpcForm::class,
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

    public function scores()
    {
        return $this->hasMany(
            PdpcScore::class,
            'responseID',
            'responseID'
        );
    }
}