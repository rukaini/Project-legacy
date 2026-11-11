<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SsoController;

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminProfileController;
use App\Http\Controllers\Admin\ManageFormController;
use App\Http\Controllers\Admin\EvaluationDocController;
use App\Http\Controllers\Admin\PostFormController;
use App\Http\Controllers\Admin\PreFormController;
use App\Http\Controllers\Admin\AuditObservationController;
use App\Http\Controllers\Admin\PdpcFormController;

use App\Http\Controllers\HR\HRDashboardController;
use App\Http\Controllers\HR\HRProfileController;
use App\Http\Controllers\HR\GuruNewController;

use App\Http\Controllers\Observer\OBDashboardController;
use App\Http\Controllers\Observer\OBProfile;
use App\Http\Controllers\Observer\ListEvaluateController;
use App\Http\Controllers\Observer\ManageEvaluateController;
use App\Http\Controllers\Observer\PreObservationController;

use App\Http\Controllers\ExternalObserver\ExtDashboardController;
use App\Http\Controllers\ExternalObserver\EXTProfile;

use App\Http\Controllers\NewTeacher\NewTeacherDashboardController;
use App\Http\Controllers\NewTeacher\GuruNewProfileController;
use App\Http\Controllers\NewTeacher\ResultController;

use App\Http\Controllers\Principal\PrincipalDashboardController;
use App\Http\Controllers\Principal\PrincipalProfileController;
use App\Http\Controllers\Principal\GNListController;
use App\Http\Controllers\Principal\PResultController;

use App\Http\Controllers\EvaluationDocDownloadController;
use App\Http\Controllers\PostObservationController;
use App\Http\Controllers\PdpcObservationController;


Route::get('/', function () {
    return redirect()->route('login');
});


/* Admin */
Route::middleware('auth:admin')->prefix('admin')->group(function () {

    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');
    Route::get('/profile', [AdminProfileController::class, 'index'])->name('admin.profile');
    Route::put('/profile/password', [AdminProfileController::class, 'updatePassword'])->name('admin.profile.password');

    Route::get('/audit-observation', [AuditObservationController::class, 'index'])->name('admin.audit.observation');

    Route::get('/manage-form', [ManageFormController::class, 'index'])->name('admin.manage.form');
    Route::post('/evaluation-doc', [EvaluationDocController::class, 'store'])->name('admin.evaluation.doc.store');
    Route::delete('/evaluation-doc/{doc_id}', [EvaluationDocController::class, 'destroy'])->name('admin.evaluation.doc.delete');

    // PRE FORM
    Route::get('/pre-form', [PreFormController::class, 'index'])->name('admin.pre.form');
    Route::post('/pre-form', [PreFormController::class, 'storeForm'])->name('admin.pre.form.store');
    Route::get('/pre-form/{preForm}/edit', [PreFormController::class, 'edit'])->name('admin.pre.form.edit');
    Route::get('/pre-form/{preForm}/preview', [PreFormController::class, 'preview'])->name('admin.pre.form.preview');
    Route::put('/pre-form/{preForm}', [PreFormController::class, 'updateForm'])->name('admin.pre.form.update');
    Route::delete('/pre-form/{preForm}', [PreFormController::class, 'destroyForm'])->name('admin.pre.form.delete');
    Route::post('/pre-form/{preForm}/new-version', [PreFormController::class, 'createNewVersion'])->name('admin.pre.form.new-version');

    // PRE SECTION
    Route::post('/pre-section', [PreFormController::class, 'storeSection'])->name('admin.pre.section.store');
    Route::put('/pre-section/{sectionID}', [PreFormController::class, 'updateSection'])->name('admin.pre.section.update');
    Route::delete('/pre-section/{sectionID}', [PreFormController::class, 'destroySection'])->name('admin.pre.section.delete');

    // PRE CRITERIA
    Route::post('/pre-criteria', [PreFormController::class, 'storeCriteria'])->name('admin.pre.criteria.store');
    Route::put('/pre-criteria/{criteriaID}', [PreFormController::class, 'updateCriteria'])->name('admin.pre.criteria.update');
    Route::delete('/pre-criteria/{criteriaID}', [PreFormController::class, 'destroyCriteria'])->name('admin.pre.criteria.delete');

    // PDPC FORM
    Route::get('/pdpc-form', [PdpcFormController::class, 'index'])->name('admin.pdpc.form');
    Route::post('/pdpc-form', [PdpcFormController::class, 'store'])->name('admin.pdpc.form.store');
    Route::get('/pdpc-form/{pdpcForm}/edit', [PdpcFormController::class, 'edit'])->name('admin.pdpc.form.edit');
    Route::get('/pdpc-form/{pdpcForm}/preview', [PdpcFormController::class, 'preview'])->name('admin.pdpc.form.preview');
    Route::put('/pdpc-form/{pdpcForm}', [PdpcFormController::class, 'update'])->name('admin.pdpc.form.update');
    Route::delete('/pdpc-form/{pdpcForm}', [PdpcFormController::class, 'destroy'])->name('admin.pdpc.form.destroy');
    Route::post('/pdpc-form/{pdpcForm}/new-version', [PdpcFormController::class, 'createNewVersion'])->name('admin.pdpc.form.new-version');

    // POST FORM
    Route::get('/post-form', [PostFormController::class, 'index'])->name('admin.post.form');
    Route::post('/post-form', [PostFormController::class, 'storeForm'])->name('admin.post.form.store');
    Route::get('/post-form/{postForm}', [PostFormController::class, 'show'])->name('admin.post.form.show');
    Route::get('/post-form/{postForm}/edit', [PostFormController::class, 'edit'])->name('admin.post.form.edit');
    Route::put('/post-form/{postForm}', [PostFormController::class, 'updateForm'])->name('admin.post.form.update');
    Route::delete('/post-form/{postForm}', [PostFormController::class, 'destroyForm'])->name('admin.post.form.delete');
    Route::post('/post-form/{postForm}/new-version', [PostFormController::class, 'createNewVersion'])->name('admin.post.form.new-version');

    // SECTION
    Route::post('/post-section', [PostFormController::class, 'storeSection'])->name('admin.post.section.store');
    Route::put('/post-section/{sectionID}', [PostFormController::class, 'updateSection'])->name('admin.post.section.update');
    Route::delete('/post-section/{sectionID}', [PostFormController::class, 'destroySection'])->name('admin.post.section.delete');

    // FIELD
    Route::post('/post-field', [PostFormController::class, 'storeField'])->name('admin.post.field.store');
    Route::put('/post-field/{fieldID}', [PostFormController::class, 'updateField'])->name('admin.post.field.update');
    Route::delete('/post-field/{fieldID}', [PostFormController::class, 'destroyField'])->name('admin.post.field.delete');
});


