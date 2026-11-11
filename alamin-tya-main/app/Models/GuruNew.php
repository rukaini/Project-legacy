<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GuruNew extends Model
{
    protected $table = 'guru_new';
    protected $primaryKey = 'gn_id';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;
    
    protected $fillable = [
        'gn_id', 'current_status', 'assign_status', 'join_date', 
        'class', 'subject_teaching', 'applicant_id', 'application_id', 
        'schoolID', 'teacherID'
    ];
    
    // Relationship with Applicant
    public function applicant()
    {
        return $this->belongsTo(Applicant::class, 'applicant_id', 'applicant_id');
    }
    
    // Relationship with School
    public function school()
    {
        return $this->belongsTo(School::class, 'schoolID', 'schoolID');
    }
    
    // Relationship with Teacher
    public function teacher()
    {
        return $this->belongsTo(Teacher::class, 'teacherID', 'teacherID');
    }
}