<?php

use App\Http\Controllers\Admin\AnalyticsController as AdminAnalyticsController;
use App\Http\Controllers\Admin\DirectoryController as AdminDirectoryController;
use App\Http\Controllers\Admin\MonitoringController as AdminMonitoringController;
use App\Http\Controllers\Admin\ProfileController as AdminProfileController;
use App\Http\Controllers\Admin\ProposalController as AdminProposalController;
use App\Http\Controllers\Admin\TeachingPlanController as AdminTeachingPlanController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SsoController;
use App\Http\Controllers\Trainer\AnalyticsController;
use App\Http\Controllers\Trainer\AttendanceController;
use App\Http\Controllers\Trainer\FeedbackController;
use App\Http\Controllers\Trainer\LearnerTrackingController;
use App\Http\Controllers\Trainer\ParticipantController;
use App\Http\Controllers\Trainer\ProfileController;
use App\Http\Controllers\Trainer\ProposalController;
use App\Http\Controllers\Trainer\SimpleTrainingController;
use App\Http\Controllers\Trainer\SubmissionController;
use App\Http\Controllers\Trainer\TeachingPlanController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/login');
});

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/

Route::get('/login', [AuthController::class, 'showLogin'])
    ->name('login');

Route::post('/logout', [AuthController::class, 'logout'])
    ->name('logout');

Route::get('/sso/callback', [SsoController::class, 'callback'])
    ->middleware('throttle:30,1')
    ->name('sso.callback');

/*
|--------------------------------------------------------------------------
| Dashboard Routes
|--------------------------------------------------------------------------
*/

Route::get('/trainer/dashboard', [DashboardController::class, 'trainerDashboard'])
    ->name('trainer.dashboard');

Route::get('/admin/dashboard', [DashboardController::class, 'adminDashboard'])
    ->name('admin.dashboard');

/*
|--------------------------------------------------------------------------
| Trainer Training Routes
|--------------------------------------------------------------------------
*/

Route::get('/trainer/courses', [CourseController::class, 'index'])
    ->name('trainer.courses');

Route::get('/trainer/courses/{course_id}', [CourseController::class, 'show'])
    ->name('trainer.courses.show');

Route::get('/trainer/courses/{courseId}/teaching-plan', [TeachingPlanController::class, 'index'])
    ->name('trainer.courses.teaching-plans.index');
Route::post('/trainer/courses/{courseId}/teaching-plans', [TeachingPlanController::class, 'store'])
    ->name('trainer.courses.teaching-plans.store');
Route::delete('/trainer/courses/{courseId}/teaching-plans/{planId}', [TeachingPlanController::class, 'destroy'])
    ->whereNumber('planId')
    ->name('trainer.courses.teaching-plans.destroy');
Route::get('/trainer/courses/{courseId}/teaching-plans/{planId}/view', [TeachingPlanController::class, 'view'])
    ->whereNumber('planId')
    ->name('trainer.courses.teaching-plans.view');
Route::get('/trainer/courses/{courseId}/teaching-plans/{planId}/download', [TeachingPlanController::class, 'download'])
    ->whereNumber('planId')
    ->name('trainer.courses.teaching-plans.download');
Route::patch('/trainer/courses/{courseId}/teaching-plans/{planId}/feedback/seen', [TeachingPlanController::class, 'markFeedbackSeen'])
    ->whereNumber('planId')
    ->name('trainer.courses.teaching-plans.feedback.seen');

/*
|--------------------------------------------------------------------------
| Trainer Learning Material / LMS Workspace Routes
|--------------------------------------------------------------------------
*/

Route::get('/trainer/courses/{course_id}/materials', [CourseController::class, 'materials'])
    ->name('trainer.courses.materials');
Route::get('/trainer/courses/{course_id}/materials/{material_id}/download', [CourseController::class, 'downloadMaterial'])
    ->name('trainer.courses.materials.download');

Route::get('/trainer/courses/{course_id}/assessments/builder', [CourseController::class, 'assessmentBuilder'])
    ->name('trainer.courses.assessments.builder');

Route::delete('/trainer/courses/{course_id}/modules/{module_id}', [CourseController::class, 'deleteModule'])
    ->name('trainer.courses.modules.delete');

Route::patch('/trainer/courses/{course_id}/assessments/{assessment_id}', [CourseController::class, 'updateAssessment'])
    ->name('trainer.courses.assessments.update');
Route::get('/trainer/courses/{course_id}/assessments/{assessment_id}/authoring', [CourseController::class, 'assessmentAuthoringData'])
    ->name('trainer.courses.assessments.authoring');
Route::get('/trainer/courses/{course_id}/assessments/{assessment_id}/questions/{question_id}/image', [CourseController::class, 'assessmentQuestionImage'])
    ->name('trainer.courses.assessments.questions.image');

