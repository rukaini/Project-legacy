<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\SsoController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SchoolController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\PrincipalController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\HrAdminController;
use App\Http\Controllers\teacher\TeacherDashboardController;
use App\Http\Controllers\principal\PrincipalDashboardController;
use App\Http\Controllers\staff\StaffDashboardController;

/*
|--------------------------------------------------------------------------
| ROOT ROUTE
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return redirect()->route('login');
});

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

// Old local login is retired: GET /login redirects to Al Amin Login.
// (Middleware and SSO failures use route('login'), so they land on Al Amin Login too.)
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');

// SSO callback: Al Amin Login sends the user here with a one-time ?code=
Route::get('/sso/callback', [SsoController::class, 'callback'])
    ->middleware('throttle:30,1')
    ->name('sso.callback');

/*
|--------------------------------------------------------------------------
| Common Routes
|--------------------------------------------------------------------------
*/

Route::get('/change-password', [AuthController::class, 'showChangePassword'])->name('change.password');
Route::post('/change-password', [AuthController::class, 'changePassword'])->name('change.password.submit');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

/*
|--------------------------------------------------------------------------
| STAFF MODULE
|--------------------------------------------------------------------------
*/

Route::middleware(['auth.staff'])->group(function () {
    Route::get('/staff-dashboard', [StaffDashboardController::class, 'index'])->name('staff.dashboard');
});

/*
|--------------------------------------------------------------------------
| HR MODULE
|--------------------------------------------------------------------------
*/

Route::middleware(['web', 'auth.hr'])->group(function () {
        // ================================================================
    // DASHBOARD
    // ================================================================
Route::get('/hr/dashboard', [DashboardController::class, 'index'])->name('hr.dashboard');
    // ================================================================
    // SCHOOL MANAGEMENT
    // ================================================================
    Route::get('/school-list', [SchoolController::class, 'list'])->name('school.list');
    Route::get('/school-records', [SchoolController::class, 'records'])->name('school.records');
    Route::get('/school/organizations', [SchoolController::class, 'organizations'])->name('school.organizations');
    Route::get('/school/upload', [SchoolController::class, 'uploadForm'])->name('school.upload');
    Route::post('/school/upload', [SchoolController::class, 'importCSV'])->name('school.import');
    Route::get('/school/download-template', [SchoolController::class, 'downloadTemplate'])->name('school.download-template');
    Route::post('/school/store', [SchoolController::class, 'store'])->name('school.store');
    Route::get('/school/{id}', [SchoolController::class, 'show'])->name('school.show');
    Route::get('/school/{id}/edit', [SchoolController::class, 'edit'])->name('school.edit');
    Route::put('/school/{id}', [SchoolController::class, 'update'])->name('school.update');
    Route::delete('/school/{id}', [SchoolController::class, 'destroy'])->name('school.destroy');

    // ================================================================
    // PRINCIPAL MANAGEMENT
    // ================================================================
    Route::get('/principals', [PrincipalController::class, 'index'])->name('principal.index');
    Route::get('/principals/stopped', [PrincipalController::class, 'stopped'])->name('principal.stopped');
    Route::get('/principals/template', [PrincipalController::class, 'template'])->name('principals.template');
    Route::post('/principals/import', [PrincipalController::class, 'import'])->name('principals.import');
    Route::post('/principals', [PrincipalController::class, 'store'])->name('principals.store');
    Route::get('/principals/{principalID}', [PrincipalController::class, 'show'])->name('principals.show');
    Route::get('/principals/{principalID}/edit', [PrincipalController::class, 'edit'])->name('principals.edit');
    Route::put('/principals/{principalID}', [PrincipalController::class, 'update'])->name('principals.update');
    Route::post('/principals/{principalID}/terminate', [PrincipalController::class, 'terminate'])->name('principals.terminate');
    Route::delete('/principals/{principalID}', [PrincipalController::class, 'destroy'])->name('principals.destroy');

    // ================================================================
    // STAFF MANAGEMENT
    // ================================================================
    Route::get('/staff', [StaffController::class, 'index'])->name('staff.list');
    Route::get('/staff/template', [StaffController::class, 'template'])->name('staff.template');
    Route::get('/staff/stopped', [StaffController::class, 'stopped'])->name('staff.stopped');
    Route::get('/staff/resigned', [StaffController::class, 'resigned'])->name('staff.resigned');
    Route::post('/staff/import', [StaffController::class, 'import'])->name('staff.import');
    Route::post('/staff', [StaffController::class, 'store'])->name('staff.store');
    Route::get('/staff/{staffID}', [StaffController::class, 'show'])->name('staff.show');
    Route::get('/staff/{staffID}/edit', [StaffController::class, 'edit'])->name('staff.edit');
    Route::put('/staff/{staffID}', [StaffController::class, 'update'])->name('staff.update');
    Route::post('/staff/{staffID}/terminate', [StaffController::class, 'terminate'])->name('staff.terminate');
    Route::delete('/staff/{staffID}', [StaffController::class, 'destroy'])->name('staff.destroy');

    // ================================================================
    // TEACHER MANAGEMENT
    // ================================================================
    Route::get('/teachers', [TeacherController::class, 'index'])->name('teachers.index');
    Route::get('/teacher-details', [TeacherController::class, 'details'])->name('teachers.details');
    Route::post('/teachers/import', [TeacherController::class, 'import'])->name('teachers.import');
    Route::get('/teachers/template', [TeacherController::class, 'template'])->name('teachers.template');
    Route::post('/teachers', [TeacherController::class, 'store'])->name('teachers.store');
    Route::get('/teachers/stopped', [TeacherController::class, 'stopped'])->name('teachers.stopped');
    Route::get('/teachers/{teacherID}', [TeacherController::class, 'show'])->name('teachers.show');
    Route::get('/teachers/{teacherID}/edit', [TeacherController::class, 'edit'])->name('teachers.edit');
    Route::put('/teachers/{teacherID}', [TeacherController::class, 'update'])->name('teachers.update');
    Route::post('/teachers/{teacherID}/terminate', [TeacherController::class, 'terminate'])->name('teachers.terminate');
    Route::delete('/teachers/{teacherID}', [TeacherController::class, 'destroy'])->name('teachers.destroy');
    
      Route::prefix('hr-admins')->name('hr-admins.')->group(function () {
        Route::get('/',                   [HrAdminController::class, 'index'])->name('index');
        Route::get('/create',             [HrAdminController::class, 'create'])->name('create');
        Route::post('/',                  [HrAdminController::class, 'store'])->name('store');
        Route::get('/{hrAdmin}/edit',     [HrAdminController::class, 'edit'])->name('edit');
        Route::put('/{hrAdmin}',          [HrAdminController::class, 'update'])->name('update');
        Route::delete('/{hrAdmin}',       [HrAdminController::class, 'destroy'])->name('destroy');
        Route::patch('/{hrAdmin}/toggle', [HrAdminController::class, 'toggleStatus'])->name('toggle');
    });

  
});

