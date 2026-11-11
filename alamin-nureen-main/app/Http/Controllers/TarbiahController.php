<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class TarbiahController extends Controller
{
    private function staff(): string
    {
        $staffID = trim((string) ($_SESSION['staffID'] ?? $_SESSION['staff_id'] ?? ''));
        abort_if($staffID === '', 403, 'Staff EDU login is required.');
        abort_unless(DB::table('staff_edu')->where('staffID', $staffID)
            ->whereRaw("LOWER(COALESCE(status,'active')) = 'active'")->exists(), 403);
        return $staffID;
    }

    public function index(): Response
    {
        $staffID = $this->staff();
        $ready = Schema::hasTable('staff_tarbiah_attendance') && Schema::hasColumn('tarbiah', 'credit_hours');
        $activities = $ready ? DB::table('tarbiah as t')
            ->leftJoin('school as s', 's.schoolID', '=', 't.schoolID')
            ->leftJoin('staff_tarbiah_attendance as sta', function ($join) use ($staffID) {
                $join->on('sta.tarbiah_id', '=', 't.tarbiah_id')->where('sta.staffID', '=', $staffID);
            })
            ->orderByRaw('t.session_date >= CURRENT_DATE DESC')->orderBy('t.session_date')
            ->select('t.*', 's.schoolName', 'sta.attendance_status as my_status')->get() : collect();
        $trainingReady = Schema::hasTable('trainhub_staff_enrollments');
        $training = null;
        // Do not refer to the new table in SQL until the additive patch exists.
        if (!$trainingReady) {
            $training = DB::table('course as c')
                ->leftJoin('course_participant as cp', function ($join) use ($staffID) {
                    $join->on('cp.courseID', '=', 'c.courseID')
                         ->where('cp.participantType', '=', 'staff')->where('cp.staffID', '=', $staffID);
                })->select('c.*','cp.participantID as my_participant');
        } else {
            $training = DB::table('course as c')
                ->leftJoin('course_participant as cp', function ($join) use ($staffID) {
                    $join->on('cp.courseID','=','c.courseID')->where('cp.participantType','=','staff')
                         ->where('cp.staffID','=',$staffID);
                })
                ->leftJoin('trainhub_staff_enrollments as e', function ($join) use ($staffID) {
                    $join->on('e.courseID','=','c.courseID')->where('e.staffID','=',$staffID);
                })->select('c.*','cp.participantID as my_participant','e.enrolled_at as my_enrollment');
        }
        $training = $training->orderBy('c.courseName')->get()->map(function ($c) {
            $c->is_pic = in_array((string)($_SESSION['staffID'] ?? $_SESSION['staff_id'] ?? ''),
                array_filter(array_map('trim', explode(',', (string)($c->staffAttendeeIDs ?? '')))), true);
            return $c;
        });
        $sessionDates = DB::table('course_session')->selectRaw('courseID, MIN(sessionDate) AS first_date, MAX(sessionDate) AS last_date')
            ->groupBy('courseID')->get()->keyBy('courseID');
        $managementOrdersReady = Schema::hasTable('trainhub_staff_training_orders');
        $staffToVerify = $managementOrdersReady ? DB::table('course_participant as cp')
            ->join('course as c', 'c.courseID','=','cp.courseID')
            ->where('cp.participantType','staff')->whereNotNull('cp.staffID')
            ->select('cp.staffID','cp.participantName','cp.courseID','c.courseName','c.organiserName')
            ->orderBy('c.courseName')->orderBy('cp.participantName')->limit(250)->get()
            ->filter(fn($p) => stripos((string)$p->organiserName, 'Al Amin Edu Oasis') === false)
            : collect();
        return response()->view('tarbiah.index', compact('activities', 'staffID', 'ready',
            'trainingReady','training','sessionDates','staffToVerify','managementOrdersReady'));
    }

    public function enroll(Request $request, int $tarbiahID): Response
    {
        $staffID = $this->staff();
        abort_unless(Schema::hasTable('staff_tarbiah_attendance'), 503, 'Staff Tarbiah table is unavailable.');
        $activity = DB::table('tarbiah')->where('tarbiah_id', $tarbiahID)->first();
        abort_unless($activity, 404);
        if ($activity->session_date < date('Y-m-d')) {
            return back()->with('error', 'This Tarbiah activity has already ended.');
        }
        try {
            DB::table('staff_tarbiah_attendance')->insert([
                'staffID' => $staffID, 'tarbiah_id' => $tarbiahID,
                'attendance_status' => 'pending', 'created_at' => now(), 'updated_at' => now(),
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            if ((int) ($e->errorInfo[1] ?? 0) === 1062) {
                return back()->with('error', 'You have already joined this activity.');
            }
            throw $e;
        }
        DB::table('audit_log')->insert([
            'staffID' => $staffID, 'userName' => (string) ($_SESSION['staffName'] ?? $staffID),
            'actionType' => 'INSERT', 'tableName' => 'staff_tarbiah_attendance',
            'newValue' => 'Staff EDU joined Tarbiah '.$tarbiahID,
        ]);
        return back()->with('success', "Enrollment recorded. Attendance status is managed by the external Tarbiah system; approved records use Tarbiah's recorded credit hours.");
    }

}
