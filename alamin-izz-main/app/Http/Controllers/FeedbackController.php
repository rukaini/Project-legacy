<?php

namespace App\Http\Controllers;

use App\Models\CourseParticipant;
use App\Models\FeedbackForm;
use App\Models\FeedbackResponse;
use App\Models\GuruNew;
use App\Models\Outsider;
use App\Models\Teacher;
use App\Models\Training;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FeedbackController extends Controller
{
    public function submit(Request $request, Training $training, FeedbackForm $form): RedirectResponse
    {
        $user = $request->user();

        $existingParticipant = CourseParticipant::findFor($user, $training->getKey());
        if ($existingParticipant?->isFeedbackCompleted) {
            return back()->with('error', 'You have already submitted feedback for this course.');
        }

        $answers = $request->input('answers', []);
        if (! is_array($answers)) {
            return back()->withErrors(['answers' => 'Please submit a valid set of answers.']);
        }

        $form->load('questions');
        $requiredIds = $form->questions->where('isRequired', true)->pluck('questionID');
        foreach ($requiredIds as $qId) {
            if (! isset($answers[$qId]) || trim((string) $answers[$qId]) === '') {
                return back()->withErrors(['answers' => 'Please answer all required questions (marked with *).']);
            }
        }

        // Validate the complete submission before creating any response rows.
        foreach ($form->questions as $question) {
            $val = $answers[$question->questionID] ?? null;
            if ($val === null || trim((string) $val) === '') {
                continue;
            }

            if ($question->questionType === 'rating'
                && (filter_var($val, FILTER_VALIDATE_INT) === false || (int) $val < 1 || (int) $val > 10)
            ) {
                return back()->withErrors(['answers' => 'Ratings must be between 1 and 10.']);
            }
        }

        $responseDate = now();

        $submitted = DB::transaction(function () use ($user, $training, $form, $answers, $responseDate): bool {
            $participant = $this->resolveParticipant($user, $training);
            abort_unless($participant, 403, 'You are not enrolled in this training.');

            if ($participant->isFeedbackCompleted) {
                return false;
            }

            // feedback_response stores one rating-or-comment value per question.
            foreach ($form->questions as $question) {
                $val = $answers[$question->questionID] ?? null;
                if ($val === null || trim((string) $val) === '') {
                    continue;
                }

                FeedbackResponse::create([
                    'questionID'    => $question->questionID,
                    'participantID' => $participant->participantID,
                    'responseType'  => 'participant',
                    // Canonical persistence adapter: Izz 1-10 maps to shared 1-5.
                    'rating'        => $question->questionType === 'rating'
                        ? (int) ceil(((int) $val) / 2)
                        : null,
                    'comment'       => $question->questionType !== 'rating' ? (string) $val : null,
                    'responseDate'  => $responseDate,
                ]);
            }

            $participant->update(['isFeedbackCompleted' => true]);

            return true;
        });

        if (! $submitted) {
            return back()->with('error', 'You have already submitted feedback for this course.');
        }

        return back()->with('success', 'Thank you! Your feedback has been submitted.');
    }

    private function resolveParticipant(User $user, Training $training): ?CourseParticipant
    {
        $courseId = $training->getKey();

        if ($user->isTeacher()) {
            $teacher = Teacher::where('email', $user->email)->first();
            if (! $teacher) {
                return null;
            }

            return CourseParticipant::firstOrCreate(
                ['courseID' => $courseId, 'teacherID' => $teacher->teacherID],
                [
                    'participantType' => 'teacher',
                    'participantName' => $teacher->teacherName,
                    'email'           => $teacher->email,
                ],
            );
        }

        if ($user->isOutsider()) {
            $outsider = Outsider::where('user_id', $user->id)->first();
            if (! $outsider) {
                return null;
            }

            return CourseParticipant::firstOrCreate(
                ['courseID' => $courseId, 'outsider_id' => $outsider->outsider_id],
                [
                    'participantType'   => 'public',
                    'participantName'   => $user->name,
                    'organisationName'  => $outsider->organization,
                    'email'             => $user->email,
                ],
            );
        }

        if ($user->isGuruBaru()) {
            $guruNew = GuruNew::where('email', $user->email)->first();
            if (! $guruNew) {
                return null;
            }

            return CourseParticipant::firstOrCreate(
                ['courseID' => $courseId, 'gn_id' => $guruNew->gn_id],
                [
                    'participantType' => 'new_teacher',
                    'participantName' => $guruNew->gn_name,
                    'email'           => $guruNew->email,
                ],
            );
        }

        return null;
    }
}
