<?php

namespace Tests\Feature;

use App\Http\Controllers\CourseController;
use App\Http\Controllers\Trainer\AttendanceController;
use App\Http\Controllers\Trainer\ParticipantController;
use Illuminate\Support\Facades\Route;
use ReflectionClass;
use Tests\TestCase;

class ParticipantAttendanceDecompositionContractTest extends TestCase
{
    public function test_participant_and_attendance_routes_keep_their_existing_contracts(): void
    {
        $this->assertRouteContract(
            'trainer.courses.participants',
            ['GET', 'HEAD'],
            'trainer/courses/{course_id}/participants',
            ParticipantController::class,
            'participants'
        );
        $this->assertRouteContract(
            'trainer.courses.attendance',
            ['GET', 'HEAD'],
            'trainer/courses/{course_id}/attendance',
            AttendanceController::class,
            'attendance'
        );
    }

    public function test_extracted_methods_and_helper_no_longer_reside_in_course_controller(): void
    {
        $courseController = new ReflectionClass(CourseController::class);

        foreach (['participants', 'attendance', 'participantCode'] as $method) {
            $this->assertFalse(
                $courseController->hasMethod($method),
                "CourseController should no longer contain [{$method}]."
            );
        }

        $this->assertTrue($courseController->hasMethod('trainerCanAccessCourse'));
    }

    public function test_participant_and_attendance_views_and_partials_resolve(): void
    {
        foreach ([
            'trainer.training.participants',
            'trainer.training.participants.partials.styles',
            'trainer.training.participants.partials.content',
            'trainer.training.participants.partials.results',
            'trainer.training.participants.partials.scripts',
            'trainer.training.attendance',
            'trainer.training.attendance.partials.styles',
            'trainer.training.attendance.partials.content',
            'trainer.training.attendance.partials.detail',
            'trainer.training.attendance.partials.results',
            'trainer.training.attendance.partials.report-fragment',
            'trainer.training.attendance.partials.scripts',
            'trainer.training.partials.workspace-header',
            'trainer.training.partials.report-paginator',
        ] as $view) {
            $this->assertTrue(view()->exists($view), "Expected view [{$view}] to resolve.");
        }
    }

    private function assertRouteContract(
        string $name,
        array $methods,
        string $uri,
        string $controller,
        string $action
    ): void {
        $route = Route::getRoutes()->getByName($name);

        $this->assertNotNull($route, "Expected route [{$name}] to remain registered.");
        $this->assertSame($methods, $route->methods());
        $this->assertSame($uri, $route->uri());
        $this->assertSame($controller.'@'.$action, $route->getActionName());
    }
}
