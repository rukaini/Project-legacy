<?php

namespace App\Http\Controllers\Trainer;

use App\Http\Controllers\Controller;
use App\Services\Feedback\TrainerFeedbackReportService;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    public function feedback(Request $request, TrainerFeedbackReportService $feedbackReport)
    {
        if (session('user_role') !== 'trainer') {
            return redirect()->route('login')->with('error', 'Please login as trainer first.');
        }

        $trainerId = (string) session('user_id');
        $filters = $feedbackReport->normalizeFilters($request);

        if ($request->string('feedback_fragment')->toString() === 'comments') {
            return view('trainer.feedback.partials.comment-list', $feedbackReport->commentPage(
                $trainerId,
                mb_substr(trim($request->string('course_id')->toString()), 0, 20),
                mb_substr(trim($request->string('question_id')->toString()), 0, 20),
                $filters
            ));
        }

        $data = $feedbackReport->report($trainerId, $filters);

        if ($request->ajax()) {
            return view('trainer.feedback.partials.dynamic-region', $data);
        }

        return view('trainer.feedback', $data);
    }
}
