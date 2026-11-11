<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PreSectionComment extends Model
{
    protected $table = 'pre_section_comment';
    protected $primaryKey = 'sectionCommentID';
    public $timestamps = false;

    protected $fillable = [
        'responseID',
        'sectionID',
        'comment',
    ];

    public function response()
    {
        return $this->belongsTo(
            PreResponse::class,
            'responseID',
            'responseID'
        );
    }

    public function section()
    {
        return $this->belongsTo(
            PreSection::class,
            'sectionID',
            'sectionID'
        );
    }
}