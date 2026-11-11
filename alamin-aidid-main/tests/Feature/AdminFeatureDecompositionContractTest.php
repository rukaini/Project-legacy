<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Admin\DirectoryController;
use App\Http\Controllers\Admin\MonitoringController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\ProposalController;
use App\Http\Controllers\Admin\TeachingPlanController;
use App\Http\Controllers\AdminController;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AdminFeatureDecompositionContractTest extends TestCase
{
    public function test_administrator_routes_keep_their_existing_contracts(): void
    {
        $this->assertRouteContract('admin.proposals', 'GET', 'admin/proposals', ProposalController::class, 'proposals');
        $this->assertRouteContract('admin.proposals.approve', 'POST', 'admin/proposals/{proposalId}/approve', ProposalController::class, 'approveProposal');
        $this->assertRouteContract('admin.proposals.reject', 'POST', 'admin/proposals/{proposalId}/reject', ProposalController::class, 'rejectProposal');
        $this->assertRouteContract('admin.activity', 'GET', 'admin/activity', MonitoringController::class, 'activityLog');
        $this->assertRouteContract('admin.courses', 'GET', 'admin/courses', MonitoringController::class, 'courses');
        $this->assertRouteContract('admin.courses.teaching-plans.comments.store', 'POST', 'admin/courses/{courseId}/trainers/{trainerId}/teaching-plans/{planId}/comments', TeachingPlanController::class, 'storeComment');
        $this->assertRouteContract('admin.courses.teaching-plans.view', 'GET', 'admin/courses/{courseId}/trainers/{trainerId}/teaching-plans/{planId}/view', TeachingPlanController::class, 'view');
        $this->assertRouteContract('admin.courses.teaching-plans.download', 'GET', 'admin/courses/{courseId}/trainers/{trainerId}/teaching-plans/{planId}/download', TeachingPlanController::class, 'download');
        $this->assertRouteContract('admin.analytics', 'GET', 'admin/analytics', AnalyticsController::class, 'index');
        $this->assertRouteContract('admin.directory', 'GET', 'admin/directory', DirectoryController::class, 'directory');
        $this->assertRouteContract('admin.directory.trainer.certificate.view', 'GET', 'admin/directory/trainers/{trainerId}/certificates/{certId}/view', DirectoryController::class, 'viewTrainerCertificate');
        $this->assertRouteContract('admin.profile', 'GET', 'admin/profile', ProfileController::class, 'profile');
        $this->assertRouteContract('admin.profile.password', 'POST', 'admin/profile/password', ProfileController::class, 'updatePassword');
        $this->assertRouteContract('admin.profile.picture', 'POST', 'admin/profile/picture', ProfileController::class, 'updatePicture');
        $this->assertRouteContract('admin.profile.picture.remove', 'POST', 'admin/profile/picture/remove', ProfileController::class, 'deletePicture');
    }

    public function test_old_umbrella_admin_controller_is_removed(): void
    {
        $this->assertFileDoesNotExist(app_path('Http/Controllers/AdminController.php'));
        $this->assertFalse(class_exists(AdminController::class));
    }

    public function test_decomposed_administrator_views_and_partials_resolve(): void
    {
        foreach ([
            'admin.partials.navbar',
            'admin.proposals',
            'admin.proposals.partials.styles',
            'admin.proposals.partials.content',
            'admin.proposals.partials.scripts',
            'admin.activity',
            'admin.activity.partials.styles',
            'admin.activity.partials.content',
            'admin.activity.partials.scripts',
            'admin.courses',
            'admin.courses.partials.styles',
            'admin.courses.partials.content',
            'admin.courses.partials.scripts',
            'admin.analytics',
            'admin.analytics.partials.styles',
            'admin.analytics.partials.content',
            'admin.analytics.partials.filters',
            'admin.analytics.partials.organization-overview',
            'admin.analytics.partials.training-detail',
            'admin.analytics.partials.scripts',
            'admin.directory',
            'admin.directory.partials.styles',
            'admin.directory.partials.content',
            'admin.directory.partials.scripts',
            'admin.profile',
            'admin.profile.partials.styles',
            'admin.profile.partials.content',
        ] as $view) {
            $this->assertTrue(view()->exists($view), "Expected administrator view [{$view}] to resolve.");
        }
    }

    private function assertRouteContract(
        string $name,
        string $method,
        string $uri,
        string $controller,
        string $action
    ): void {
        $route = Route::getRoutes()->getByName($name);

        $this->assertNotNull($route, "Expected route [{$name}] to remain registered.");
        $this->assertSame($method === 'GET' ? ['GET', 'HEAD'] : [$method], $route->methods());
        $this->assertSame($uri, $route->uri());
        $this->assertSame($controller.'@'.$action, $route->getActionName());
    }
}
