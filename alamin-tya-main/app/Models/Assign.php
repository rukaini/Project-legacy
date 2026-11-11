<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Assign extends Model
{
    protected $table = 'assign';
    protected $primaryKey = 'assignID';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false; // Important - no timestamps
    
    protected $fillable = [
        'assignID',
        'teacherID',
        'schoolID',
        'assignDate',
        'status',
        // add other fields as needed
    ];
    
    // Relationships
    public function teacher()
    {
        return $this->belongsTo(Teacher::class, 'teacherID', 'teacherID');
    }
    
    public function school()
    {
        return $this->belongsTo(School::class, 'schoolID', 'schoolID');
    }
}