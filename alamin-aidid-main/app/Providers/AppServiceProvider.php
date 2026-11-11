<?php

namespace App\Providers;

use App\Services\Lms\TrainerLmsSubmissionReportService;
use App\Services\TeachingPlanService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View as IlluminateView;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer([
            'trainer.training.partials.workspace-header',
            'trainer.simple.partials.header',
        ], function (IlluminateView $view): void {
            $data = $view->getData();
            $course = $data['course'] ?? null;
            $courseId = trim((string) ($course->course_id ?? ''));
            $trainerId = session('user_role') === 'trainer' ? trim((string) session('user_id')) : '';
            $state = $data['teachingPlanState'] ?? null;

            if (! is_array($state) && $courseId !== '' && $trainerId !== '') {
                $state = app(TeachingPlanService::class)->navigationState($courseId, $trainerId);
            }

            $view->with('teachingPlanNavigationState', is_array($state) ? $state : null);
        });

        View::composer('trainer.training.partials.workspace-header', function (IlluminateView $view): void {
            $data = $view->getData();
            $course = $data['course'] ?? null;
            $courseId = trim((string) ($course->course_id ?? ''));
            $pendingCount = isset($data['pendingSubmissionCount'])
                ? (int) $data['pendingSubmissionCount']
                : ($courseId !== ''
                    ? app(TrainerLmsSubmissionReportService::class)->pendingReviewCount($courseId)
                    : 0);

            $view->with('lmsPendingReviewCount', $pendingCount);
        });
    }
}
