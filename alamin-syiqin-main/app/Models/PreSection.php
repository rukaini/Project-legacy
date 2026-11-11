<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PreSection extends Model
{
    protected $table = 'pre_section';
    protected $primaryKey = 'sectionID';
    public $timestamps = false;

    protected $fillable = [
        'formID',
        'section_name',
        'display_order',
    ];

    public function form()
    {
        return $this->belongsTo(
            PreForm::class,
            'formID',
            'formID'
        );
    }

    public function criteria()
    {
        return $this->hasMany(
            PreCriteria::class,
            'sectionID',
            'sectionID'
        );
    }
}