<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\EnrollOutsider;
use App\Models\Notification;
use App\Models\Outsider;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OutsiderDashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user     = $request->user();
        $outsider = Outsider::where('user_id', $user->id)->first();

        $enrollments = $outsider
            ? EnrollOutsider::with('course')
                ->where('outsider_id', $outsider->outsider_id)
                ->latest('applied_at')
                ->get()
            : collect();

        $registeredTrainings = $enrollments
            ->where('status', 'approved')
            ->filter(fn ($e) => $e->course && in_array($e->course->status, ['upcoming', 'ongoing']))
            ->values()
            ->map(fn ($e) => [
                'id'         => $e->course->courseID,
                'title'      => $e->course->courseName,
                'mode' => $e->course->mode,
                'organiser'  => $e->course->organiserName,
                'status'     => $e->course->status,
            ]);

        return Inertia::render('dashboard/outsider', [
            'registered_trainings' => $registeredTrainings,
            'pending_applications' => $enrollments->where('status', 'pending')->count(),
            'training_history'     => $enrollments
                ->where('status', 'approved')
                ->filter(fn ($e) => $e->course && $e->course->status === 'completed')
                ->count(),
            'unread_notifications' => Notification::forRecipient($user)
                ->where('is_read', false)
                ->count(),
        ]);
    }
}
