<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\TeachingPlanService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class TeachingPlanController extends Controller
{
    public function storeComment(
        Request $request,
        string $courseId,
        string $trainerId,
        int $planId,
        TeachingPlanService $teachingPlans
    ) {
        if (session('user_role') !== 'admin') {
            return redirect()->route('login')->with('error', 'Please login as admin first.');
        }

        $staffId = (string) session('user_id');
        abort_unless(DB::table('staff_edu')->where('staffID', $staffId)->exists(), 403);
        abort_unless($teachingPlans->trainerIsAssigned($courseId, $trainerId), 404);

        $request->merge(['comment' => trim((string) $request->input('comment'))]);
        $validated = $request->validate([
            'comment' => ['required', 'string', 'max:2000'],
        ], [
            'comment.required' => 'Enter feedback before sending it.',
            'comment.max' => 'Feedback must be 2,000 characters or fewer.',
        ]);

        DB::transaction(function () use ($courseId, $trainerId, $planId, $staffId, $validated): void {
            $plan = DB::table('teaching_plans')
                ->where('id', $planId)
                ->where('course_id', $courseId)
                ->where('trainer_id', $trainerId)
                ->lockForUpdate()
                ->first();

            abort_unless($plan, 404);

            $now = now();
            DB::table('teaching_plan_comments')->insert([
                'teaching_plan_id' => $plan->id,
                'reviewer_staff_id' => $staffId,
                'comment' => $validated['comment'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }, 3);

        return redirect()->route('admin.courses', [
            'course' => $courseId,
            'tab' => 'teaching-plans',
            'trainer' => $trainerId,
        ])->with('success', 'Teaching Plan feedback sent.');
    }

    public function view(
        string $courseId,
        string $trainerId,
        int $planId,
        TeachingPlanService $teachingPlans
    ) {
        return $this->serve($courseId, $trainerId, $planId, $teachingPlans, false);
    }

    public function download(
        string $courseId,
        string $trainerId,
        int $planId,
        TeachingPlanService $teachingPlans
    ) {
        return $this->serve($courseId, $trainerId, $planId, $teachingPlans, true);
    }

    private function serve(
        string $courseId,
        string $trainerId,
        int $planId,
        TeachingPlanService $teachingPlans,
        bool $download
    ) {
        if (session('user_role') !== 'admin') {
            return redirect()->route('login')->with('error', 'Please login as admin first.');
        }

        abort_unless($teachingPlans->trainerIsAssigned($courseId, $trainerId), 404);
        $plan = $teachingPlans->planInTrainerContext($courseId, $trainerId, $planId);
        abort_unless($plan, 404);

        $path = $teachingPlans->availableFilePath($plan->file_path);
        if (! $path) {
            return redirect()->route('admin.courses', [
                'course' => $courseId,
                'tab' => 'teaching-plans',
                'trainer' => $trainerId,
            ])->with('error', 'That teaching plan file is unavailable.');
        }

        $name = $teachingPlans->safeOriginalName($plan->original_name);

        return $download
            ? Storage::disk('local')->download($path, $name)
            : Storage::disk('local')->response($path, $name, [], 'inline');
    }
}