/*
|--------------------------------------------------------------------------
| ORGANIZATION MANAGEMENT
|--------------------------------------------------------------------------
*/
Route::get('/orgs', [OrganizationController::class, 'index'])->name('orgs.index');
Route::get('/orgs/download-template', [OrganizationController::class, 'downloadTemplate'])->name('orgs.download-template');
Route::post('/orgs', [OrganizationController::class, 'store'])->name('orgs.store');
Route::get('/orgs/create', [OrganizationController::class, 'create'])->name('orgs.create');
Route::get('/orgs/{id}', [OrganizationController::class, 'show'])->name('orgs.show');
Route::get('/orgs/{id}/edit', [OrganizationController::class, 'edit'])->name('orgs.edit');
Route::put('/orgs/{id}', [OrganizationController::class, 'update'])->name('orgs.update');
Route::delete('/orgs/{id}', [OrganizationController::class, 'destroy'])->name('orgs.destroy');
Route::post('/orgs/import', [OrganizationController::class, 'import'])->name('orgs.import');
/*
|--------------------------------------------------------------------------
| TEACHER MODULE
|--------------------------------------------------------------------------
*/

Route::middleware(['auth.teacher'])->group(function () {
    Route::get('/teacher-dashboard', [TeacherDashboardController::class, 'index'])->name('teacher.dashboard');
});

/*
|--------------------------------------------------------------------------
| PRINCIPAL MODULE
|--------------------------------------------------------------------------
*/

Route::middleware(['auth.principal'])->group(function () {
    Route::get('/dashboard', [PrincipalDashboardController::class, 'index'])->name('principal.dashboard');
    Route::get('/principal/teachers', [PrincipalController::class, 'teacherList'])->name('principal.teachers.list');
    Route::get('/principal/teachers/export', [PrincipalController::class, 'exportTeachers'])->name('principal.teachers.export');
    Route::get('/principal/teachers/stats', [PrincipalController::class, 'getTeacherStats'])->name('principal.teachers.stats');
    Route::get('/principal/teachers/{teacherID}', [PrincipalController::class, 'showTeacher'])->name('principal.teachers.show');
    Route::get('/principal/calendar', [PrincipalController::class, 'calendar'])->name('principal.calendar');
    Route::get('/principal/resigned', [PrincipalController::class, 'resignList'])->name('principal.resigned');
    

});

/*
|--------------------------------------------------------------------------
| DEBUG ROUTES
|--------------------------------------------------------------------------
*/

Route::get('/debug-staff-session', function () {
    return [
        'session_has_user' => session()->has('user'),
        'session_role' => session('role'),
        'session_user_id' => session('userID'),
        'session_user_name' => session('userName'),
        'session_all' => session()->all(),
        'user_object' => session('user') ? [
            'class' => get_class(session('user')),
            'staffID' => session('user')->staffID ?? null,
            'email' => session('user')->email ?? null,
            'role' => session('user')->role ?? null,
        ] : null,
        'session_driver' => config('session.driver'),
        'session_lifetime' => config('session.lifetime'),
        'session_id' => session()->getId(),
    ];
});