<?php

namespace App\Http\Controllers\Trainer;

use App\Http\Controllers\Controller;
use App\Services\TeachingPlanService;
use App\Support\SharedTrainingSchema;
use App\Support\TrainerActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class TeachingPlanController extends Controller
{
    public function index(string $courseId, TeachingPlanService $teachingPlans)
    {
        if (session('user_role') !== 'trainer') {
            return redirect()->route('login')->with('error', 'Please login as trainer first.');
        }

        $trainerId = SharedTrainingSchema::currentTrainerId();
        $course = SharedTrainingSchema::course($courseId);

        abort_unless($course, 404);
        $courseId = (string) $course->course_id;
        abort_unless($teachingPlans->trainerIsAssigned($courseId, $trainerId), 403);

        return view('trainer.teaching-plan.show', array_merge([
            'course' => $course,
            'isLmsMode' => strtolower((string) ($course->training_mode ?? 'standard')) === 'lms',
        ], $teachingPlans->trainerViewData($courseId, $trainerId)));
    }

    public function store(Request $request, string $courseId, TeachingPlanService $teachingPlans)
    {
        if (session('user_role') !== 'trainer') {
            return redirect()->route('login')->with('error', 'Please login as trainer first.');
        }

        $trainerId = SharedTrainingSchema::currentTrainerId();
        $course = SharedTrainingSchema::course($courseId);
        $courseId = (string) ($course->course_id ?? $courseId);
        abort_unless($course && $teachingPlans->trainerIsAssigned($courseId, $trainerId), 403);

        if ($teachingPlans->current($courseId, $trainerId)) {
            return $this->workspaceRedirect($course)->withErrors([
                'teaching_plan' => 'Remove your current Teaching Plan before uploading a new submission.',
            ]);
        }

        $validated = $request->validate([
            'teaching_plan' => ['required', 'file', 'mimes:pdf,doc,docx', 'extensions:pdf,doc,docx', 'max:10240'],
        ], [
            'teaching_plan.required' => 'Choose a teaching plan to upload.',
            'teaching_plan.mimes' => 'The teaching plan must be a PDF, DOC, or DOCX file.',
            'teaching_plan.extensions' => 'The teaching plan must use a PDF, DOC, or DOCX filename.',
            'teaching_plan.max' => 'The teaching plan must be 10 MB or smaller.',
        ]);

        $file = $validated['teaching_plan'];
        $extension = strtolower($file->getClientOriginalExtension());
        $folder = 'teaching-plans/course-'.$courseId.'/trainer-'.$trainerId;
        $path = $file->storeAs($folder, Str::uuid().'.'.$extension, 'local');

        if (! $path) {
            return $this->workspaceRedirect($course)
                ->with('error', 'The teaching plan could not be stored. Please try again.');
        }

        try {
            $planId = DB::transaction(function () use ($courseId, $trainerId, $file, $path, $teachingPlans): int {
                SharedTrainingSchema::constrainCourse(DB::table('course')->lockForUpdate(), $courseId)->first();
                $currentExists = DB::table('teaching_plans')
                    ->where('course_id', $courseId)
                    ->where('trainer_id', $trainerId)
                    ->exists();

                if ($currentExists) {
                    throw new \DomainException('A current Teaching Plan already exists.');
                }

                $now = now();

                return DB::table('teaching_plans')->insertGetId([
                    'course_id' => $courseId,
                    'trainer_id' => $trainerId,
                    'original_name' => $teachingPlans->safeOriginalName($file->getClientOriginalName()),
                    'file_path' => $path,
                    'mime_type' => mb_substr((string) ($file->getMimeType() ?: 'application/octet-stream'), 0, 150),
                    'file_size' => (int) $file->getSize(),
                    'submitted_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }, 3);
        } catch (\DomainException $exception) {
            Storage::disk('local')->delete($path);

            return $this->workspaceRedirect($course)->withErrors([
                'teaching_plan' => 'Remove your current Teaching Plan before uploading a new submission.',
            ]);
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($path);
            report($exception);

            return $this->workspaceRedirect($course)
                ->with('error', 'The teaching plan could not be saved. Please try again.');
        }

        TrainerActivityLogger::log(
            $trainerId,
            'teaching_plan.submit',
            $teachingPlans->safeOriginalName($file->getClientOriginalName()),
            $courseId,
            'teaching_plan',
            $planId
        );

        return $this->workspaceRedirect($course)
            ->with('success', 'Teaching plan uploaded.');
    }

    public function destroy(
        Request $request,
        string $courseId,
        int $planId,
        TeachingPlanService $teachingPlans
    ) {
        if (session('user_role') !== 'trainer') {
            return redirect()->route('login')->with('error', 'Please login as trainer first.');
        }

        $trainerId = SharedTrainingSchema::currentTrainerId();
        $course = SharedTrainingSchema::course($courseId);
        $courseId = (string) ($course->course_id ?? $courseId);
        abort_unless($course && $teachingPlans->trainerIsAssigned($courseId, $trainerId), 403);

        $removedPlan = DB::transaction(function () use ($courseId, $trainerId, $planId): ?object {
            $plan = DB::table('teaching_plans')
                ->where('id', $planId)
                ->where('course_id', $courseId)
                ->where('trainer_id', $trainerId)
                ->lockForUpdate()
                ->first();

            abort_unless($plan, 404);

            $hasUnseenFeedback = DB::table('teaching_plan_comments')
                ->where('teaching_plan_id', $plan->id)
                ->whereNull('seen_by_trainer_at')
                ->exists();

            if ($hasUnseenFeedback) {
                return null;
            }

            DB::table('teaching_plan_comments')->where('teaching_plan_id', $plan->id)->delete();
            DB::table('teaching_plans')->where('id', $plan->id)->delete();

            return $plan;
        }, 3);

        if (! $removedPlan) {
            $message = 'Review and mark the latest Admin feedback as seen before replacing this submission.';

            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 422);
            }

            return $this->workspaceRedirect($course)->with('error', $message);
        }

        $path = $teachingPlans->availableFilePath($removedPlan->file_path);
        if ($path) {
            Storage::disk('local')->delete($path);
        }

        TrainerActivityLogger::log(
            $trainerId,
            'teaching_plan.remove',
            $teachingPlans->safeOriginalName($removedPlan->original_name),
            $courseId,
            'teaching_plan',
            $planId
        );

        $message = 'Teaching Plan submission removed.';
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'state' => $teachingPlans->trainerState(null),
            ]);
        }

        return $this->workspaceRedirect($course)->with('success', $message);
    }

    public function view(string $courseId, int $planId, TeachingPlanService $teachingPlans)
    {
        return $this->serve($courseId, $planId, $teachingPlans, false);
    }

    public function download(string $courseId, int $planId, TeachingPlanService $teachingPlans)
    {
        return $this->serve($courseId, $planId, $teachingPlans, true);
    }

    public function markFeedbackSeen(
        Request $request,
        string $courseId,
        int $planId,
        TeachingPlanService $teachingPlans
    ) {
        if (session('user_role') !== 'trainer') {
            return redirect()->route('login')->with('error', 'Please login as trainer first.');
        }

        $trainerId = SharedTrainingSchema::currentTrainerId();
        $course = SharedTrainingSchema::course($courseId);
        $courseId = (string) ($course->course_id ?? $courseId);
        abort_unless($course && $teachingPlans->trainerIsAssigned($courseId, $trainerId), 403);

        $plan = $teachingPlans->planInTrainerContext($courseId, $trainerId, $planId);
        abort_unless($plan, 404);
        abort_unless(Schema::hasColumn('teaching_plan_comments', 'seen_by_trainer_at'), 503);

        DB::table('teaching_plan_comments')
            ->where('teaching_plan_id', $plan->id)
            ->whereNull('seen_by_trainer_at')
            ->update(['seen_by_trainer_at' => now()]);

        $message = 'Teaching Plan feedback marked as seen.';
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'state' => $teachingPlans->trainerState($teachingPlans->current($courseId, $trainerId)),
            ]);
        }

        return $this->workspaceRedirect($course)->with('success', $message);
    }

    private function serve(string $courseId, int $planId, TeachingPlanService $teachingPlans, bool $download)
    {
        if (session('user_role') !== 'trainer') {
            return redirect()->route('login')->with('error', 'Please login as trainer first.');
        }

        $trainerId = SharedTrainingSchema::currentTrainerId();
        $course = SharedTrainingSchema::course($courseId);
        $courseId = (string) ($course->course_id ?? $courseId);
        abort_unless($teachingPlans->trainerIsAssigned($courseId, $trainerId), 403);
        $plan = $teachingPlans->planInTrainerContext($courseId, $trainerId, $planId);
        abort_unless($plan, 404);

        $path = $teachingPlans->availableFilePath($plan->file_path);
        if (! $path) {
            $course = SharedTrainingSchema::course($courseId);
            abort_unless($course, 404);

            return $this->workspaceRedirect($course)->with('error', 'That teaching plan file is unavailable.');
        }

        $name = $teachingPlans->safeOriginalName($plan->original_name);

        return $download
            ? Storage::disk('local')->download($path, $name)
            : Storage::disk('local')->response($path, $name, [], 'inline');
    }

    private function workspaceRedirect(object $course)
    {
        return redirect()->route('trainer.courses.teaching-plans.index', $course->course_id);
    }
}
