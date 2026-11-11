<?php

namespace Tests\Feature;

use App\Http\Controllers\CourseController;
use App\Http\Controllers\Trainer\SubmissionController;
use App\Services\Lms\TrainerLmsSubmissionFileResolver;
use App\Services\Lms\TrainerLmsSubmissionReportService;
use Illuminate\Support\Facades\Route;
use ReflectionClass;
use Tests\TestCase;

class SubmissionDecompositionContractTest extends TestCase
{
    public function test_submission_routes_keep_their_existing_contracts(): void
    {
        $this->assertRouteContract(
            'trainer.courses.submissions',
            ['GET', 'HEAD'],
            'trainer/courses/{course_id}/submissions',
            'submissions'
        );
        $this->assertRouteContract(
            'trainer.courses.submissions.review',
            ['PATCH'],
            'trainer/courses/{course_id}/submissions/{submission_id}/review',
            'markSubmissionReviewed'
        );
        $this->assertRouteContract(
            'trainer.courses.submissions.preview',
            ['GET', 'HEAD'],
            'trainer/courses/{course_id}/submissions/{submission_id}/preview',
            'previewSubmissionFile'
        );
        $this->assertRouteContract(
            'trainer.courses.submissions.zip',
            ['GET', 'HEAD'],
            'trainer/courses/{course_id}/submissions/{assessment_id}/download-zip',
            'downloadSubmissionZip'
        );
        $this->assertRouteContract(
            'trainer.courses.submissions.download',
            ['GET', 'HEAD'],
            'trainer/courses/{course_id}/submissions/{submission_id}/download',
            'downloadSubmissionFile'
        );
    }

    public function test_extracted_methods_no_longer_reside_in_course_controller(): void
    {
        $courseController = new ReflectionClass(CourseController::class);

        foreach ([
            'submissions',
            'markSubmissionReviewed',
            'previewSubmissionFile',
            'downloadSubmissionFile',
            'downloadSubmissionZip',
        ] as $method) {
            $this->assertFalse(
                $courseController->hasMethod($method),
                "CourseController should no longer contain [{$method}]."
            );
        }
    }

    public function test_submission_view_and_partials_resolve(): void
    {
        foreach ([
            'trainer.training.submission',
            'trainer.training.submission.partials.styles',
            'trainer.training.submission.partials.header',
            'trainer.training.submission.partials.task-inbox',
            'trainer.training.submission.partials.workspace',
            'trainer.training.submission.partials.roster-results',
            'trainer.training.submission.partials.file-preview-modal',
            'trainer.training.submission.partials.scripts',
        ] as $view) {
            $this->assertTrue(view()->exists($view), "Expected submission view [{$view}] to resolve.");
        }

        $this->assertTrue(class_exists(TrainerLmsSubmissionReportService::class));
        $this->assertTrue(class_exists(TrainerLmsSubmissionFileResolver::class));
    }

    private function assertRouteContract(
        string $name,
        array $methods,
        string $uri,
        string $action
    ): void {
        $route = Route::getRoutes()->getByName($name);

        $this->assertNotNull($route, "Expected route [{$name}] to remain registered.");
        $this->assertSame($methods, $route->methods());
        $this->assertSame($uri, $route->uri());
        $this->assertSame(
            SubmissionController::class.'@'.$action,
            $route->getActionName()
        );
    }
}
