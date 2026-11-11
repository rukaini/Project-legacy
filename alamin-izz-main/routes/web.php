<?php

use App\Enums\Role;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\Dashboard\GuruBaruDashboardController;
use App\Http\Controllers\GuruBaruApplicationController;
use App\Http\Controllers\Dashboard\GuruBesarDashboardController;
use App\Http\Controllers\Dashboard\OutsiderDashboardController;
use App\Http\Controllers\Dashboard\TeacherDashboardController;
use App\Http\Controllers\AssessmentController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\PenyelarasLadapController;
use App\Http\Controllers\SsoController;
use App\Http\Controllers\OutsiderApplicationController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\LearningMaterialController;
use App\Http\Controllers\TrainingContentInteractionController;
use App\Http\Controllers\TrainingController;
use App\Http\Controllers\TrainingHistoryController;
use App\Http\Controllers\TarbiahController;
use App\Http\Controllers\Hr\HrDashboardController;
use App\Http\Controllers\Hr\HrApplicationController;
use App\Http\Controllers\Hr\HrUserController;
use Illuminate\Support\Facades\Route;

// ── Public ────────────────────────────────────────────────────────────────────
Route::get('/', fn() => redirect()->route('login'))->name('home');

// ── Al Amin Login (SSO) — the local login form no longer exists ──────────────
Route::get('/login', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    // The gateway's own page has no way to display this, so log it here —
    // otherwise a failed SSO attempt (expired code, unmapped role, etc.)
    // just silently bounces back to the gateway with no visible reason.
    if ($error = session('error')) {
        \Illuminate\Support\Facades\Log::info('SSO sign-in failed, returning to Al Amin Login', ['error' => $error]);
    }

    $portal = config('gateway.portal_url');

    abort_unless(is_string($portal) && filter_var($portal, FILTER_VALIDATE_URL), 503, 'Single sign-on is not configured.');

    return redirect()->away($portal);
})->name('login');

Route::get('/sso/callback', [SsoController::class, 'callback'])
    ->middleware('throttle:30,1')
    ->name('sso.callback');

// ── Generic dashboard redirect — handles existing to_route('dashboard') calls ─
Route::middleware('auth')->get('/dashboard', function () {
    $role = auth()->user()->getRoleNames()->first();
    return match($role) {
        Role::GuruBesar->value => redirect()->route('dashboard.guru-besar'),
        Role::Teacher->value   => redirect()->route('dashboard.teacher'),
        Role::GuruBaru->value  => redirect()->route('dashboard.guru-baru'),
        Role::HR->value        => redirect()->route('hr.dashboard'),
        default                => redirect()->route('dashboard.outsider'),
    };
})->name('dashboard');

