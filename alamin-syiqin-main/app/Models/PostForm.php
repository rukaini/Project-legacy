<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PostForm extends Model
{
    protected $table = 'post_form';

    protected $primaryKey = 'formID';

    public $timestamps = false;

    protected $fillable = [
        'form_name',
        'version',
        'instruction',
        'status',
        'staffID',
    ];

    public function admin()
    {
        return $this->belongsTo(
            StaffEdu::class,
            'staffID',
            'staffID'
        );
    }

    public function sections()
    {
        return $this->hasMany(
            PostSection::class,
            'formID',
            'formID'
        );
    }

    public function responses()
    {
        return $this->hasMany(
            PostResponse::class,
            'formID',
            'formID'
        );
    }
}
