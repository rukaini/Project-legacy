<?php

namespace App\Http\Controllers\Observer;

use App\Http\Controllers\Controller;
use App\Models\Observer;
use App\Models\PreResponse;
use App\Models\PdpcResponse;
use App\Models\PostResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class OBDashboardController extends Controller
{
    public function index(Request $request)
    {
        $teacher = Auth::guard('teacher')->user();

        $observer = Observer::where(
            'teacherID',
            $teacher->teacherID
        )->firstOrFail();

        $assignments = DB::table('observer_assignment')
            ->join(
                'guru_new',
                'observer_assignment.gn_id',
                '=',
                'guru_new.gn_id'
            )
            ->leftJoin(
                'school',
                'guru_new.schoolID',
                '=',
                'school.schoolID'
            )
            ->where(
                'observer_assignment.observerID',
                $observer->observerID
            )
            ->select(
                'observer_assignment.*',
                'guru_new.gn_name',
                'school.schoolName'
            )
            ->orderByDesc('observer_assignment.assignedDate')
            ->get();

        foreach ($assignments as $assignment) {
            $gnId = $assignment->gn_id;

            // PRE
            $preResponse = PreResponse::where('gn_id', $gnId)
                ->where('observation_stage', 'PRE')
                ->where('observerID', $observer->observerID)
                ->latest('responseID')
                ->first();

            // POST PDPC
            $pdpcResponse = PdpcResponse::where('gn_id', $gnId)
                ->where('observation_stage', 'POST')
                ->where('observerID', $observer->observerID)
                ->latest('responseID')
                ->first();

            // POST Feedback
            $feedbackResponse = PostResponse::where('gn_id', $gnId)
                ->where('observation_stage', 'POST')
                ->where('observerID', $observer->observerID)
                ->latest('responseID')
                ->first();

            $assignment->pre_status = $this->getStatus($preResponse);
            $assignment->pdpc_status = $this->getStatus($pdpcResponse);
            $assignment->feedback_status = $this->getStatus($feedbackResponse);


            $hasPostAssignment = false;

            if (Schema::hasColumn('observer_assignment', 'observation_stage')) {
                $hasPostAssignment = DB::table('observer_assignment')
                    ->where('observerID', $observer->observerID)
                    ->where('gn_id', $gnId)
                    ->where('observation_stage', 'POST')
                    ->exists();
            }

            if ($pdpcResponse || $feedbackResponse) {
                $hasPostAssignment = true;
            }

            $assignment->has_post_assignment = $hasPostAssignment;

            // PRE is always included.
            $completedCount = $assignment->pre_status === 'Completed'
                ? 1
                : 0;

            $totalForms = 1;

            // Include POST components only when applicable.
            if ($hasPostAssignment) {
                $totalForms = 3;

                if ($assignment->pdpc_status === 'Completed') {
                    $completedCount++;
                }

                if ($assignment->feedback_status === 'Completed') {
                    $completedCount++;
                }
            }

            $assignment->completed_count = $completedCount;
            $assignment->total_forms = $totalForms;

            $assignment->has_draft =
                $assignment->pre_status === 'Draft'
                || (
                    $hasPostAssignment
                    && (
                        $assignment->pdpc_status === 'Draft'
                        || $assignment->feedback_status === 'Draft'
                    )
                );

            $assignment->is_completed =
                $completedCount === $totalForms;

            $assignment->progress = (int) round(
                ($completedCount / $totalForms) * 100
            );
        }

        $totalAssigned = $assignments->count();

        $completedCount = $assignments
            ->filter(fn($assignment) => $assignment->is_completed)
            ->count();

        $ongoingCount = $assignments
            ->filter(fn($assignment) => !$assignment->is_completed)
            ->count();

        $draftCount = $assignments
            ->filter(fn($assignment) => $assignment->has_draft)
            ->count();

        $recentEvaluations = $assignments->take(5);

        // PRE result filters
        $preSearch = trim((string) $request->input('pre_search', ''));
        $preMonth = $request->input('pre_month', 'all');
        $preYear = $request->input('pre_year', 'all');
        $preLevel = $request->input('pre_level', 'all');

        // Available PRE years
        $preYears = DB::table('pre_response')
            ->where('observerID', $observer->observerID)
            ->where('observation_stage', 'PRE')
            ->where('status', 'Submitted')
            ->whereNotNull('observation_date')
            ->selectRaw('YEAR(observation_date) AS year')
            ->pluck('year')
            ->filter()
            ->unique()
            ->sortDesc()
            ->values();

        // PRE results
        $preResultQuery = DB::table('pre_response')
            ->join(
                'guru_new',
                'pre_response.gn_id',
                '=',
                'guru_new.gn_id'
            )
            ->where('pre_response.observerID', $observer->observerID)
            ->where('pre_response.observation_stage', 'PRE')
            ->where('pre_response.status', 'Submitted');

        if ($preSearch !== '') {
            $preResultQuery->where(
                'guru_new.gn_name',
                'like',
                "%{$preSearch}%"
            );
        }

        if ($preMonth !== 'all') {
            $preResultQuery->whereMonth(
                'pre_response.observation_date',
                (int) $preMonth
            );
        }

        if ($preYear !== 'all') {
            $preResultQuery->whereYear(
                'pre_response.observation_date',
                (int) $preYear
            );
        }

        if ($preLevel !== 'all') {
            $preResultQuery->where(
                'pre_response.achievement_level',
                $preLevel
            );
        }

        $preResults = $preResultQuery
            ->select(
                'pre_response.responseID',
                'pre_response.observation_date',
                'pre_response.class_name',
                'pre_response.subject_name',
                'pre_response.total_score',
                'pre_response.percentage',
                'pre_response.achievement_level',
                'guru_new.gn_id',
                'guru_new.gn_name'
            )
            ->orderByDesc('pre_response.observation_date')
            ->orderByDesc('pre_response.responseID')
            ->paginate(10, ['*'], 'pre_page')
            ->withQueryString();

        return view(
            'observer.dashboard',
            compact(
                'totalAssigned',
                'ongoingCount',
                'draftCount',
                'completedCount',
                'recentEvaluations',
                'preResults',
                'preYears',
                'preSearch',
                'preMonth',
                'preYear',
                'preLevel'
            )
        );
    }

    private function getStatus($response): string
    {
        if (!$response) {
            return 'Pending';
        }

        if ($response->status === 'Draft') {
            return 'Draft';
        }

        if ($response->status === 'Submitted') {
            return 'Completed';
        }

        return 'Pending';
    }
}
