<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PostField extends Model
{
    protected $table = 'post_field';

    protected $primaryKey = 'fieldID';

    public $timestamps = false;

    protected $fillable = [
        'sectionID',
        'field_label',
        'field_type',
        'display_order',
        'is_required',
    ];

    public function section()
    {
        return $this->belongsTo(
            PostSection::class,
            'sectionID',
            'sectionID'
        );
    }

    public function options()
    {
        return $this->hasMany(
            PostFieldOption::class,
            'fieldID',
            'fieldID'
        );
    }

    public function answers()
    {
        return $this->hasMany(
            PostAnswer::class,
            'fieldID',
            'fieldID'
        );
    }
}