// ── Guru Besar only ───────────────────────────────────────────────────────────
Route::middleware(['auth', 'role:guru_besar'])->group(function () {
    Route::get('/dashboard/principal', [GuruBesarDashboardController::class, 'index'])
        ->name('dashboard.guru-besar');

    Route::get('/trainings/create', [TrainingController::class, 'create'])->name('trainings.create');
    Route::post('/trainings', [TrainingController::class, 'store'])->name('trainings.store');
    Route::get('/trainings/{training}/edit', [TrainingController::class, 'edit'])->name('trainings.edit');
    Route::patch('/trainings/{training}', [TrainingController::class, 'update'])->name('trainings.update');
    Route::post('/trainings/{training}/cancel', [TrainingController::class, 'cancel'])->name('trainings.cancel');
    Route::post('/trainings/{training}/complete', [TrainingController::class, 'complete'])->name('trainings.complete');

    Route::get('/applications', [ApplicationController::class, 'index'])->name('applications.index');
    Route::post('/applications/{application}/approve', [ApplicationController::class, 'approve'])->name('applications.approve');
    Route::post('/applications/{application}/reject', [ApplicationController::class, 'reject'])->name('applications.reject');
    Route::get('/trainings/{training}/assign', [ApplicationController::class, 'assignForm'])->name('trainings.assign.form');
    Route::post('/trainings/{training}/assign', [ApplicationController::class, 'assign'])->name('trainings.assign');

    Route::get('/trainings/{training}/attendance', [AttendanceController::class, 'manage'])->name('attendance.manage');
    Route::post('/trainings/{training}/attendance/generate', [AttendanceController::class, 'generate'])->name('attendance.generate');
    Route::get('/trainings/{training}/attendance/report', [AttendanceController::class, 'report'])->name('attendance.report');

    Route::get('/teachers/{teacherId}/history', [TrainingHistoryController::class, 'teacherHistory'])->name('history.teacher');
    Route::get('/reports/development-overview', [TrainingHistoryController::class, 'developmentOverview'])->name('reports.development');
    Route::get('/reports/development-overview/pdf', [TrainingHistoryController::class, 'developmentOverviewPdf'])->name('reports.development.pdf');
    Route::get('/trainings/{training}/letters', [ReportController::class, 'attendanceLetter'])->name('reports.letters');

    Route::post('/tarbiah', [TarbiahController::class, 'store'])->name('tarbiah.store');
    Route::put('/tarbiah/{tarbiah}', [TarbiahController::class, 'update'])->name('tarbiah.update');
    Route::delete('/tarbiah/{tarbiah}', [TarbiahController::class, 'destroy'])->name('tarbiah.destroy');

    Route::post('/penyelaras-ladap', [PenyelarasLadapController::class, 'store'])->name('penyelaras-ladap.store');
    Route::put('/penyelaras-ladap/{penyelarasLadap}', [PenyelarasLadapController::class, 'update'])->name('penyelaras-ladap.update');
    Route::delete('/penyelaras-ladap/{penyelarasLadap}',[PenyelarasLadapController::class, 'destroy'])->name('penyelaras-ladap.destroy');
    Route::post('/penyelaras-ladap/attendance/{attendance}/approve', [PenyelarasLadapController::class, 'approve'])->name('penyelaras-ladap.attendance.approve');

    Route::get('/school-certificates', [CertificateController::class, 'school'])->name('certificates.school');
    Route::post('/school-certificates/{certificate}/review', [CertificateController::class, 'review'])->name('certificates.review');
});

// ── Penyelaras Ladap — Guru Besar (manage) and Teacher (view own school) ───────
Route::middleware(['auth', 'role:guru_besar|teacher'])->group(function () {
    Route::get('/penyelaras-ladap', [PenyelarasLadapController::class, 'index'])->name('penyelaras-ladap.index');
    // Teacher views own certificate; Guru Besar views any certificate in their school.
    Route::get('/certificates/{certificate}/file', [CertificateController::class, 'show'])->name('certificates.show');
});

Route::middleware(['auth', 'role:teacher'])->group(function () {
    Route::post('/penyelaras-ladap/{penyelarasLadap}/mark', [PenyelarasLadapController::class, 'mark'])->name('penyelaras-ladap.mark');

    Route::get('/certificates', [CertificateController::class, 'index'])->name('certificates.index');
    Route::post('/certificates', [CertificateController::class, 'store'])->name('certificates.store');
    Route::put('/certificates/{certificate}', [CertificateController::class, 'update'])->name('certificates.update');
    Route::delete('/certificates/{certificate}',[CertificateController::class, 'destroy'])->name('certificates.destroy');
});

// ── Participants (teacher OR outsider) ────────────────────────────────────────
Route::middleware(['auth', 'role:teacher|outsider'])->group(function () {
    Route::get('/dashboard/teacher', [TeacherDashboardController::class, 'index'])
        ->name('dashboard.teacher');
    Route::get('/dashboard/outsider', [OutsiderDashboardController::class, 'index'])
        ->name('dashboard.outsider');

    Route::post('/trainings/{training}/apply', [ApplicationController::class, 'store'])->name('applications.store');
    Route::get('/my-applications', [ApplicationController::class, 'myApplications'])->name('applications.mine');

    Route::get('/trainings/{training}/attendance/mark', [AttendanceController::class, 'markForm'])->name('attendance.mark.form');
    Route::post('/trainings/{training}/attendance/mark', [AttendanceController::class, 'mark'])->name('attendance.mark');

});

// ── Teacher only ──────────────────────────────────────────────────────────────
Route::middleware(['auth', 'role:teacher'])->group(function () {
    Route::post('/tarbiah/{tarbiah}/mark', [TarbiahController::class, 'mark'])->name('tarbiah.mark');
});

// ── Tarbiah — Guru Besar (register) and Teacher (view/attend) ────────────────
Route::middleware(['auth', 'role:guru_besar|teacher'])->group(function () {
    Route::get('/tarbiah', [TarbiahController::class, 'index'])->name('tarbiah.index');
});

