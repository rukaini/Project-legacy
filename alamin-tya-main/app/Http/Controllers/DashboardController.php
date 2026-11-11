<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Models\Teacher;
use App\Models\Staff;
use App\Models\Principal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    /**
     * Run one dashboard query on its own; on failure log it, remember the
     * message (shown as a banner) and return a default.
     */
    private function safe(string $label, callable $fn, $default, array &$problems)
    {
        try {
            return $fn();
        } catch (\Throwable $e) {
            Log::error("Dashboard [{$label}] failed: " . $e->getMessage());
            $problems[] = "{$label}: " . preg_replace('/\s+\(Connection:.*$/s', '', $e->getMessage());
            return $default;
        }
    }

    public function index()
    {
        $problems = [];

        // Uses only columns that exist in the database schema:
        // School(schoolID, schoolName, totalTeacher, vacancy), Principal(schoolID, pensionDate),
        // Teacher/Staff(pensionDate), Assign(schoolID, teacherID, assignDate, status)
        $totalSchools    = $this->safe('Total schools',    fn () => School::count(),    0, $problems);
        $totalTeachers   = $this->safe('Total teachers',   fn () => Teacher::count(),   0, $problems);
        $totalStaff      = $this->safe('Total staff',      fn () => Staff::count(),     0, $problems);
        $totalPrincipals = $this->safe('Total principals', fn () => Principal::whereNotNull('schoolID')->count(), 0, $problems);
        $vacancies       = $this->safe('Vacancies',        fn () => (int) School::sum('vacancy'), 0, $problems);

        // Schools that have no principal row pointing at them
        $schoolsWithoutPrincipal = $this->safe('Schools without principal', fn () => School::whereNotIn(
                'schoolID',
                Principal::whereNotNull('schoolID')->select('schoolID')
            )
            ->select('schoolID', 'schoolName', 'totalTeacher', 'vacancy')
            ->orderBy('schoolName')
            ->get(), collect(), $problems);

        $totalSchoolsWithoutPrincipal = $schoolsWithoutPrincipal->count();

        $schools = $this->safe('School list', fn () => School::select('schoolID', 'schoolName', 'totalTeacher', 'vacancy')
            ->orderBy('schoolName')
            ->get(), collect(), $problems);

        $latestAssign = $this->safe('Latest assignment', fn () => DB::table('assign')
            ->join('teacher', 'assign.teacherID', '=', 'teacher.teacherID')
            ->join('school', 'assign.schoolID', '=', 'school.schoolID')
            ->select('teacher.teacherName', 'school.schoolName', 'assign.assignDate')
            ->orderByDesc('assign.assignDate')
            ->first(), null, $problems);

        // Retiring within 6 months (pensionDate exists on all three tables)
        $retiringSoon = collect();
        foreach ([
            [Teacher::class,   'teacherID',   'teacherName',   'teacher'],
            [Principal::class, 'principalID', 'principalName', 'principal'],
            [Staff::class,     'staffID',     'staffName',     'staff'],
        ] as [$model, $idCol, $nameCol, $type]) {
            $rows = $this->safe("Retiring soon ({$type})", fn () => $model::whereNotNull('pensionDate')
                ->where('pensionDate', '>=', now())
                ->where('pensionDate', '<=', now()->addMonths(6))
                ->select("{$idCol} as id", "{$nameCol} as name", DB::raw("'{$type}' as type"), 'pensionDate')
                ->orderBy('pensionDate', 'asc')
                ->get(), collect(), $problems);
            $retiringSoon = $retiringSoon->concat($rows);
        }
        $retiringSoon = $retiringSoon->sortBy('pensionDate');

        $error = empty($problems) ? null : implode(' | ', $problems);

        return view('hr.dashboard', compact(
            'totalSchools',
            'totalTeachers',
            'totalStaff',
            'totalPrincipals',
            'vacancies',
            'schools',
            'latestAssign',
            'retiringSoon',
            'schoolsWithoutPrincipal',
            'totalSchoolsWithoutPrincipal',
            'error'
        ));
    }

    // Navigation Methods
    public function school()
    {
        return view('school.school');
    }

    public function teacher()
    {
        return view('teacher.teacher');
    }

    public function placement()
    {
        return view('placement.placement');
    }

    public function placementRecords()
    {
        return view('placement.records');
    }

    public function offer()
    {
        return view('offer.offer');
    }

    public function letter()
    {
        return view('letter.letter');
    }
}