<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PreScore extends Model
{
    protected $table = 'pre_score';
    protected $primaryKey = 'scoreID';
    public $timestamps = false;

    protected $fillable = [
        'responseID',
        'criteriaID',
        'score',
    ];

    public function response()
    {
        return $this->belongsTo(
            PreResponse::class,
            'responseID',
            'responseID'
        );
    }

    public function criteria()
    {
        return $this->belongsTo(
            PreCriteria::class,
            'criteriaID',
            'criteriaID'
        );
    }
}