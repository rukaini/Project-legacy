<?php

namespace Tests\Feature;

use App\Http\Controllers\CourseController;
use App\Http\Controllers\Trainer\SimpleTrainingController;
use Illuminate\Support\Facades\Route;
use ReflectionClass;
use Tests\TestCase;

class SimpleTrainingDecompositionContractTest extends TestCase
{
    public function test_simple_training_routes_keep_their_existing_contracts(): void
    {
        $this->assertRouteContract(
            'trainer.simple.overview',
            'GET',
            'trainer/simple/{course_id}',
            'simpleOverview'
        );
        $this->assertRouteContract(
            'trainer.simple.participants',
            'GET',
            'trainer/simple/{course_id}/participants',
            'simpleParticipants'
        );
        $this->assertRouteContract(
            'trainer.simple.materials',
            'GET',
            'trainer/simple/{course_id}/materials',
            'simpleMaterials'
        );
        $this->assertRouteContract(
            'trainer.simple.materials.store',
            'POST',
            'trainer/simple/{course_id}/materials',
            'storeSimpleMaterial'
        );
        $this->assertRouteContract(
            'trainer.simple.materials.delete',
            'POST',
            'trainer/simple/materials/{material_id}/delete',
            'deleteSimpleMaterial'
        );
        $this->assertRouteContract(
            'trainer.simple.materials.view',
            'GET',
            'trainer/simple/materials/{material_id}/view',
            'viewSimpleMaterial'
        );
        $this->assertRouteContract(
            'trainer.simple.attendance',
            'GET',
            'trainer/simple/{course_id}/attendance',
            'simpleAttendance'
        );
    }

    public function test_extracted_methods_no_longer_reside_in_course_controller(): void
    {
        $courseController = new ReflectionClass(CourseController::class);

        foreach ([
            'simpleOverview',
            'simpleParticipants',
            'simpleMaterials',
            'storeSimpleMaterial',
            'viewSimpleMaterial',
            'deleteSimpleMaterial',
            'simpleAttendance',
        ] as $method) {
            $this->assertFalse(
                $courseController->hasMethod($method),
                "CourseController should no longer contain [{$method}]."
            );
        }
    }

    public function test_simple_training_views_and_partials_resolve(): void
    {
        foreach ([
            'trainer.simple.overview',
            'trainer.simple.partials.header',
            'trainer.simple.partials.workspace-styles',
            'trainer.simple.partials.paginator',
            'trainer.simple.partials.report-scripts',
            'trainer.simple.overview.partials.styles',
            'trainer.simple.overview.partials.content',
            'trainer.simple.overview.partials.header',
            'trainer.simple.overview.partials.session-spotlight',
            'trainer.simple.overview.partials.participants-panel',
            'trainer.simple.overview.partials.sidebar',
            'trainer.simple.overview.partials.scripts',
            'trainer.simple.participants',
            'trainer.simple.participants.partials.styles',
            'trainer.simple.participants.partials.content',
            'trainer.simple.participants.partials.scripts',
            'trainer.simple.participants.partials.results',
            'trainer.simple.materials',
            'trainer.simple.materials.partials.styles',
            'trainer.simple.materials.partials.content',
            'trainer.simple.materials.partials.scripts',
            'trainer.simple.materials.partials.results',
            'trainer.simple.attendance',
            'trainer.simple.attendance.partials.styles',
            'trainer.simple.attendance.partials.content',
            'trainer.simple.attendance.partials.scripts',
            'trainer.simple.attendance.partials.results',
        ] as $view) {
            $this->assertTrue(view()->exists($view), "Expected Simple Training view [{$view}] to resolve.");
        }
    }

    private function assertRouteContract(
        string $name,
        string $method,
        string $uri,
        string $action
    ): void {
        $route = Route::getRoutes()->getByName($name);

        $this->assertNotNull($route, "Expected route [{$name}] to remain registered.");
        $expectedMethods = $method === 'GET' ? ['GET', 'HEAD'] : [$method];

        $this->assertSame($expectedMethods, $route->methods());
        $this->assertSame($uri, $route->uri());
        $this->assertSame(
            SimpleTrainingController::class.'@'.$action,
            $route->getActionName()
        );
    }
}
