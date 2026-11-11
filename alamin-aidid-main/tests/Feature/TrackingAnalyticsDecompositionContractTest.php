<?php

namespace Tests\Feature;

use App\Http\Controllers\CourseController;
use App\Http\Controllers\Trainer\AnalyticsController;
use App\Http\Controllers\Trainer\LearnerTrackingController;
use App\Services\Lms\TrainerLmsLearnerTrackingReportService;
use Illuminate\Support\Facades\Route;
use ReflectionClass;
use Tests\TestCase;

class TrackingAnalyticsDecompositionContractTest extends TestCase
{
    public function test_tracking_and_analytics_routes_keep_their_existing_contracts(): void
    {
        $this->assertRouteContract(
            'trainer.courses.learner-tracking',
            'trainer/courses/{course_id}/learner-tracking',
            LearnerTrackingController::class,
            'learnerTracking'
        );
        $this->assertRouteContract(
            'trainer.analytics',
            'trainer/analytics',
            AnalyticsController::class,
            'analytics'
        );
    }

    public function test_extracted_methods_and_tracking_helper_leave_course_controller(): void
    {
        $courseController = new ReflectionClass(CourseController::class);
        $trackingController = new ReflectionClass(LearnerTrackingController::class);
        $trackingService = new ReflectionClass(TrainerLmsLearnerTrackingReportService::class);

        foreach (['learnerTracking', 'analytics', 'formatTrackingSeconds'] as $method) {
            $this->assertFalse(
                $courseController->hasMethod($method),
                "CourseController should no longer contain [{$method}]."
            );
        }

        $this->assertTrue($trackingController->hasMethod('formatTrackingSeconds'));
        $this->assertTrue($trackingController->getMethod('formatTrackingSeconds')->isPrivate());
        $this->assertTrue($trackingService->hasMethod('build'));
        $this->assertTrue($trackingService->hasMethod('formatDuration'));
    }

    public function test_tracking_and_analytics_views_and_partials_resolve(): void
    {
        foreach ([
            'trainer.training.learner-tracking',
            'trainer.training.learner-tracking.partials.styles',
            'trainer.training.learner-tracking.partials.content',
            'trainer.training.learner-tracking.partials.header',
            'trainer.training.learner-tracking.partials.sidebar',
            'trainer.training.learner-tracking.partials.overview-panel',
            'trainer.training.learner-tracking.partials.engagement-panel',
            'trainer.training.learner-tracking.partials.assessment-panel',
            'trainer.training.learner-tracking.partials.attempt-detail',
            'trainer.training.learner-tracking.partials.pagination',
            'trainer.training.learner-tracking.partials.scripts',
            'trainer.training.partials.workspace-header-styles',
            'trainer.training.partials.workspace-page-heading',
            'trainer.training.analytics',
            'trainer.training.analytics.partials.styles',
            'trainer.training.analytics.partials.content',
            'trainer.training.analytics.partials.header-filters',
            'trainer.training.analytics.partials.metrics',
            'trainer.training.analytics.partials.report-panels',
            'trainer.training.analytics.partials.scripts',
        ] as $view) {
            $this->assertTrue(view()->exists($view), "Expected view [{$view}] to resolve.");
        }
    }

    public function test_learner_tracking_reuses_the_shared_shell_only_on_full_page_renders(): void
    {
        $page = file_get_contents(resource_path('views/trainer/training/learner-tracking.blade.php'));
        $header = file_get_contents(resource_path('views/trainer/training/learner-tracking/partials/header.blade.php'));
        $content = file_get_contents(resource_path('views/trainer/training/learner-tracking/partials/content.blade.php'));
        $sharedStyles = file_get_contents(resource_path('views/trainer/training/partials/workspace-header-styles.blade.php'));

        $this->assertStringContainsString("@include('trainer.training.partials.workspace-header-styles')", $page);
        $this->assertStringContainsString("@include('trainer.training.learner-tracking.partials.header')", $page);
        $this->assertStringContainsString("@include('trainer.training.partials.workspace-header'", $header);
        $this->assertStringNotContainsString('workspace-header', $content);
        $this->assertStringContainsString('data-lms-workspace-header-styles', $sharedStyles);
        $this->assertStringContainsString('.lw-tabs a.active', $sharedStyles);
        $this->assertStringContainsString('.lw-tab-badge', $sharedStyles);
    }

    public function test_mysql_activity_union_normalizes_every_text_position_to_one_collation(): void
    {
        $reflection = new ReflectionClass(TrainerLmsLearnerTrackingReportService::class);
        $service = $reflection->newInstanceWithoutConstructor();
        $textSql = $reflection->getMethod('activityUnionTextSql');
        $coalescedSql = $reflection->getMethod('activityUnionCoalescedTextSql');

        foreach ([
            ["'material'", 'kind'],
            ['assessment.title', 'title'],
            ['training_module.module_title', 'context'],
        ] as [$expression, $alias]) {
            $sql = $textSql->invoke($service, $expression, $alias, 'mysql');

            $this->assertStringContainsString("CONVERT(({$expression}) USING utf8mb4)", $sql);
            $this->assertStringContainsString('COLLATE utf8mb4_unicode_ci', $sql);
            $this->assertStringEndsWith(" as {$alias}", $sql);
        }

        $titleSql = $coalescedSql->invoke($service, [
            'training_content_block.block_title',
            'learning_material.original_file_name',
            "'Untitled material'",
        ], 'title', 'mysql');

        $this->assertStringContainsString('CONVERT((training_content_block.block_title) USING utf8mb4)', $titleSql);
        $this->assertStringContainsString('CONVERT((learning_material.original_file_name) USING utf8mb4)', $titleSql);
        $this->assertStringContainsString("CONVERT(('Untitled material') USING utf8mb4)", $titleSql);
        $this->assertStringContainsString('COLLATE utf8mb4_unicode_ci', $titleSql);

        $sqliteSql = $textSql->invoke($service, 'assessment.title', 'title', 'sqlite');
        $this->assertSame('assessment.title as title', $sqliteSql);
        $this->assertStringNotContainsString('COLLATE', $sqliteSql);

        $source = file_get_contents(app_path('Services/Lms/TrainerLmsLearnerTrackingReportService.php'));
        $this->assertStringContainsString('$this->activityUnionTextExpression("\'material\'", \'kind\')', $source);
        $this->assertStringContainsString('$this->activityUnionCoalescedTextExpression([', $source);
        $this->assertStringContainsString("\$this->activityUnionTextExpression('training_module.module_title', 'context')", $source);
        $this->assertStringContainsString('$this->activityUnionTextExpression("\'quiz\'", \'kind\')', $source);
        $this->assertStringContainsString("\$this->activityUnionTextExpression('assessment.title', 'title')", $source);
        $this->assertStringContainsString('$this->activityUnionTextExpression("\'Quiz\'", \'context\')', $source);
    }

    private function assertRouteContract(
        string $name,
        string $uri,
        string $controller,
        string $action
    ): void {
        $route = Route::getRoutes()->getByName($name);

        $this->assertNotNull($route, "Expected route [{$name}] to remain registered.");
        $this->assertSame(['GET', 'HEAD'], $route->methods());
        $this->assertSame($uri, $route->uri());
        $this->assertSame($controller.'@'.$action, $route->getActionName());
    }
}