/* HR */
Route::middleware('auth:hr')->prefix('hr')->group(function () {

    Route::get('/dashboard', [HRDashboardController::class, 'index'])->name('hr.dashboard');
    Route::get('/profile', [HRProfileController::class, 'index'])->name('hr.profile');
    Route::put('/profile/password', [HRProfileController::class, 'updatePassword'])->name('hr.profile.password');

    Route::get('/new-teachers', [GuruNewController::class, 'index'])->name('hr.gurunew.index');
    Route::get('/new-teachers/create', [GuruNewController::class, 'create'])->name('hr.gurunew.create');
    Route::post('/new-teachers', [GuruNewController::class, 'store'])->name('hr.gurunew.store');
    Route::put('/new-teachers/{gn_id}', [GuruNewController::class, 'update'])->name('hr.gurunew.update');
    Route::get('/teacher-progress', [\App\Http\Controllers\HR\TeacherProgressController::class, 'index'])->name('hr.teacher.progress');
});


/* Observer */
Route::middleware('auth:teacher')->prefix('observer')->group(function () {

    Route::get('/dashboard', [OBDashboardController::class, 'index'])->name('observer.dashboard');
    Route::get('/profile', [OBProfile::class, 'index'])->name('observer.profile');
    Route::put('/profile/password', [OBProfile::class, 'updatePassword'])->name('observer.profile.password');
    Route::get('/download-form', [EvaluationDocDownloadController::class, 'observer'])->name('observer.download.form');

    // PRE OBSERVATION
    Route::get('/pre-observation/{gn_id}', [PreObservationController::class, 'create'])->name('observer.pre.create');
    Route::post('/pre-observation/{gn_id}', [PreObservationController::class, 'store'])->name('observer.pre.store');
    Route::get('/pre-observation/{responseID}/edit', [PreObservationController::class, 'edit'])->name('observer.pre.edit');
    Route::put('/pre-observation/{responseID}', [PreObservationController::class, 'update'])->name('observer.pre.update');
    Route::get('/pre-observation/{responseID}/view', [PreObservationController::class, 'show'])->name('observer.pre.view');

    // PDPC OBSERVATION
    Route::get('/pdpc-observation/{gn_id}/create', [PdpcObservationController::class, 'create'])->name('observer.pdpc.create');
    Route::post('/pdpc-observation/{gn_id}', [PdpcObservationController::class, 'store'])->name('observer.pdpc.store');
    Route::get('/pdpc-observation/{responseID}/edit', [PdpcObservationController::class, 'edit'])->name('observer.pdpc.edit');
    Route::put('/pdpc-observation/{responseID}', [PdpcObservationController::class, 'update'])->name('observer.pdpc.update');
    Route::get('/pdpc-observation/{responseID}/view', [PdpcObservationController::class, 'show'])->name('observer.pdpc.view');

    // POST / FEEDBACK OBSERVATION
    Route::get('/post-observation/{gn_id}', [PostObservationController::class, 'create'])->name('observer.post.create');
    Route::post('/post-observation/{gn_id}', [PostObservationController::class, 'store'])->name('observer.post.store');
    Route::get('/post-observation/{responseID}/edit', [PostObservationController::class, 'edit'])->name('observer.post.edit');
    Route::put('/post-observation/{responseID}', [PostObservationController::class, 'update'])->name('observer.post.update');
    Route::get('/post-observation/{responseID}/view', [PostObservationController::class, 'show'])->name('observer.post.view');

    Route::get('/list-evaluate', [ListEvaluateController::class, 'index'])->name('observer.list.evaluate');
    Route::get('/manage/{gn_id}', [ManageEvaluateController::class, 'index'])->name('observer.manage');
});


