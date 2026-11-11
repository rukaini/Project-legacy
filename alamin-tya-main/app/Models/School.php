<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class School extends Model
{
    protected $table = 'school';
    protected $primaryKey = 'schoolID';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false; // Important - no timestamps
    
    protected $fillable = [
        'schoolID', 'schoolName', 'schoolAddress', 'registerDate', 
        'phoneNumber', 'totalTeacher', 'vacancy', 'capacity'
    ];
    
    // Relationships
    public function teachers()
    {
        return $this->hasMany(Teacher::class, 'schoolID', 'schoolID');
    }
    
    public function applications()
    {
        return $this->hasMany(Application::class, 'schoolID', 'schoolID');
    }
    
    public function guruNews()
    {
        return $this->hasMany(GuruNew::class, 'schoolID', 'schoolID');
    }
    
    // Check if school has vacancies
    public function hasVacancies()
    {
        return $this->vacancy > 0;
    }
    
    // Get available vacancies count
    public function getVacanciesCount()
    {
        return $this->vacancy;
    }
    
    // Fill a vacancy (when teacher is placed)
    public function fillVacancy()
    {
        if ($this->vacancy > 0) {
            $this->decrement('vacancy');
            $this->increment('totalTeacher');
            $this->save();
            return true;
        }
        return false;
    }
}