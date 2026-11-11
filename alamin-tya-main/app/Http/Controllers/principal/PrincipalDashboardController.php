<?php

namespace App\Http\Controllers\principal;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\Teacher;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PrincipalDashboardController extends Controller
{
    public function index(): View
    {
        // Check if user is logged in
        if (!Session::has('user')) {
            abort(403, 'Unauthorized access. Please login first.');
        }
        
        $principal = Session::get('user');
        
        // Verify user is a principal
        if (!isset($principal->role) || strtolower($principal->role) !== 'principal') {
            abort(403, 'Unauthorized access.');
        }
        
        // Get school information
        $school = School::find($principal->schoolID);
        
        // Get teacher statistics from ASSIGN table
        $totalTeachers = Teacher::join('ASSIGN', 'Teacher.teacherID', '=', 'ASSIGN.teacherID')
                                ->where('ASSIGN.schoolID', $principal->schoolID)
                                ->count();
        
        $activeTeachers = Teacher::join('ASSIGN', 'Teacher.teacherID', '=', 'ASSIGN.teacherID')
                                 ->where('ASSIGN.schoolID', $principal->schoolID)
                                 ->where(function($q) {
                                     $q->where('ASSIGN.status', 'Aktif')
                                       ->orWhereNull('ASSIGN.status');
                                 })
                                 ->count();
        
        $resignedTeachers = Teacher::join('ASSIGN', 'Teacher.teacherID', '=', 'ASSIGN.teacherID')
                                   ->where('ASSIGN.schoolID', $principal->schoolID)
                                   ->where('ASSIGN.status', 'Berhenti')
                                   ->count();
        
        // School Capacity Information
        $schoolCapacity = $school->capacity ?? 0;
        $vacancy = $school->vacancy ?? 0;
        $totalTeacherFromSchool = $school->totalTeacher ?? 0;
        
        return view('principal.dashboard', compact(
            'principal',
            'school',
            'totalTeachers',
            'activeTeachers',
            'resignedTeachers',
            'schoolCapacity',
            'vacancy',
            'totalTeacherFromSchool'
        ));
    }
}