<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\EnrollGuruBaru;
use App\Models\GuruNew;
use App\Models\EnrollOutsider;
use App\Models\Outsider;
use App\Models\School;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HrDashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $totalTeachers  = Teacher::count();
        $totalOutsiders = Outsider::count();
        $totalGuruBaru  = GuruNew::count();
        $totalSchools   = School::count();

        $pendingOutsider = EnrollOutsider::where('status', 'pending')->count();
        $pendingGuruBaru = EnrollGuruBaru::where('status', 'pending')->count();
        $pendingTotal    = $pendingOutsider + $pendingGuruBaru;

        $thisMonth = now()->startOfMonth();
        $approvedThisMonth = EnrollOutsider::where('status', 'approved')->where('applied_at', '>=', $thisMonth)->count()
            + EnrollGuruBaru::where('status', 'approved')->where('applied_at', '>=', $thisMonth)->count();

        // Outsider status breakdown
        $outsiderStats = [
            'pending'  => EnrollOutsider::where('status', 'pending')->count(),
            'approved' => EnrollOutsider::where('status', 'approved')->count(),
            'rejected' => EnrollOutsider::where('status', 'rejected')->count(),
        ];

        // Guru Baru status breakdown
        $guruBaruStats = [
            'pending'  => EnrollGuruBaru::where('status', 'pending')->count(),
            'approved' => EnrollGuruBaru::where('status', 'approved')->count(),
            'rejected' => EnrollGuruBaru::where('status', 'rejected')->count(),
        ];

        // Teachers by school
        $schools = School::withCount('teachers')->orderBy('schoolName')->get()->map(fn ($s) => [
            'id'             => $s->schoolID,
            'name'           => $s->schoolName,
            'teacher_count'  => $s->teachers_count,
        ]);

        // Recent pending applications (latest 8)
        $recentOutsider = EnrollOutsider::with(['outsider', 'course'])
            ->where('status', 'pending')
            ->latest('applied_at')
            ->limit(5)
            ->get()
            ->map(fn ($e) => [
                'id'           => $e->outsider_id . '-' . $e->courseID,
                'type'         => 'outsider',
                'outsider_id'  => $e->outsider_id,
                'courseID'    => $e->courseID,
                'name'         => $e->outsider?->name ?? '—',
                'training'     => $e->course?->courseName ?? '—',
                'applied_at'   => $e->applied_at,
            ]);

        $recentGuruBaru = EnrollGuruBaru::with(['applicant', 'course'])
            ->where('status', 'pending')
            ->latest('applied_at')
            ->limit(5)
            ->get()
            ->map(fn ($e) => [
                'id'            => 'gb-' . $e->enrollment_id,
                'type'          => 'guru_baru',
                'enrollment_id' => $e->enrollment_id,
                'courseID'     => $e->courseID,
                'name'          => $e->applicant?->gn_name ?? '—',
                'training'      => $e->course?->courseName ?? '—',
                'applied_at'    => $e->applied_at,
            ]);

        $recentPending = $recentOutsider->concat($recentGuruBaru)
            ->sortByDesc('applied_at')
            ->values()
            ->take(8);

        return Inertia::render('dashboard/hr', [
            'stats' => [
                'total_schools'        => $totalSchools,
                'total_teachers'       => $totalTeachers,
                'total_outsiders'      => $totalOutsiders,
                'total_guru_baru'      => $totalGuruBaru,
                'pending_total'        => $pendingTotal,
                'approved_this_month'  => $approvedThisMonth,
            ],
            'outsider_stats'  => $outsiderStats,
            'guru_baru_stats' => $guruBaruStats,
            'schools'         => $schools,
            'recent_pending'  => $recentPending,
        ]);
    }
}
