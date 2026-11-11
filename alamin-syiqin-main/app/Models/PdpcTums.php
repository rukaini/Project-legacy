<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PdpcTums extends Model
{
    protected $table = 'pdpc_tums';
    protected $primaryKey = 'tumsID';
    public $timestamps = false;

    protected $fillable = [
        'aspectID',
        'tums_code',
        'tums_name',
        'wajaran',
        'display_order',
    ];

    public function aspect()
    {
        return $this->belongsTo(
            PdpcAspect::class,
            'aspectID',
            'aspectID'
        );
    }

    public function tt()
    {
        return $this->hasMany(
            PdpcTt::class,
            'tumsID',
            'tumsID'
        )->orderBy('display_order');
    }

    public function rubrics()
    {
        return $this->hasMany(
            PdpcRubric::class,
            'tumsID',
            'tumsID'
        )->orderByDesc('score');
    }
}
