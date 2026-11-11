<?php

namespace App\Http\Controllers;

use App\Events\GuruBaruApplicationSubmitted;
use App\Models\GuruNew;
use App\Models\EnrollGuruBaru;
use App\Models\ParticipantMaterialAccess;
use App\Models\Training;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GuruBaruApplicationController extends Controller
{
    public function store(Request $request, Training $training): RedirectResponse
    {
        $user      = $request->user();
        $guruNew = GuruNew::where('email', $user->email)->firstOrFail();

        abort_unless(
            $training->isSimple(),
            422,
            'Series courses do not require an application — you are already enrolled.'
        );

        $alreadyApplied = EnrollGuruBaru::where('gn_id', $guruNew->gn_id)
            ->where('course_id', $training->getKey())
            ->exists();

        abort_if($alreadyApplied, 422, 'You have already applied for this course.');

        $approvedCount = $training->applications()->where('status', 'approved')->count()
            + $training->outsiderEnrollments()->where('status', 'approved')->count()
            + $training->guruBaruEnrollments()->where('status', 'approved')->count();
        abort_if($approvedCount >= $training->capacity, 422, 'This course is full.');

        $enrollment = EnrollGuruBaru::create([
            'gn_id' => $guruNew->gn_id,
            'courseID'    => $training->getKey(),
            'status'       => 'pending',
            'applied_at'   => now(),
        ]);

        event(new GuruBaruApplicationSubmitted($enrollment));

        return redirect()->route('trainings.show', $training->getKey())
            ->with('success', 'Your application has been submitted.');
    }

    public function myCourses(Request $request): Response
    {
        $courses = Training::lms()
            ->orderBy('courseName')
            ->orderBy('courseID')
            ->get();

        $access = ParticipantMaterialAccess::forCourses($courses->map(fn (Training $t) => $t->getKey()));

        return Inertia::render('applications/guru-baru-courses', [
            'courses' => $courses->map(fn (Training $t) => [
                'id'          => $t->getKey(),
                'courseName' => $t->courseName,
                'status'      => $t->status,
                'organiser'   => $t->organiserName,
                'mode'  => $t->mode,
            ] + $access->get($t->getKey())),
        ]);
    }

    public function myApplications(Request $request): Response
    {
        $user      = $request->user();
        $guruNew = GuruNew::where('email', $user->email)->first();

        $applied = $guruNew
            ? EnrollGuruBaru::with('course')
                ->where('gn_id', $guruNew->gn_id)
                ->where('status', '!=', 'auto_enrolled')
                ->orderBy('applied_at', 'desc')
                ->get()
                ->map(fn (EnrollGuruBaru $e) => [
                    'id'               => $e->courseID,
                    'status'           => $e->status,
                    'course_category'  => $e->course?->course_category,
                    'applied_at'       => $e->applied_at,
                    'rejection_reason' => $e->rejection_reason,
                    'training'         => $e->course ? [
                        'id'          => $e->course->courseID,
                        'courseName' => $e->course->courseName,
                        'title'       => $e->course->courseName,
                        'status'      => $e->course->status,
                    ] : null,
                ])
            : collect();

        $access  = ParticipantMaterialAccess::forCourses($applied->pluck('training.id'));
        $applied = $applied->map(fn (array $a) => $a + $access->get($a['training']['id'] ?? null, ['can_view' => true, 'access_until' => null]));

        return Inertia::render('applications/guru-baru-applications', [
            'applications' => $applied->values(),
        ]);
    }
}
