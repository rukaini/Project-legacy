<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PdpcTt extends Model
{
    protected $table = 'pdpc_tt';
    protected $primaryKey = 'ttID';

    public $timestamps = false;

    protected $fillable = [
        'tumsID',
        'display_order',
    ];

    public function tums()
    {
        return $this->belongsTo(
            PdpcTums::class,
            'tumsID',
            'tumsID'
        );
    }

    public function points()
    {
        return $this->hasMany(
            PdpcTtPoint::class,
            'ttID',
            'ttID'
        )->orderBy('display_order');
    }

}