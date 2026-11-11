<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PreCriteria extends Model
{
    protected $table = 'pre_criteria';
    protected $primaryKey = 'criteriaID';
    public $timestamps = false;

    protected $fillable = [
        'sectionID',
        'criteria_label',
        'display_order',
    ];

    public function section()
    {
        return $this->belongsTo(
            PreSection::class,
            'sectionID',
            'sectionID'
        );
    }

    public function scores()
    {
        return $this->hasMany(
            PreScore::class,
            'criteriaID',
            'criteriaID'
        );
    }
}