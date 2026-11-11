<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PostAnswer extends Model
{
    protected $table = 'post_answer';

    protected $primaryKey = 'answerID';

    public $timestamps = false;

    protected $fillable = [
        'answer_value',
        'responseID',
        'fieldID',
    ];

    public function response()
    {
        return $this->belongsTo(
            PostResponse::class,
            'responseID',
            'responseID'
        );
    }

    public function field()
    {
        return $this->belongsTo(
            PostField::class,
            'fieldID',
            'fieldID'
        );
    }
}