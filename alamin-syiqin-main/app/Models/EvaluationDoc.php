<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EvaluationDoc extends Model
{
    protected $table = 'evaluation_doc';

    protected $primaryKey = 'doc_id';

    public $timestamps = false;

    protected $fillable = [
        'form_name',
        'description',
        'file_name',
        'file_path',
        'file_type',
        'uploaded_at',
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
}
