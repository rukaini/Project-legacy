<?php

namespace Tests\Feature;

use App\Http\Controllers\CourseController;
use App\Http\Controllers\Trainer\FeedbackController;
use App\Http\Controllers\Trainer\ProfileController;
use App\Http\Controllers\Trainer\ProposalController;
use App\Http\Controllers\Trainer\TeachingPlanController;
use Illuminate\Support\Facades\Route;
use ReflectionClass;
use Tests\TestCase;

class TrainerFeatureDecompositionContractTest extends TestCase
{
    public function test_profile_routes_keep_their_contracts_on_the_profile_controller(): void
    {
        $this->assertRouteContract('trainer.profile', 'GET', 'trainer/profile', ProfileController::class, 'profile');
        $this->assertRouteContract('trainer.profile.password', 'POST', 'trainer/profile/password', ProfileController::class, 'updatePassword');
        $this->assertRouteContract('trainer.profile.picture', 'POST', 'trainer/profile/picture', ProfileController::class, 'updatePicture');
        $this->assertRouteContract('trainer.profile.picture.remove', 'DELETE', 'trainer/profile/picture', ProfileController::class, 'pictureRemove');
        $this->assertRouteContract('trainer.profile.certificate', 'POST', 'trainer/profile/certificate', ProfileController::class, 'certificateUpload');
        $this->assertRouteContract('trainer.profile.certificate.remove', 'DELETE', 'trainer/profile/certificate/{certId}', ProfileController::class, 'certificateRemove');
        $this->assertRouteContract('trainer.profile.document.view', 'GET', 'trainer/profile/document/{docId}/view', ProfileController::class, 'documentView');
    }

    public function test_feedback_and_proposal_routes_keep_their_existing_contracts(): void
    {
        $this->assertRouteContract('trainer.feedback', 'GET', 'trainer/feedback', FeedbackController::class, 'feedback');
        $this->assertRouteContract('trainer.proposals', 'GET', 'trainer/proposals', ProposalController::class, 'proposals');
        $this->assertRouteContract('trainer.proposals.store', 'POST', 'trainer/proposals', ProposalController::class, 'storeProposal');
        $this->assertRouteContract('trainer.proposals.delete', 'DELETE', 'trainer/proposals/{proposal_id}', ProposalController::class, 'deleteProposal');
    }

    public function test_teaching_plan_routes_use_the_course_scoped_trainer_controller(): void
    {
        $this->assertRouteContract('trainer.courses.teaching-plans.index', 'GET', 'trainer/courses/{courseId}/teaching-plan', TeachingPlanController::class, 'index');
        $this->assertRouteContract('trainer.courses.teaching-plans.store', 'POST', 'trainer/courses/{courseId}/teaching-plans', TeachingPlanController::class, 'store');
        $this->assertRouteContract('trainer.courses.teaching-plans.view', 'GET', 'trainer/courses/{courseId}/teaching-plans/{planId}/view', TeachingPlanController::class, 'view');
        $this->assertRouteContract('trainer.courses.teaching-plans.download', 'GET', 'trainer/courses/{courseId}/teaching-plans/{planId}/download', TeachingPlanController::class, 'download');
        $this->assertRouteContract('trainer.courses.teaching-plans.feedback.seen', 'PATCH', 'trainer/courses/{courseId}/teaching-plans/{planId}/feedback/seen', TeachingPlanController::class, 'markFeedbackSeen');
    }

    public function test_extracted_methods_no_longer_reside_in_course_controller(): void
    {
        $courseController = new ReflectionClass(CourseController::class);

        foreach ([
            'profile',
            'updatePassword',
            'updatePicture',
            'pictureRemove',
            'certificateUpload',
            'certificateRemove',
            'documentView',
            'feedback',
            'proposals',
            'storeProposal',
            'deleteProposal',
        ] as $method) {
            $this->assertFalse(
                $courseController->hasMethod($method),
                "CourseController should no longer contain [{$method}]."
            );
        }
    }

    public function test_decomposed_trainer_views_and_partials_resolve(): void
    {
        foreach ([
            'trainer.profile',
            'trainer.profile.partials.styles',
            'trainer.profile.partials.content',
            'trainer.profile.partials.summary',
            'trainer.profile.partials.account-panel',
            'trainer.profile.partials.certificates-panel',
            'trainer.profile.partials.documents-panel',
            'trainer.profile.partials.scripts',
            'trainer.feedback',
            'trainer.feedback.partials.styles',
            'trainer.feedback.partials.content',
            'trainer.feedback.partials.summary',
            'trainer.feedback.partials.filters',
            'trainer.feedback.partials.course-list',
            'trainer.feedback.partials.scripts',
            'trainer.training.proposal',
            'trainer.training.proposal.partials.styles',
            'trainer.training.proposal.partials.content',
            'trainer.training.proposal.partials.stats',
            'trainer.training.proposal.partials.form',
            'trainer.training.proposal.partials.list',
            'trainer.training.proposal.partials.results',
            'trainer.training.proposal.partials.detail',
            'trainer.training.proposal.partials.scripts',
            'trainer.teaching-plan.partials.card',
            'trainer.teaching-plan.partials.styles',
            'trainer.teaching-plan.partials.tab-attention-styles',
            'trainer.teaching-plan.show',
        ] as $view) {
            $this->assertTrue(view()->exists($view), "Expected decomposed view [{$view}] to resolve.");
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
        $expectedMethods = $method === 'GET' ? ['GET', 'HEAD'] : [$method];

        $this->assertSame($expectedMethods, $route->methods());
        $this->assertSame($uri, $route->uri());
        $this->assertSame($controller.'@'.$action, $route->getActionName());
    }
}