/*
|--------------------------------------------------------------------------
| Trainer Submission Routes
|--------------------------------------------------------------------------
*/

Route::get('/trainer/courses/{course_id}/submissions', [SubmissionController::class, 'submissions'])
    ->name('trainer.courses.submissions');

Route::patch('/trainer/courses/{course_id}/submissions/{submission_id}/review', [SubmissionController::class, 'markSubmissionReviewed'])
    ->name('trainer.courses.submissions.review');

Route::get('/trainer/courses/{course_id}/submissions/{submission_id}/preview', [SubmissionController::class, 'previewSubmissionFile'])
    ->name('trainer.courses.submissions.preview');

Route::get('/trainer/courses/{course_id}/submissions/{assessment_id}/download-zip', [SubmissionController::class, 'downloadSubmissionZip'])
    ->name('trainer.courses.submissions.zip');

Route::get('/trainer/courses/{course_id}/participants', [ParticipantController::class, 'participants'])
    ->name('trainer.courses.participants');

Route::get('/trainer/courses/{course_id}/attendance', [AttendanceController::class, 'attendance'])
    ->name('trainer.courses.attendance');

Route::get('/trainer/courses/{course_id}/sessions/{session_id}/attendance-qr', [AttendanceController::class, 'qrPresentation'])
    ->name('trainer.courses.attendance.qr');

/*
|--------------------------------------------------------------------------
| Module Routes
|--------------------------------------------------------------------------
*/

Route::post('/trainer/courses/{course_id}/modules', [CourseController::class, 'storeModule'])
    ->name('trainer.courses.modules.store');

Route::patch('/trainer/courses/{course_id}/modules/reorder', [CourseController::class, 'reorderModules'])
    ->name('trainer.courses.modules.reorder');

Route::patch('/trainer/courses/{course_id}/modules/{module_id}', [CourseController::class, 'updateModule'])
    ->name('trainer.courses.modules.update');

Route::patch('/trainer/courses/{course_id}/modules/{module_id}/save-blocks', [CourseController::class, 'saveModuleBlocks'])
    ->name('trainer.courses.modules.blocks.save');

/*
|--------------------------------------------------------------------------
| Content Block Routes
|--------------------------------------------------------------------------
*/

Route::post('/trainer/courses/{course_id}/modules/{module_id}/blocks/material', [CourseController::class, 'storeMaterialBlock'])
    ->name('trainer.courses.blocks.material.store');

Route::post('/trainer/courses/{course_id}/modules/{module_id}/blocks/media', [CourseController::class, 'storeMediaBlock'])
    ->name('trainer.courses.blocks.media.store');

Route::post('/trainer/courses/{course_id}/modules/{module_id}/blocks/youtube', [CourseController::class, 'storeYoutubeBlock'])
    ->name('trainer.courses.blocks.youtube.store');

Route::post('/trainer/courses/{course_id}/modules/{module_id}/blocks/assessment', [CourseController::class, 'storeAssessmentBlock'])
    ->name('trainer.courses.blocks.assessment.store');

Route::patch('/trainer/courses/{course_id}/blocks/{block_id}/file', [CourseController::class, 'replaceUploadedBlock'])
    ->name('trainer.courses.blocks.file.replace');

Route::delete('/trainer/courses/{course_id}/blocks/{block_id}', [CourseController::class, 'deleteBlock'])
    ->name('trainer.courses.blocks.delete');

/*
|--------------------------------------------------------------------------
| Assessment Routes
|--------------------------------------------------------------------------
*/

Route::post('/trainer/courses/{course_id}/assessments', [CourseController::class, 'storeAssessment'])
    ->name('trainer.courses.assessments.store');

Route::get('/trainer/courses/{course_id}/learner-tracking', [LearnerTrackingController::class, 'learnerTracking'])
    ->name('trainer.courses.learner-tracking');

// Trainer Analytics
Route::get('/trainer/analytics', [AnalyticsController::class, 'analytics'])
    ->name('trainer.analytics');

/*
|--------------------------------------------------------------------------
| Trainer Training Proposal Routes
|--------------------------------------------------------------------------
*/

Route::get('/trainer/proposals', [ProposalController::class, 'proposals'])
    ->name('trainer.proposals');

Route::post('/trainer/proposals', [ProposalController::class, 'storeProposal'])
    ->name('trainer.proposals.store');

Route::delete('/trainer/proposals/{proposal_id}', [ProposalController::class, 'deleteProposal'])
    ->name('trainer.proposals.delete');

/*
|--------------------------------------------------------------------------
| Trainer Profile Routes
|--------------------------------------------------------------------------
*/

Route::get('/trainer/profile', [ProfileController::class, 'profile'])
    ->name('trainer.profile');

Route::post('/trainer/profile/password', [ProfileController::class, 'updatePassword'])
    ->name('trainer.profile.password');

