<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PdpcTtPoint extends Model
{
    protected $table = 'pdpc_tt_point';
    protected $primaryKey = 'pointID';

    public $timestamps = false;
    
    protected $fillable = [
        'ttID',
        'point_text',
        'display_order',
    ];

    public function tt()
    {
        return $this->belongsTo(
            PdpcTt::class,
            'ttID',
            'ttID'
        );
    }

    public function scores()
    {
        return $this->hasMany(
            PdpcScore::class,
            'pointID',
            'pointID'
        );
    }
}