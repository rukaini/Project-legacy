<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PdpcAspect extends Model
{
    protected $table = 'pdpc_aspect';
    protected $primaryKey = 'aspectID';

    public $timestamps = false;

    protected $fillable = [
        'formID',
        'aspect_code',
        'aspect_name',
        'display_order',
    ];

    public function form()
    {
        return $this->belongsTo(
            PdpcForm::class,
            'formID',
            'formID'
        );
    }

    public function tums()
    {
        return $this->hasMany(
            PdpcTums::class,
            'aspectID',
            'aspectID'
        )->orderBy('display_order');
    }
}