// ── Outsider only ─────────────────────────────────────────────────────────────
Route::middleware(['auth', 'role:outsider'])->group(function () {
    Route::post('/trainings/{training}/outsider-apply', [OutsiderApplicationController::class, 'store'])
        ->name('outsider.applications.store');
});

// ── Guru Baru (applicant) ─────────────────────────────────────────────────────
Route::middleware(['auth', 'role:guru_baru'])->group(function () {
    Route::get('/dashboard/guru-baru', [GuruBaruDashboardController::class, 'index'])
        ->name('dashboard.guru-baru');

    Route::post('/trainings/{training}/guru-baru-apply', [GuruBaruApplicationController::class, 'store'])
        ->name('guru_baru.applications.store');
    Route::get('/guru-baru/my-courses', [GuruBaruApplicationController::class, 'myCourses'])
        ->name('guru_baru.courses.mine');
    Route::get('/guru-baru/my-applications', [GuruBaruApplicationController::class, 'myApplications'])
        ->name('guru_baru.applications.mine');

    Route::get('/trainings/{training}/assessments/{assessment}', [AssessmentController::class, 'show'])
        ->name('assessments.show');
    Route::post('/trainings/{training}/assessments/{assessment}/submit', [AssessmentController::class, 'store'])
        ->name('assessments.store');

    // Content block interaction logging
    Route::post('/trainings/{training}/blocks/{block}/interact', [TrainingContentInteractionController::class, 'log'])
        ->name('content.interact');
});

// ── All authenticated ─────────────────────────────────────────────────────────
Route::middleware('auth')->group(function () {
    Route::get('/trainings', [TrainingController::class, 'index'])->name('trainings.index');
    Route::get('/trainings/{training}', [TrainingController::class, 'show'])->name('trainings.show');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/mark-read', [NotificationController::class, 'markRead'])->name('notifications.mark-read');
    Route::delete('/notifications', [NotificationController::class, 'clear'])->name('notifications.clear');

    // History — all /export routes must precede /{training} so they are not captured as a slug
    Route::get('/my-history', [TrainingHistoryController::class, 'index'])->name('history.mine');
    Route::get('/my-history/export', [TrainingHistoryController::class, 'export'])->name('history.export');
    Route::get('/my-history/export-outsider', [TrainingHistoryController::class, 'exportOutsider'])->name('history.export.outsider');
    Route::get('/my-history/export-guru-baru', [TrainingHistoryController::class, 'exportGuruBaru'])->name('history.export.guru_baru');
    Route::get('/my-history/{training}', [TrainingHistoryController::class, 'show'])->name('history.show');

    // Learning materials — stream files through auth; ?dl=1 forces download
    Route::get('/learning-materials/{material}', [LearningMaterialController::class, 'show'])
        ->name('learning-materials.show');

    // Feedback submission — any authenticated user
    Route::post('/trainings/{training}/feedback/{form}/submit', [FeedbackController::class, 'submit'])
        ->name('feedback.submit');
});

// ── HR only ──────────────────────────────────────────────────────────────────
Route::middleware(['auth', 'role:hr'])->prefix('hr')->name('hr.')->group(function () {
    Route::get('/dashboard', [HrDashboardController::class, 'index'])->name('dashboard');

    // Applications
    Route::get('/applications', [HrApplicationController::class, 'index'])->name('applications.index');
    Route::post('/applications/outsider/{outsiderId}/{courseId}/approve', [HrApplicationController::class, 'approveOutsider'])->name('applications.outsider.approve');
    Route::post('/applications/outsider/{outsiderId}/{courseId}/reject',  [HrApplicationController::class, 'rejectOutsider'])->name('applications.outsider.reject');
    Route::post('/applications/guru-baru/{enrollment}/approve', [HrApplicationController::class, 'approveGuruBaru'])->name('applications.guru-baru.approve');
    Route::post('/applications/guru-baru/{enrollment}/reject',  [HrApplicationController::class, 'rejectGuruBaru'])->name('applications.guru-baru.reject');

    // User lists
    Route::get('/teachers',  [HrUserController::class, 'teachers'])->name('teachers.index');
    Route::get('/outsiders', [HrUserController::class, 'outsiders'])->name('outsiders.index');
    Route::get('/guru-baru', [HrUserController::class, 'guruBaru'])->name('guru-baru.index');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
