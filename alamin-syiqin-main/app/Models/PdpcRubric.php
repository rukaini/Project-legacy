<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PdpcRubric extends Model
{
    protected $table = 'pdpc_rubric';
    protected $primaryKey = 'rubricID';

    public $timestamps = false;

    protected $fillable = [
        'tumsID',
        'score',
        'description',
    ];

    public function tums()
    {
        return $this->belongsTo(
            PdpcTums::class,
            'tumsID',
            'tumsID'
        );
    }
}