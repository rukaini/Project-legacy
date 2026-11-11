<?php

namespace App\Http\Controllers;

use App\Events\OutsiderApplicationSubmitted;
use App\Models\EnrollOutsider;
use App\Models\Outsider;
use App\Models\Training;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class OutsiderApplicationController extends Controller
{
    public function store(Request $request, Training $training): RedirectResponse
    {
        abort_unless(
            $training->isSimple(),
            403, 'Outsiders may only apply for single or multi-day courses.'
        );

        abort_unless(
            $training->status === 'upcoming',
            422, 'This course is not open for registration.'
        );

        $outsider = Outsider::where('user_id', $request->user()->id)->first();
        abort_unless($outsider, 403, 'No outsider profile found for your account.');

        abort_if(
            EnrollOutsider::where('outsider_id', $outsider->outsider_id)
                ->where('course_id', $training->getKey())
                ->exists(),
            422, 'You have already applied for this course.'
        );

        $firstSession = $training->courseSessions()->orderBy('sessionDate')->first();
        abort_if(
            $firstSession && now()->startOfDay()->gte(Carbon::parse($firstSession->session_date)->startOfDay()),
            422, 'Registration deadline has passed.'
        );

        $approvedCount = $training->applications()->where('status', 'approved')->count()
            + $training->outsiderEnrollments()->where('status', 'approved')->count()
            + $training->guruBaruEnrollments()->where('status', 'approved')->count();
        abort_if($approvedCount >= $training->capacity, 422, 'This course is full.');

        $enrollment = EnrollOutsider::create([
            'outsider_id' => $outsider->outsider_id,
            'courseID'   => $training->getKey(),
            'status'      => 'pending',
            'applied_at'  => now(),
        ]);

        event(new OutsiderApplicationSubmitted($enrollment));

        return redirect()->route('trainings.show', $training->getKey())
            ->with('success', 'Your application has been submitted.');
    }
}
