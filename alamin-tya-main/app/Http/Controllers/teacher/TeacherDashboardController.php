<?php

namespace App\Http\Controllers\teacher;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\View\View;
use Carbon\Carbon;

class TeacherDashboardController extends Controller
{
    /**
     * Display teacher dashboard
     */
    public function index(): View
    {
        // Get teacher data from session
        $teacher = Session::get('user');
        
        // If session doesn't have user object, try to get from individual session keys
        if (!$teacher) {
            $teacher = (object) [
                'teacherID' => Session::get('userID'),
                'teacherName' => Session::get('userName'),
                'role' => Session::get('role'),
                'schoolID' => Session::get('schoolID')
            ];
        }
        
        // Get teacher details from database
        $teacherData = null;
        if ($teacher && isset($teacher->teacherID)) {
            $teacherData = DB::table('teacher')
                ->where('teacherID', $teacher->teacherID)
                ->first();
        }
        
        // If teacher not found in database, use session data
        if (!$teacherData && $teacher) {
            $teacherData = (object) [
                'teacherID' => $teacher->teacherID ?? null,
                'teacherName' => $teacher->teacherName ?? 'Teacher',
                'email' => $teacher->email ?? null,
                'phoneNumber' => $teacher->phoneNumber ?? null,
                'ICNumber' => $teacher->ICNumber ?? null,
                'gender' => $teacher->gender ?? null,
                'address' => $teacher->address ?? null,
                'race' => $teacher->race ?? null,
                'maritalStatus' => $teacher->maritalStatus ?? null,
                'appointedDate' => $teacher->appointedDate ?? null,
                'pensionDate' => $teacher->pensionDate ?? null,
                'serviceDate' => $teacher->serviceDate ?? null,
                'status' => $teacher->status ?? 'Active',
            ];
        }

        $teacherID = $teacherData->teacherID ?? null;
        
        // ===== Get School Information =====
        $schoolName = 'Not Assigned';
        $schoolID = null;
        $schoolAddress = null;
        $schoolPhone = null;
        $assignDate = null; // Service start date from assign table
        
        if ($teacherID) {
            // Get teacher's school from assign table
            $assignment = DB::table('assign')
                ->where('teacherID', $teacherID)
                ->where('status', 'Aktif')
                ->first();
            
            if ($assignment) {
                $schoolID = $assignment->schoolID;
                $assignDate = $assignment->assignDate ?? null; // This is the service start date
                
                // Get school details
                $school = DB::table('school')
                    ->where('schoolID', $schoolID)
                    ->first();
                
                if ($school) {
                    $schoolName = $school->schoolName ?? 'Not Assigned';
                    $schoolAddress = $school->schoolAddress ?? null;
                    $schoolPhone = $school->phoneNumber ?? null;
                }
            }
        }

        // ===== Pension Information =====
        $pensionDate = null;
        $pensionDateFormatted = 'Not Set';
        $daysUntilPension = null;
        $pensionStatus = 'Not Set';
        $pensionStatusClass = 'info';
        $serviceStartDate = null;
        $serviceYears = null;
        $pensionProgress = 0;
        $pensionProgressClass = '';

        // Use assignDate as the service start date
        if ($assignDate) {
            $serviceStartDate = Carbon::parse($assignDate)->format('d/m/Y');
        }

        if ($teacherData && isset($teacherData->pensionDate) && $teacherData->pensionDate) {
            $pensionDate = $teacherData->pensionDate;
            $pensionDateFormatted = Carbon::parse($pensionDate)->format('d/m/Y');
            $now = Carbon::now();
            $pension = Carbon::parse($pensionDate);
            $daysUntilPension = $now->diffInDays($pension, false);
            
            // Determine pension status
            if ($daysUntilPension <= 0) {
                $pensionStatus = 'Retired';
                $pensionStatusClass = 'success';
                $daysUntilPension = 0;
            } elseif ($daysUntilPension <= 30) {
                $pensionStatus = '⚠️ Very Urgent (≤ 30 days)';
                $pensionStatusClass = 'danger';
            } elseif ($daysUntilPension <= 90) {
                $pensionStatus = '⚠️ Urgent (≤ 90 days)';
                $pensionStatusClass = 'warning';
            } elseif ($daysUntilPension <= 180) {
                $pensionStatus = 'Approaching (≤ 180 days)';
                $pensionStatusClass = 'warning';
            } elseif ($daysUntilPension <= 365) {
                $pensionStatus = 'Within 1 Year';
                $pensionStatusClass = 'info';
            } else {
                $pensionStatus = 'On Track';
                $pensionStatusClass = 'success';
            }
            
            // Calculate service years using assignDate as service start date
            if ($assignDate) {
                $start = Carbon::parse($assignDate);
                $serviceYears = $start->diffInYears($now);
                
                $totalService = $start->diffInDays($pension);
                $elapsed = $start->diffInDays($now);
                $pensionProgress = $totalService > 0 ? round(($elapsed / $totalService) * 100) : 0;
                
                if ($pensionProgress > 100) {
                    $pensionProgress = 100;
                }
                
                if ($pensionProgress >= 90) {
                    $pensionProgressClass = 'danger';
                } elseif ($pensionProgress >= 70) {
                    $pensionProgressClass = 'warning';
                } else {
                    $pensionProgressClass = '';
                }
            }
        }

        // ===== Self Information =====
        $selfInfo = [
            'teacherID' => $teacherData->teacherID ?? 'N/A',
            'teacherName' => $teacherData->teacherName ?? 'N/A',
            'email' => $teacherData->email ?? 'Not Set',
            'phoneNumber' => $teacherData->phoneNumber ?? 'Not Set',
            'ICNumber' => $teacherData->ICNumber ?? 'Not Set',
            'gender' => $teacherData->gender ?? 'Not Set',
            'race' => $teacherData->race ?? 'Not Set',
            'maritalStatus' => $teacherData->maritalStatus ?? 'Not Set',
            'address' => $teacherData->address ?? 'Not Set',
            'appointedDate' => isset($teacherData->appointedDate) ? Carbon::parse($teacherData->appointedDate)->format('d/m/Y') : 'Not Set',
            'serviceYears' => $serviceYears ?? 'N/A',
            'status' => $teacherData->status ?? 'Active',
        ];

        // Prepare data for view
        $data = [
            'teacher' => $teacher,
            'teacherData' => $teacherData,
            'schoolName' => $schoolName,
            'schoolID' => $schoolID,
            'schoolAddress' => $schoolAddress,
            'schoolPhone' => $schoolPhone,
            'assignDate' => $assignDate,
            'pensionDate' => $pensionDateFormatted,
            'daysUntilPension' => $daysUntilPension,
            'pensionStatus' => $pensionStatus,
            'pensionStatusClass' => $pensionStatusClass,
            'serviceStartDate' => $serviceStartDate, // Now using assignDate from assign table
            'serviceYears' => $serviceYears,
            'pensionProgress' => $pensionProgress,
            'pensionProgressClass' => $pensionProgressClass,
            'selfInfo' => $selfInfo,
        ];

        return view('teacher.dashboard', $data);
    }
}