/*
|--------------------------------------------------------------------------
| Trainer Simple Training Workspace Routes
|--------------------------------------------------------------------------
*/

Route::get('/trainer/simple/{course_id}', [SimpleTrainingController::class, 'simpleOverview'])
    ->name('trainer.simple.overview');

Route::get('/trainer/simple/{course_id}/participants', [SimpleTrainingController::class, 'simpleParticipants'])
    ->name('trainer.simple.participants');

Route::get('/trainer/simple/{course_id}/materials', [SimpleTrainingController::class, 'simpleMaterials'])
    ->name('trainer.simple.materials');

Route::post('/trainer/simple/{course_id}/materials', [SimpleTrainingController::class, 'storeSimpleMaterial'])
    ->name('trainer.simple.materials.store');

Route::post('/trainer/simple/materials/{material_id}/delete', [SimpleTrainingController::class, 'deleteSimpleMaterial'])
    ->name('trainer.simple.materials.delete');

Route::get('/trainer/simple/materials/{material_id}/view', [SimpleTrainingController::class, 'viewSimpleMaterial'])
    ->name('trainer.simple.materials.view');

Route::get('/trainer/simple/{course_id}/attendance', [SimpleTrainingController::class, 'simpleAttendance'])
    ->name('trainer.simple.attendance');

Route::get('/trainer/courses/{course_id}/submissions/{submission_id}/download', [SubmissionController::class, 'downloadSubmissionFile'])
    ->name('trainer.courses.submissions.download');

Route::post('/trainer/profile/picture', [ProfileController::class, 'updatePicture'])->name('trainer.profile.picture');
Route::delete('/trainer/profile/picture', [ProfileController::class, 'pictureRemove'])->name('trainer.profile.picture.remove');
Route::post('/trainer/profile/certificate', [ProfileController::class, 'certificateUpload'])->name('trainer.profile.certificate');
Route::delete('/trainer/profile/certificate/{certId}', [ProfileController::class, 'certificateRemove'])->name('trainer.profile.certificate.remove');

Route::get('/admin/proposals', [AdminProposalController::class, 'proposals'])->name('admin.proposals');
Route::post('/admin/proposals/{proposalId}/approve', [AdminProposalController::class, 'approveProposal'])->name('admin.proposals.approve');
Route::post('/admin/proposals/{proposalId}/reject', [AdminProposalController::class, 'rejectProposal'])->name('admin.proposals.reject');

Route::get('/admin/activity', [AdminMonitoringController::class, 'activityLog'])->name('admin.activity');

Route::get('/admin/courses', [AdminMonitoringController::class, 'courses'])->name('admin.courses');
Route::patch('/admin/courses/{courseId}/participant-material-access', [AdminMonitoringController::class, 'updateParticipantMaterialAccess'])
    ->name('admin.courses.participant-material-access.update');
Route::post('/admin/courses/{courseId}/trainers/{trainerId}/teaching-plans/{planId}/comments', [AdminTeachingPlanController::class, 'storeComment'])
    ->whereNumber('planId')
    ->name('admin.courses.teaching-plans.comments.store');
Route::get('/admin/courses/{courseId}/trainers/{trainerId}/teaching-plans/{planId}/view', [AdminTeachingPlanController::class, 'view'])
    ->whereNumber('planId')
    ->name('admin.courses.teaching-plans.view');
Route::get('/admin/courses/{courseId}/trainers/{trainerId}/teaching-plans/{planId}/download', [AdminTeachingPlanController::class, 'download'])
    ->whereNumber('planId')
    ->name('admin.courses.teaching-plans.download');

Route::get('/admin/analytics', [AdminAnalyticsController::class, 'index'])->name('admin.analytics');

Route::get('/admin/directory', [AdminDirectoryController::class, 'directory'])->name('admin.directory');
Route::get('/admin/directory/trainers/{trainerId}/certificates/{certId}/view', [AdminDirectoryController::class, 'viewTrainerCertificate'])
    ->whereNumber('certId')
    ->name('admin.directory.trainer.certificate.view');

Route::get('/admin/profile', [AdminProfileController::class, 'profile'])->name('admin.profile');
Route::post('/admin/profile/password', [AdminProfileController::class, 'updatePassword'])->name('admin.profile.password');
Route::post('/admin/profile/picture', [AdminProfileController::class, 'updatePicture'])->name('admin.profile.picture');
Route::post('/admin/profile/picture/remove', [AdminProfileController::class, 'deletePicture'])->name('admin.profile.picture.remove');

Route::get('/trainer/profile/document/{docId}/view', [ProfileController::class, 'documentView'])->name('trainer.profile.document.view');

Route::get('/trainer/feedback', [FeedbackController::class, 'feedback'])->name('trainer.feedback');
