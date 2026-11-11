<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditObservation extends Model
{
    protected $table = 'audit_observation';

    protected $primaryKey = 'audit_id';

    public $timestamps = false;

    protected $fillable = [
        'teacherID',
        'gn_id',
        'role',
        'stage',
        'form_name',
        'action',
        'audit_date',
        'audit_time',
    ];

    protected $casts = [
        'audit_date' => 'date',
    ];


    // Observer / External Observer who submitted the form
    public function teacher()
    {
        return $this->belongsTo(
            Teacher::class,
            'teacherID',
            'teacherID'
        );
    }


    // New teacher who was observed
    public function guruNew()
    {
        return $this->belongsTo(
            GuruNew::class,
            'gn_id',
            'gn_id'
        );
    }
}