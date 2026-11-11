<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PdpcScore extends Model
{
    protected $table = 'pdpc_score';
    protected $primaryKey = 'scoreID';
    public $timestamps = false;

    protected $fillable = [
        'responseID',
        'pointID',
        'score',
    ];

    public function response()
    {
        return $this->belongsTo(
            PdpcResponse::class,
            'responseID',
            'responseID'
        );
    }

    public function point()
    {
        return $this->belongsTo(
            PdpcTtPoint::class,
            'pointID',
            'pointID'
        );
    }
}