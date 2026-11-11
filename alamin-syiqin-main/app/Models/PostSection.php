<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PostSection extends Model
{
    protected $table = 'post_section';

    protected $primaryKey = 'sectionID';

    public $timestamps = false;

    protected $fillable = [
        'section_name',
        'display_order',
        'formID',
    ];

    public function form()
    {
        return $this->belongsTo(
            PostForm::class,
            'formID',
            'formID'
        );
    }

    public function fields()
    {
        return $this->hasMany(
            PostField::class,
            'sectionID',
            'sectionID'
        );
    }
}