/* External */
Route::middleware('auth:teacher')->prefix('external-observer')->group(function () {

    Route::get('/dashboard', [ExtDashboardController::class, 'index'])->name('external.dashboard');
    Route::get('/profile', [EXTProfile::class, 'index'])->name('external.profile');
    Route::put('/profile/password', [EXTProfile::class, 'updatePassword'])->name('external.profile.password');
    Route::get('/download-form', [EvaluationDocDownloadController::class, 'external'])->name('external.download.form');

    // Feedback Observation Form
    Route::get('/post-observation/{gn_id}', [PostObservationController::class, 'create'])->name('external.post.create');
    Route::post('/post-observation/{gn_id}', [PostObservationController::class, 'store'])->name('external.post.store');
    Route::get('/post-observation/{responseID}/edit', [PostObservationController::class, 'edit'])->name('external.post.edit');
    Route::put('/post-observation/{responseID}', [PostObservationController::class, 'update'])->name('external.post.update');
    Route::get('/post-observation/{responseID}/view', [PostObservationController::class, 'show'])->name('external.post.view');

    // PDPC observation
    Route::get('/pdpc-observation/{gn_id}/create', [PdpcObservationController::class, 'create'])->name('external.pdpc.create');
    Route::post('/pdpc-observation/{gn_id}', [PdpcObservationController::class, 'store'])->name('external.pdpc.store');
    Route::get('/pdpc-observation/{responseID}/edit', [PdpcObservationController::class, 'edit'])->name('external.pdpc.edit');
    Route::put('/pdpc-observation/{responseID}', [PdpcObservationController::class, 'update'])->name('external.pdpc.update');
    Route::get('/pdpc-observation/{responseID}/view', [PdpcObservationController::class, 'show'])->name('external.pdpc.view');

    Route::get('/list-evaluate', [ListEvaluateController::class, 'index'])->name('external.list.evaluate');
    Route::get('/manage/{gn_id}', [ManageEvaluateController::class, 'index'])->name('external.manage');
});


/* GN */
Route::middleware('auth:new_teacher')->prefix('new-teacher')->group(function () {

    Route::get('/dashboard', [NewTeacherDashboardController::class, 'index'])->name('new_teacher.dashboard');
    Route::get('/profile', [GuruNewProfileController::class, 'edit'])->name('new_teacher.profile');
    Route::put('/profile', [GuruNewProfileController::class, 'update'])->name('new_teacher.profile.update');
    Route::put('/profile/password', [GuruNewProfileController::class, 'updatePassword'])->name('new_teacher.profile.password');

    // Evaluation results
    Route::get('/result', [ResultController::class, 'index'])->name('new_teacher.result');
    Route::get('/result/pre/{responseID}', [ResultController::class, 'pre'])->name('new_teacher.result.pre');
    Route::get('/result/pdpc/{responseID}', [ResultController::class, 'pdpc'])->name('new_teacher.result.pdpc');
    Route::get('/result/post/{responseID}', [ResultController::class, 'post'])->name('new_teacher.result.post');
});


/* Principal */
Route::middleware('auth:principal')->prefix('principal')->group(function () {


    Route::get('/dashboard', [PrincipalDashboardController::class, 'index'])->name('principal.dashboard');
    Route::get('/profile', [PrincipalProfileController::class, 'index'])->name('principal.profile');
    Route::put('/profile/password', [PrincipalProfileController::class, 'updatePassword'])->name('principal.profile.password');

    // New Teacher List
    Route::get('/list-teacher', [GNListController::class, 'index'])->name('principal.gn.list');
    // Training Progress 
    Route::get('/training-progress', [\App\Http\Controllers\Principal\TrainingProgressController::class, 'index'])->name('principal.training.progress');

    // Evaluation Results
    Route::get('/result', [PResultController::class, 'index'])->name('principal.result');

    Route::get('/result/pre/{responseID}', [PResultController::class, 'pre'])->name('principal.result.pre');
    Route::get('/result/pdpc/{responseID}', [PResultController::class, 'pdpc'])->name('principal.result.pdpc');
    Route::get('/result/post/{responseID}', [PResultController::class, 'post'])->name('principal.result.post');

    Route::get('/result/{gn_id}', [PResultController::class, 'show'])->name('principal.result.show');
});


/* Shared evaluation document download */
Route::get('/evaluation-doc/{doc_id}/download', [EvaluationDocDownloadController::class, 'download'])
    ->middleware('auth:teacher')
    ->name('evaluation.doc.download');


Route::get('/sso/callback', [SsoController::class, 'callback'])
    ->middleware('throttle:30,1')
    ->name('sso.callback');


/* Authentication Routes */
require __DIR__ . '/auth.php';
