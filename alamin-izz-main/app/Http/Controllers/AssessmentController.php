<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Models\AssessmentSubmission;
use App\Models\Application;
use App\Models\EnrollGuruBaru;
use App\Models\GuruNew;
use App\Models\Teacher;
use App\Models\Training;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AssessmentController extends Controller
{
    public function show(Training $training, Assessment $assessment): Response
    {
        abort_unless($training->isLms(), 403);
        abort_unless($assessment->courseID === $training->getKey(), 404);

        $user        = request()->user();
        $enrollmentId = $this->resolveEnrollmentId($user, $training, false);
        $submission = $enrollmentId === null
            ? null
            : AssessmentSubmission::where('assessment_id', $assessment->assessment_id)
                ->where('enrollment_id', $enrollmentId)
                ->first();

        $assessment->load('questions');

        return Inertia::render('assessments/show', [
            'training'      => $training,
            'assessment'    => $assessment,
            'submission'    => $submission,
            'enrollment_id' => $enrollmentId,
        ]);
    }

    public function store(Request $request, Training $training, Assessment $assessment): RedirectResponse
    {
        abort_unless($training->isLms(), 403);
        abort_unless($assessment->courseID === $training->getKey(), 404);

        $user         = $request->user();
        $enrollmentId = $this->resolveEnrollmentId($user, $training, true);

        $existing = AssessmentSubmission::where('assessment_id', $assessment->assessment_id)
            ->where('enrollment_id', $enrollmentId)
            ->exists();

        if ($existing) {
            return back()->with('error', 'You have already submitted this assessment.');
        }

        if ($assessment->type === 'quiz') {
            $this->submitQuiz($request, $assessment, $enrollmentId);
        } else {
            $this->submitTask($request, $assessment, $enrollmentId);
        }

        return back()->with('success', 'Submitted successfully.');
    }

    private function resolveEnrollmentId($user, Training $training, bool $materialize): ?string
    {
        // Teachers: must have an approved enrolment; their teacherID is the key.
        if ($user->isTeacher()) {
            $teacher = Teacher::where('email', $user->email)->first();
            abort_unless($teacher, 403, 'No teacher profile found.');

            $approved = Application::where('teacherID', $teacher->teacherID)
                ->where('course_id', $training->getKey())
                ->where('status', 'approved')
                ->exists();
            abort_unless($approved || ! $materialize, 403, 'Enrol in this training first.');
            abort_unless($approved, 403, 'Enrol in this training first.');

            return (string) $teacher->teacherID;
        }

        $guruNew = GuruNew::where('email', $user->email)->first();
        abort_unless($guruNew, 403, 'No applicant profile found.');

        $identity = ['gn_id' => $guruNew->gn_id, 'course_id' => $training->getKey()];
        $enrollment = EnrollGuruBaru::where($identity)->first();

        if (! $enrollment && $materialize) {
            $enrollment = EnrollGuruBaru::firstOrCreate(
                $identity,
                ['status' => 'auto_enrolled', 'applied_at' => now()],
            );
        }

        return $enrollment ? (string) $enrollment->enrollment_id : null;
    }

    private function submitQuiz(Request $request, Assessment $assessment, string $enrollmentId): void
    {
        $questions = $assessment->questions;
        $answers   = [];
        $score     = 0.0;

        foreach ($questions as $question) {
            $given = $request->input("answers.{$question->question_id}");

            if (in_array($question->question_type, ['mcq', 'true_false'])
                && $question->correct_answer
                && strtolower((string) $given) === strtolower((string) $question->correct_answer)
            ) {
                $score += (float) $question->marks;
            }

            $answers[] = [
                'question_id' => $question->question_id,
                'answer'      => $given,
            ];
        }

        $hasOpen = $questions->contains(fn ($q) => $q->question_type === 'short_answer');

        AssessmentSubmission::create([
            'assessment_id' => $assessment->assessment_id,
            'enrollment_id' => $enrollmentId,
            'answers_json'  => $answers,
            'grade'         => $score,
            'status'        => $hasOpen ? 'submitted' : 'graded',
            'submitted_at'  => now(),
        ]);
    }

    private function submitTask(Request $request, Assessment $assessment, string $enrollmentId): void
    {
        $filePath  = null;
        $fileName  = null;
        if ($request->hasFile('file')) {
            $file      = $request->file('file');
            $filePath  = $file->store('submissions', 'public');
            $fileName  = $file->getClientOriginalName();
        }

        AssessmentSubmission::create([
            'assessment_id'       => $assessment->assessment_id,
            'enrollment_id'       => $enrollmentId,
            'submitted_file_path' => $filePath,
            'original_file_name'  => $fileName,
            'answers_json'        => $request->filled('text_answer')
                ? [['question_id' => 0, 'answer' => $request->input('text_answer')]]
                : null,
            'status'              => 'submitted',
            'submitted_at'        => now(),
        ]);
    }
}
