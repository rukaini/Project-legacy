<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Analytics\AdminAnalyticsReportService;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function index(Request $request, AdminAnalyticsReportService $reports)
    {
        if (session('user_role') !== 'admin') {
            return redirect()->route('login')->with('error', 'Please login as admin first.');
        }

        $allTrainings = $reports->trainings();
        $trainingType = $reports->validatedTrainingType($request);
        $trainings = match ($trainingType) {
            'simple' => $allTrainings->where('training_type', 'Simple Training')->values(),
            'lms' => $allTrainings->where('training_type', 'LMS')->values(),
            default => $allTrainings,
        };
        $requestedCourseId = mb_substr(trim((string) $request->query('course_id', '')), 0, 20);
        $selectedTraining = $requestedCourseId !== ''
            ? $trainings->first(fn ($training) => (string) $training->course_id === $requestedCourseId)
            : null;
        $dateRange = $reports->resolveDateRange($request);

        $viewData = [
            'trainings' => $trainings,
            'selectedTraining' => $selectedTraining,
            'trainingType' => $trainingType,
            'reportDate' => $dateRange['preset'],
            'dateFrom' => $dateRange['from'],
            'dateTo' => $dateRange['to'],
            'dateRange' => $dateRange,
            'contextFilterActive' => $requestedCourseId !== ''
                || $trainingType !== 'all'
                || $dateRange['preset'] !== 'all',
        ];

        $viewData += $selectedTraining
            ? $reports->trainingReport($selectedTraining, $request, $dateRange)
            : $reports->organizationReport($trainings, $dateRange);

        return view('admin.analytics', $viewData);
    }
}
