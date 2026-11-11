<?php

namespace Tests\Feature;

use Illuminate\Routing\Route as LaravelRoute;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RepositoryStructureContractTest extends TestCase
{
    public function test_normalized_trainer_views_resolve_and_old_paths_do_not(): void
    {
        foreach ([
            'trainer.feedback',
            'trainer.profile',
            'trainer.training.learner-tracking',
            'trainer.training.participants',
        ] as $view) {
            $this->assertTrue(view()->exists($view), "Expected view [{$view}] to exist.");
        }

        foreach ([
            'feedback',
            'trainer.training.profile',
            'trainer.training.learner_tracking',
            'trainer.training.participant',
            'welcome',
        ] as $view) {
            $this->assertFalse(view()->exists($view), "Legacy view [{$view}] should not exist.");
        }
    }

    public function test_registered_controller_routes_target_existing_methods(): void
    {
        foreach (Route::getRoutes() as $route) {
            $this->assertInstanceOf(LaravelRoute::class, $route);
            $action = $route->getActionName();

            if (! str_contains($action, '@')) {
                continue;
            }

            [$controller, $method] = explode('@', $action, 2);

            $this->assertTrue(class_exists($controller), "Route controller [{$controller}] does not exist.");
            $this->assertTrue(
                method_exists($controller, $method),
                "Route action [{$action}] targets a missing method."
            );
        }
    }

    public function test_proven_unused_scaffold_and_broken_route_are_absent(): void
    {
        $this->assertFileDoesNotExist(resource_path('views/welcome.blade.php'));
        $this->assertNull(
            Route::getRoutes()->getByName('trainer.courses.learner-tracking.content-interaction')
        );
    }

    public function test_vite_inputs_only_include_the_runtime_module(): void
    {
        $config = file_get_contents(base_path('vite.config.js'));

        $this->assertIsString($config);
        $this->assertStringContainsString('resources/js/module-save-coordinator.js', $config);
        $this->assertStringNotContainsString('resources/js/app.js', $config);
        $this->assertStringNotContainsString('resources/css/app.css', $config);
        $this->assertFileDoesNotExist(resource_path('js/app.js'));
        $this->assertFileDoesNotExist(resource_path('css/app.css'));
    }

    public function test_active_application_sources_do_not_reference_removed_attendance_responses(): void
    {
        $roots = [
            app_path(),
            resource_path('views'),
            resource_path('js'),
            base_path('routes'),
        ];
        $pattern = '/\brsvp(?:_status)?\b|will[-_ ]come|not[-_ ]coming|\bmaybe\b|no[-_ ]response|confirmed attendance|confirmation rate|confirmed participants|response status/i';

        foreach ($roots as $root) {
            $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root));

            foreach ($files as $file) {
                if (! $file->isFile()) {
                    continue;
                }

                $contents = file_get_contents($file->getPathname());

                $this->assertIsString($contents);
                $this->assertDoesNotMatchRegularExpression(
                    $pattern,
                    $contents,
                    "Removed attendance-response behavior remains in [{$file->getPathname()}]."
                );
            }
        }
    }
}
