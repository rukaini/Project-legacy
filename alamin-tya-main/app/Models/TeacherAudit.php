<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeacherAudit extends Model
{
    // Important: Your table name in the DB is likely 'teacher_audit' (based on the previous error)
    protected $table = 'teacher_audit'; 

    // Match these to your actual column names from the screenshot
    protected $fillable = [
        'teacherID', 'status', 'oldData', 'newData', 'actionDate', 'action'
    ];

    public $timestamps = false; // Keep this as false since you have your own actionDate
}