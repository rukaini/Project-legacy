<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PostFieldOption extends Model
{
    protected $table = 'post_field_option';

    protected $primaryKey = 'optionID';

    public $timestamps = false;

    protected $fillable = [
        'fieldID',
        'option_label',
        'display_order',
    ];

    public function field()
    {
        return $this->belongsTo(
            PostField::class,
            'fieldID',
            'fieldID'
        );
    }
}