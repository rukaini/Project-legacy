<?php

namespace App\Http\Controllers\staff;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\View\View;
use Carbon\Carbon;

class StaffDashboardController extends Controller
{
    /**
     * Display staff dashboard
     */
    public function index(): View
    {
        // Get staff data from session
        $staff = Session::get('user');
        
        // If session doesn't have user object, try to get from individual session keys
        if (!$staff) {
            $staff = (object) [
                'staffID' => Session::get('userID'),
                'staffName' => Session::get('userName'),
                'role' => Session::get('role'),
                'department' => Session::get('department')
            ];
        }
        
        // Get staff details from database
        $staffData = null;
        if ($staff && isset($staff->staffID)) {
            $staffData = DB::table('staff')
                ->where('staffID', $staff->staffID)
                ->first();
        }
        
        // If staff not found in database, use session data
        if (!$staffData && $staff) {
            $staffData = (object) [
                'staffID' => $staff->staffID ?? null,
                'staffName' => $staff->staffName ?? 'Staff Member',
                'email' => $staff->email ?? null,
                'phoneNumber' => $staff->phoneNumber ?? null,
                'ICNumber' => $staff->ICNumber ?? null,
                'gender' => $staff->gender ?? null,
                'address' => $staff->address ?? null,
                'race' => $staff->race ?? null,
                'maritalStatus' => $staff->maritalStatus ?? null,
                'appointedDate' => $staff->appointedDate ?? null,
                'pensionDate' => $staff->pensionDate ?? null,
                'serviceDate' => $staff->serviceDate ?? null,
                'department' => $staff->department ?? 'Not Assigned',
                'role' => $staff->role ?? 'Staff',
                'credit_hour' => $staff->credit_hour ?? 0,
                'status' => $staff->status ?? 'Active',
            ];
        }

        // Convert to array for view
        $staffArray = (array) $staffData;

        // Calculate service years
        $serviceYears = null;
        $serviceStartDate = null;
        if (isset($staffData->appointedDate) && $staffData->appointedDate) {
            $start = Carbon::parse($staffData->appointedDate);
            $serviceStartDate = $start->format('d/m/Y');
            $serviceYears = $start->diffInYears(now());
        }

        // ===== Pension Information =====
        $pensionDate = null;
        $pensionDateFormatted = 'Not Set';
        $daysUntilPension = null;
        $pensionStatus = 'Not Set';
        $pensionStatusClass = 'secondary';
        $pensionProgress = 0;
        $pensionProgressClass = '';

        if (isset($staffData->pensionDate) && $staffData->pensionDate) {
            $pensionDate = $staffData->pensionDate;
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
            
            // Calculate progress
            if (isset($staffData->appointedDate) && $staffData->appointedDate) {
                $start = Carbon::parse($staffData->appointedDate);
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

        // Prepare data for view
        $data = [
            'staffData' => $staffArray,
            'serviceYears' => $serviceYears,
            'serviceStartDate' => $serviceStartDate,
            'pensionDate' => $pensionDateFormatted,
            'daysUntilPension' => $daysUntilPension,
            'pensionStatus' => $pensionStatus,
            'pensionStatusClass' => $pensionStatusClass,
            'pensionProgress' => $pensionProgress,
            'pensionProgressClass' => $pensionProgressClass,
        ];

        return view('staff.dashboard', $data);
    }
}