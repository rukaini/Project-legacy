<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\EnrollGuruBaru;
use App\Models\GuruNew;
use App\Models\Notification;
use App\Models\Training;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GuruBaruDashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user    = $request->user();
        $guruNew = GuruNew::where('email', $user->email)->first();

        // Series courses: auto-enrolled
        $seriesCount = Training::lms()
            ->whereIn('status', ['upcoming', 'ongoing'])
            ->count();

        // Single/multi_day applications
        $pendingCount  = 0;
        $approvedCount = 0;
        $recentApplications = collect();

        if ($guruNew) {
            $pendingCount  = EnrollGuruBaru::where('gn_id', $guruNew->gn_id)
                ->where('status', 'pending')->count();
            $approvedCount = EnrollGuruBaru::where('gn_id', $guruNew->gn_id)
                ->where('status', 'approved')->count();

            $recentApplications = EnrollGuruBaru::with('course')
                ->where('gn_id', $guruNew->gn_id)
                ->where('status', '!=', 'auto_enrolled')
                ->orderBy('applied_at', 'desc')
                ->take(4)
                ->get()
                ->map(fn (EnrollGuruBaru $e) => [
                    'courseID'   => $e->courseID,
                    'courseName' => $e->course?->courseName ?? '—',
                    'category'    => $e->course?->course_category,
                    'status'      => $e->status,
                    'applied_at'  => $e->applied_at,
                ]);
        }

        // Available single/multi_day courses to apply for
        $openCourses = Training::simple()
            ->where('status', 'upcoming')
            ->orderBy('courseName')
            ->take(4)
            ->get()
            ->map(fn ($c) => [
                'id'        => $c->courseID,
                'name'      => $c->courseName,
                'category'  => $c->course_category,
                'mode'      => $c->mode,
                'organiser' => $c->organiserName,
                'price'     => $c->price,
            ]);

        return Inertia::render('dashboard/guru-baru', [
            'applicant'           => $guruNew ? [
                'id'           => $guruNew->gn_id,
                'full_name'    => $guruNew->gn_name,
                'email'        => $guruNew->email,
                'phone_number' => $guruNew->phone_number,
            ] : null,
            'series_count'        => $seriesCount,
            'pending_count'       => $pendingCount,
            'approved_count'      => $approvedCount,
            'open_courses'        => $openCourses,
            'recent_applications' => $recentApplications,
            'unread_notifications' => Notification::forRecipient($user)
                ->where('is_read', false)
                ->count(),
        ]);
    }
}
