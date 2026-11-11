<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PdpcForm extends Model
{
    protected $table = 'pdpc_form';
    protected $primaryKey = 'formID';

    protected $fillable = [
        'form_name',
        'instruction',
        'version_no',
        'status',
        'staffID',
    ];

    public function aspects()
    {
        return $this->hasMany(
            PdpcAspect::class,
            'formID',
            'formID'
        )->orderBy('display_order');
    }

    public function responses()
    {
        return $this->hasMany(
            PdpcResponse::class,
            'formID',
            'formID'
        );
    }
}