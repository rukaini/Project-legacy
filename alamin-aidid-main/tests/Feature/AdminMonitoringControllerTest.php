<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminMonitoringControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        Storage::fake('local');

        Schema::create('trainer', function (Blueprint $table): void {
            $table->string('trainerID', 20)->primary();
            $table->string('trainerName');
        });
        Schema::create('staff_edu', function (Blueprint $table): void {
            $table->string('staffID', 20)->primary();
            $table->string('staffName');
        });
        Schema::create('course', function (Blueprint $table): void {
            $table->string('courseID', 20)->primary();
            $table->string('courseName');
            $table->string('courseCategory')->nullable();
            $table->text('description')->nullable();
            $table->unsignedInteger('capacity')->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->string('status')->nullable();
            $table->string('poster')->nullable();
            $table->string('courseType')->nullable();
            $table->string('targetAudience')->nullable();
        });
        Schema::create('participant_material_access', function (Blueprint $table): void {
            $table->string('course_id', 20)->primary();
            $table->boolean('is_enabled')->default(false);
            $table->dateTime('access_until')->nullable();
            $table->timestamps();
        });
        Schema::create('trainer_activity_log', function (Blueprint $table): void {
            $table->bigIncrements('log_id');
            $table->string('trainer_id', 20)->index('idx_trainer');
            $table->string('action', 60)->index('idx_action');
            $table->string('detail', 255)->nullable();
            $table->string('course_id', 20)->nullable();
            $table->string('target_type', 40)->nullable();
            $table->bigInteger('target_id')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent()->index('idx_created');
        });
        Schema::create('course_session', function (Blueprint $table): void {
            $table->string('sessionID', 20)->primary();
            $table->string('courseID', 20);
            $table->string('sessionName')->nullable();
            $table->date('sessionDate')->nullable();
            $table->time('startTime')->nullable();
            $table->time('endTime')->nullable();
            $table->string('location')->nullable();
        });
        Schema::create('session_trainer', function (Blueprint $table): void {
            $table->string('sessionID', 20);
            $table->string('trainerID', 20);
        });
        Schema::create('enroll_guru_baru', function (Blueprint $table): void {
            $table->unsignedInteger('enrollment_id')->primary();
            $table->string('gn_id', 20);
            $table->string('course_id', 20);
            $table->string('status')->default('approved');
            $table->timestamp('applied_at')->nullable();
        });
        Schema::create('enroll_teacher', function (Blueprint $table): void {
            $table->string('teacherID', 20);
            $table->string('course_id', 20);
            $table->string('status')->default('approved');
            $table->timestamp('applied_at')->nullable();
        });
        Schema::create('enroll_outsider', function (Blueprint $table): void {
            $table->unsignedInteger('outsider_id');
            $table->string('course_id', 20);
            $table->string('status')->default('approved');
            $table->timestamp('applied_at')->nullable();
        });
        Schema::create('teacher', function (Blueprint $table): void {
            $table->string('teacherID', 20)->primary();
            $table->string('teacherName');
        });
        Schema::create('guru_new', function (Blueprint $table): void {
            $table->unsignedInteger('gn_id')->primary();
            $table->string('gn_name');
        });
        Schema::create('outsider', function (Blueprint $table): void {
            $table->unsignedInteger('outsider_id')->primary();
            $table->string('name');
        });
        Schema::create('attendance', function (Blueprint $table): void {
            $table->increments('attendance_id');
            $table->string('attendance_status')->nullable();
            $table->string('session_id', 20);
            $table->string('teacher_id', 20);
            $table->decimal('timestamp_status', 8, 2)->nullable();
            $table->decimal('hours_ladap', 8, 2)->nullable();
            $table->timestamps();
        });
        Schema::create('attendance_guru_baru', function (Blueprint $table): void {
            $table->increments('attendGuruBaru_id');
            $table->string('attendance_status')->nullable();
            $table->string('session_id', 20);
            $table->string('gn_id', 20);
            $table->decimal('timestamp_status', 8, 2)->nullable();
            $table->decimal('hours_ladap', 8, 2)->nullable();
            $table->timestamps();
        });
        Schema::create('attendance_outsider', function (Blueprint $table): void {
            $table->increments('attendOutsider_id');
            $table->string('attendance_status')->nullable();
            $table->string('session_id', 20);
            $table->unsignedInteger('outsider_id');
            $table->decimal('timestamp_status', 8, 2)->nullable();
            $table->timestamps();
        });
        Schema::create('teaching_plans', function (Blueprint $table): void {
            $table->id();
            $table->string('course_id', 20);
            $table->string('trainer_id', 20);
            $table->string('original_name');
            $table->string('file_path', 500);
            $table->string('mime_type');
            $table->unsignedBigInteger('file_size');
            $table->timestamp('submitted_at');
            $table->timestamps();
            $table->unique(['course_id', 'trainer_id']);
        });
        Schema::create('teaching_plan_comments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('teaching_plan_id');
            $table->string('reviewer_staff_id', 20);
            $table->text('comment');
            $table->timestamps();
        });

        DB::table('trainer')->insert([
            'trainerID' => 'TR9001',
            'trainerName' => 'Trainer Read Only',
        ]);
        DB::table('staff_edu')->insert([
            'staffID' => 'STAFF7001',
            'staffName' => 'Administrator Test',
        ]);
        DB::table('course')->insert([
            'courseID' => 'C501',
            'courseName' => 'Inclusive Classroom Practice',
            'courseCategory' => 'Professional Learning',
            'description' => 'Practical inclusive teaching strategies.',
            'capacity' => 30,
            'price' => 0,
            'status' => 'Open',
            'poster' => null,
            'courseType' => 'standard',
            'targetAudience' => 'public',
        ]);
        DB::table('trainer_activity_log')->insert([
            'trainer_id' => 'TR9001',
            'course_id' => 'C501',
            'action' => 'module.create',
            'detail' => 'Created the introduction module.',
            'created_at' => now(),
        ]);
        DB::table('course_session')->insert([
            'sessionID' => 'S601',
            'courseID' => 'C501',
            'sessionName' => 'Inclusive Practice Workshop',
            'sessionDate' => now()->toDateString(),
            'startTime' => '09:00:00',
            'endTime' => '11:00:00',
            'location' => 'Training Hall',
        ]);
        DB::table('session_trainer')->insert(['sessionID' => 'S601', 'trainerID' => 'TR9001']);
        DB::table('outsider')->insert([
            'outsider_id' => 801,
            'name' => 'Public Monitoring Participant',
        ]);
        DB::table('guru_new')->insert([
            'gn_id' => 802,
            'gn_name' => 'New Teacher Monitoring Participant',
        ]);
        DB::table('enroll_outsider')->insert([
            'course_id' => 'C501',
            'applied_at' => now()->subWeek(),
            'outsider_id' => 801,
        ]);
        DB::table('enroll_guru_baru')->insert([
            'enrollment_id' => 702,
            'course_id' => 'C501',
            'applied_at' => now()->subDays(3),
            'gn_id' => 802,
        ]);
        DB::table('attendance_guru_baru')->insert([
            'session_id' => 'S601',
            'gn_id' => 802,
            'attendance_status' => 'approved',
            'timestamp_status' => 1.25,
            'hours_ladap' => 2.0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('attendance_outsider')->insert([
            'session_id' => 'S601',
            'outsider_id' => 801,
            'attendance_status' => 'rejected',
            'timestamp_status' => 2.5,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        foreach ([
            'participant_material_access',
            'teaching_plan_comments',
            'teaching_plans',
            'attendance_outsider',
            'attendance_guru_baru',
            'attendance',
            'enroll_outsider',
            'enroll_guru_baru',
            'enroll_teacher',
            'outsider',
            'guru_new',
            'teacher',
            'session_trainer',
            'course_session',
            'trainer_activity_log',
            'course',
            'staff_edu',
            'trainer',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    public function test_activity_monitoring_renders_without_writing_shared_records(): void
    {
        $response = $this->withSession($this->adminSession())
            ->get('/admin/activity');

        $response
            ->assertOk()
            ->assertSee('Trainer Read Only')
            ->assertSee('Inclusive Classroom Practice')
            ->assertSee('Created the introduction module.');

        $this->assertSame(1, DB::table('trainer_activity_log')->count());
        $this->assertSame(1, DB::table('trainer')->count());
        $this->assertSame(1, DB::table('course')->count());
    }

    public function test_activity_monitoring_excludes_historical_navigation_and_read_noise_everywhere(): void
    {
        DB::table('trainer')->insert(['trainerID' => 'TR9002', 'trainerName' => 'Navigation Only Trainer']);
        DB::table('trainer_activity_log')->insert([
            ['trainer_id' => 'TR9001', 'course_id' => 'C501', 'action' => 'training.open', 'detail' => 'Opened a training', 'created_at' => now()],
            ['trainer_id' => 'TR9001', 'course_id' => null, 'action' => 'login', 'detail' => 'Signed in', 'created_at' => now()],
            ['trainer_id' => 'TR9001', 'course_id' => 'C501', 'action' => 'submission.review', 'detail' => 'Viewed file: Participant for Task', 'created_at' => now()],
            ['trainer_id' => 'TR9001', 'course_id' => 'C501', 'action' => 'submission.review', 'detail' => 'Downloaded file: Participant for Task', 'created_at' => now()],
            ['trainer_id' => 'TR9001', 'course_id' => 'C501', 'action' => 'submission.review', 'detail' => 'Downloaded ZIP (2 files): Task', 'created_at' => now()],
            ['trainer_id' => 'TR9001', 'course_id' => 'C501', 'action' => 'submission.review', 'detail' => 'Marked reviewed: Participant for Task', 'created_at' => now()],
            ['trainer_id' => 'TR9001', 'course_id' => 'C501', 'action' => 'module.create', 'detail' => 'Second meaningful module', 'created_at' => now()],
            ['trainer_id' => 'TR9002', 'course_id' => 'C501', 'action' => 'training.open', 'detail' => 'Noise one', 'created_at' => now()],
            ['trainer_id' => 'TR9002', 'course_id' => 'C501', 'action' => 'training.open', 'detail' => 'Noise two', 'created_at' => now()],
            ['trainer_id' => 'TR9002', 'course_id' => 'C501', 'action' => 'training.open', 'detail' => 'Noise three', 'created_at' => now()],
            ['trainer_id' => 'TR9002', 'course_id' => 'C501', 'action' => 'training.open', 'detail' => 'Noise four', 'created_at' => now()],
            ['trainer_id' => 'TR9002', 'course_id' => 'C501', 'action' => 'training.open', 'detail' => 'Noise five', 'created_at' => now()],
        ]);

        $response = $this->withSession($this->adminSession())
            ->get('/admin/activity?action=submission.review&search=participant&trainer=TR9001');

        $response->assertOk()
            ->assertSee('Marked reviewed: Participant for Task')
            ->assertDontSee('Opened a training')
            ->assertDontSee('Viewed file: Participant for Task')
            ->assertDontSee('Downloaded file: Participant for Task')
            ->assertDontSee('Downloaded ZIP (2 files): Task');
        $this->assertSame(3, $response->viewData('activities')->count());
        $this->assertSame(3, $response->viewData('stats')['total']);
        $this->assertSame(3, $response->viewData('stats')['today']);
        $this->assertSame(3, $response->viewData('stats')['week']);
        $this->assertSame('Trainer Read Only', $response->viewData('stats')['topTrainerName']);
        $this->assertSame('module.create', $response->viewData('stats')['topActionKey']);
        $this->assertNotContains('training.open', $response->viewData('actions')->all());
        $this->assertNotContains('login', $response->viewData('actions')->all());
        $this->assertContains('submission.review', $response->viewData('actions')->all());
        $this->assertSame(13, DB::table('trainer_activity_log')->count(), 'Historical rows must remain stored.');
    }

    public function test_activity_monitoring_presents_teaching_plan_and_workspace_actions_with_human_labels(): void
    {
        DB::table('trainer_activity_log')->insert([
            'trainer_id' => 'TR9001',
            'course_id' => 'C501',
            'action' => 'teaching_plan.submit',
            'detail' => 'inclusive-plan.pdf',
            'created_at' => now()->addMinute(),
        ]);

        $response = $this->withSession($this->adminSession())->get('/admin/activity');

        $response->assertOk()
            ->assertSee('Teaching Plan submitted')
            ->assertSee('inclusive-plan.pdf')
            ->assertSee('Module created')
            ->assertSee('Inclusive Classroom Practice')
            ->assertSee('Trainer Read Only');
        $this->assertContains('teaching_plan.submit', $response->viewData('actions')->all());
        $this->assertSame('teaching_plan.submit', $response->viewData('stats')['topActionKey']);
    }

    public function test_activity_custom_period_is_inclusive_and_read_only(): void
    {
        Carbon::setTestNow('2026-08-28 14:00:00');
        DB::table('trainer_activity_log')->where('action', 'module.create')->update([
            'created_at' => '2026-08-28 23:59:59',
        ]);
        DB::table('trainer_activity_log')->insert([
            'trainer_id' => 'TR9001',
            'course_id' => 'C501',
            'action' => 'training.open',
            'detail' => 'Outside the selected period.',
            'created_at' => '2026-08-27 23:59:59',
        ]);
        $before = DB::table('trainer_activity_log')->orderBy('log_id')->get()->map(fn ($row) => (array) $row)->all();

        $response = $this->withSession($this->adminSession())
            ->get('/admin/activity?period=custom&from=2026-08-28&to=2026-08-28&search=introduction&trainer=TR9001&action=module.create&sort=asc&rows=25');

        $response->assertOk();
        $this->assertSame('custom', $response->viewData('activityPeriod')['key']);
        $this->assertSame('28 Aug 2026 - 28 Aug 2026', $response->viewData('activityPeriod')['label']);
        $this->assertSame(1, $response->viewData('stats')['total']);
        $this->assertSame(1, $response->viewData('stats')['today']);
        $this->assertSame([
            'search' => 'introduction',
            'trainer' => 'TR9001',
            'action' => 'module.create',
            'sort' => 'asc',
            'rows' => 25,
        ], $response->viewData('activityControls'));
        $this->assertSame($before, DB::table('trainer_activity_log')->orderBy('log_id')->get()->map(fn ($row) => (array) $row)->all());
    }

    public function test_activity_custom_period_rejects_a_reversed_range_without_writes(): void
    {
        $before = DB::table('trainer_activity_log')->orderBy('log_id')->get()->map(fn ($row) => (array) $row)->all();

        $response = $this->withSession($this->adminSession())
            ->from('/admin/activity')
            ->get('/admin/activity?period=custom&from=2026-08-28&to=2026-08-20');

        $response->assertRedirect('/admin/activity')->assertSessionHasErrors('to');
        $this->assertSame($before, DB::table('trainer_activity_log')->orderBy('log_id')->get()->map(fn ($row) => (array) $row)->all());
    }

    public function test_training_monitoring_exposes_real_master_detail_data_without_writes(): void
    {
        DB::table('outsider')->insert([
            'outsider_id' => 803,
            'name' => 'Participant Without Attendance',
        ]);
        DB::table('enroll_outsider')->insert([
            'course_id' => 'C501',
            'applied_at' => now()->subDay(),
            'outsider_id' => 803,
        ]);
        $tables = ['course', 'course_session', 'session_trainer', 'enroll_guru_baru', 'enroll_outsider', 'attendance_guru_baru', 'attendance_outsider'];
        $before = collect($tables)->mapWithKeys(fn (string $table) => [$table => DB::table($table)->count()])->all();

        $response = $this->withSession($this->adminSession())->get('/admin/courses?course=C501');

        $response
            ->assertOk()
            ->assertSee('Training Monitoring')
            ->assertSee('Inclusive Classroom Practice')
            ->assertSee('Public Monitoring Participant')
            ->assertSee('New Teacher Monitoring Participant')
            ->assertSee('Overview')
            ->assertSee('Teaching Plans')
            ->assertSee('Attendance');
        $training = $response->viewData('trainingData')['C501'];
        $this->assertSame('Trainer Read Only', $training['trainers'][0]['name']);
        $this->assertSame('Public', $training['targetParticipant']);
        $participants = collect($training['participants'])->keyBy('enrollmentId');
        $this->assertSame('Outsider', $participants[801]['typeValue']);
        $this->assertSame('Public', $participants[801]['typeLabel']);
        $this->assertSame('Guru New', $participants[702]['typeValue']);
        $this->assertSame('New Teacher', $participants[702]['typeLabel']);
        $this->assertSame('standard', $training['modeKey']);
        $this->assertSame('simple', $training['modeFilterKey']);
        $this->assertSame('Simple', $training['modeLabel']);
        $this->assertSame('current', $training['scheduleKey']);
        $this->assertSame('Ongoing', $training['scheduleLabel']);
        $this->assertSame('Not Submitted', $training['trainers'][0]['teachingPlan']['stateLabel']);
        $this->assertSame([
            'isEnabled' => false,
            'accessUntil' => null,
            'accessUntilInput' => null,
            'accessUntilLabel' => null,
            'isExpired' => false,
            'effectiveAccess' => false,
            'stateKey' => 'disabled',
            'stateLabel' => 'Disabled',
        ], $training['materialAccess']);
        $this->assertSame([
            'needsReview' => 0,
            'feedbackSent' => 0,
            'notSubmitted' => 1,
        ], $training['teachingPlanCounts']);
        $this->assertSame('Inclusive Practice Workshop', $training['sessions'][0]['title']);
        $this->assertTrue($training['attendanceAvailable']);
        $this->assertSame(2, $training['attendance']['recorded']);
        $this->assertSame(1, $training['attendance']['notRecorded']);
        $this->assertSame(2, $training['sessions'][0]['attendance']['recorded']);
        $this->assertSame(1, $training['sessions'][0]['attendance']['notRecorded']);
        $this->assertSame(1, $participants[801]['attendance']['recorded']);
        $this->assertSame(1, $participants[702]['attendance']['recorded']);
        $records = collect($training['attendanceRecords'])->keyBy('participantName');
        $this->assertSame('Recorded', $records['Public Monitoring Participant']['statusLabel']);
        $this->assertSame('Recorded', $records['New Teacher Monitoring Participant']['statusLabel']);
        $this->assertSame('Not Recorded', $records['Participant Without Attendance']['statusLabel']);
        $this->assertSame($before, collect($tables)->mapWithKeys(fn (string $table) => [$table => DB::table($table)->count()])->all());
    }

    public function test_admin_can_enable_indefinite_material_access(): void
    {
        $this->withSession($this->adminSession())
            ->patch(route('admin.courses.participant-material-access.update', 'C501'), [
                'is_enabled' => '1',
                'access_mode' => 'no_expiry',
            ])
            ->assertRedirect(route('admin.courses', ['course' => 'C501', 'tab' => 'overview']))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('participant_material_access', [
            'course_id' => 'C501',
            'is_enabled' => 1,
            'access_until' => null,
        ]);
    }

    public function test_admin_can_enable_material_access_until_a_future_time(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-13 10:00:00', config('app.timezone')));

        $this->withSession($this->adminSession())
            ->patch(route('admin.courses.participant-material-access.update', 'C501'), [
                'is_enabled' => '1',
                'access_mode' => 'until',
                'access_until' => '2026-12-31T23:59',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('participant_material_access', [
            'course_id' => 'C501',
            'is_enabled' => 1,
            'access_until' => '2026-12-31 23:59:00',
        ]);
    }

    public function test_disabling_access_clears_expiry_and_repeated_saves_update_one_row(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-13 10:00:00', config('app.timezone')));
        $url = route('admin.courses.participant-material-access.update', 'C501');

        $this->withSession($this->adminSession())->patch($url, [
            'is_enabled' => '1', 'access_mode' => 'until', 'access_until' => '2026-12-31T23:59',
        ])->assertRedirect();
        $this->withSession($this->adminSession())->patch($url, [
            'is_enabled' => '0', 'access_mode' => 'no_expiry',
        ])->assertRedirect();

        $this->assertSame(1, DB::table('participant_material_access')->where('course_id', 'C501')->count());
        $this->assertDatabaseHas('participant_material_access', [
            'course_id' => 'C501', 'is_enabled' => 0, 'access_until' => null,
        ]);
    }

    public function test_updating_one_course_does_not_change_another_course(): void
    {
        DB::table('course')->insert([
            'courseID' => 'C502', 'courseName' => 'Second Training', 'courseType' => 'standard',
        ]);
        DB::table('participant_material_access')->insert([
            'course_id' => 'C502', 'is_enabled' => 1, 'access_until' => null,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->withSession($this->adminSession())
            ->patch(route('admin.courses.participant-material-access.update', 'C501'), [
                'is_enabled' => '0', 'access_mode' => 'no_expiry',
            ])->assertRedirect();

        $this->assertDatabaseHas('participant_material_access', [
            'course_id' => 'C502', 'is_enabled' => 1, 'access_until' => null,
        ]);
    }

    public function test_material_access_update_rejects_missing_course_and_unauthorized_user(): void
    {
        $payload = ['is_enabled' => '1', 'access_mode' => 'no_expiry'];
        $this->withSession($this->adminSession())
            ->patch(route('admin.courses.participant-material-access.update', 'MISSING'), $payload)
            ->assertNotFound();
        $this->withSession(['user_role' => 'trainer'])
            ->patch(route('admin.courses.participant-material-access.update', 'C501'), $payload)
            ->assertRedirect(route('login'));
        $this->assertSame(0, DB::table('participant_material_access')->count());
    }

    public function test_material_access_update_validates_until_value(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-13 10:00:00', config('app.timezone')));
        $url = route('admin.courses.participant-material-access.update', 'C501');
        $base = ['is_enabled' => '1', 'access_mode' => 'until'];

        $this->withSession($this->adminSession())->from('/admin/courses?course=C501')
            ->patch($url, $base)
            ->assertSessionHasErrorsIn('materialAccess', ['access_until']);
        $this->withSession($this->adminSession())->from('/admin/courses?course=C501')
            ->patch($url, $base + ['access_until' => 'not-a-date'])
            ->assertSessionHasErrorsIn('materialAccess', ['access_until']);
        $this->withSession($this->adminSession())->from('/admin/courses?course=C501')
            ->patch($url, $base + ['access_until' => '2026-10-13T09:59'])
            ->assertSessionHasErrorsIn('materialAccess', ['access_until']);
        $this->withSession($this->adminSession())->from('/admin/courses?course=C501')
            ->patch($url, ['is_enabled' => '1', 'access_mode' => 'later'])
            ->assertSessionHasErrorsIn('materialAccess', ['access_mode']);
        $this->withSession($this->adminSession())->from('/admin/courses?course=C501')
            ->patch($url, ['is_enabled' => 'sometimes', 'access_mode' => 'no_expiry'])
            ->assertSessionHasErrorsIn('materialAccess', ['is_enabled']);
        $this->assertSame(0, DB::table('participant_material_access')->count());
    }

    public function test_monitoring_exposes_indefinite_current_and_expired_access_states(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-13 10:00:00', config('app.timezone')));
        foreach (['C502' => 'Forever', 'C503' => 'Current', 'C504' => 'Expired', 'C505' => 'Expiry Boundary'] as $id => $name) {
            DB::table('course')->insert(['courseID' => $id, 'courseName' => $name, 'courseType' => 'standard']);
        }
        DB::table('participant_material_access')->insert([
            ['course_id' => 'C502', 'is_enabled' => 1, 'access_until' => null, 'created_at' => now(), 'updated_at' => now()],
            ['course_id' => 'C503', 'is_enabled' => 1, 'access_until' => '2026-10-13 10:01:00', 'created_at' => now(), 'updated_at' => now()],
            ['course_id' => 'C504', 'is_enabled' => 1, 'access_until' => '2026-10-13 09:59:00', 'created_at' => now(), 'updated_at' => now()],
            ['course_id' => 'C505', 'is_enabled' => 1, 'access_until' => '2026-10-13 10:00:00', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $data = $this->withSession($this->adminSession())->get('/admin/courses')->viewData('trainingData');

        $this->assertTrue($data['C502']['materialAccess']['effectiveAccess']);
        $this->assertNull($data['C502']['materialAccess']['accessUntil']);
        $this->assertTrue($data['C503']['materialAccess']['effectiveAccess']);
        $this->assertFalse($data['C503']['materialAccess']['isExpired']);
        $this->assertFalse($data['C504']['materialAccess']['effectiveAccess']);
        $this->assertTrue($data['C504']['materialAccess']['isExpired']);
        $this->assertSame('Expired', $data['C504']['materialAccess']['stateLabel']);
        $this->assertTrue($data['C505']['materialAccess']['effectiveAccess']);
        $this->assertFalse($data['C505']['materialAccess']['isExpired']);
    }

    public function test_teaching_plan_data_lists_every_assigned_trainer_and_tracks_current_plan_attention(): void
    {
        DB::table('trainer')->insert([
            ['trainerID' => 'TR9002', 'trainerName' => 'Trainer Without Plan'],
            ['trainerID' => 'TR9003', 'trainerName' => 'Trainer Needs Review'],
        ]);
        DB::table('session_trainer')->insert([
            ['sessionID' => 'S601', 'trainerID' => 'TR9002'],
            ['sessionID' => 'S601', 'trainerID' => 'TR9003'],
        ]);
        Storage::disk('local')->put('teaching-plans/course-501/trainer-9001/plan.pdf', 'plan contents');
        Storage::disk('local')->put('teaching-plans/course-501/trainer-9003/plan.pdf', 'awaiting plan contents');
        $planId = DB::table('teaching_plans')->insertGetId([
            'course_id' => 'C501',
            'trainer_id' => 'TR9001',
            'original_name' => 'inclusive-facilitation-teaching-plan-with-a-very-long-readable-filename.pdf',
            'file_path' => 'teaching-plans/course-501/trainer-9001/plan.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 13,
            'submitted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('teaching_plan_comments')->insert([
            'teaching_plan_id' => $planId,
            'reviewer_staff_id' => 'STAFF7001',
            'comment' => 'The sequence is clear.',
            'created_at' => now()->addMinutes(2),
            'updated_at' => now()->addMinutes(2),
        ]);
        DB::table('teaching_plan_comments')->insert([
            'teaching_plan_id' => $planId,
            'reviewer_staff_id' => 'STAFF7001',
            'comment' => 'Keep the reflection prompt in the final activity.',
            'created_at' => now()->addMinutes(3),
            'updated_at' => now()->addMinutes(3),
        ]);
        $needsReviewPlanId = DB::table('teaching_plans')->insertGetId([
            'course_id' => 'C501',
            'trainer_id' => 'TR9003',
            'original_name' => 'awaiting-feedback.pdf',
            'file_path' => 'teaching-plans/course-501/trainer-9003/plan.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 22,
            'submitted_at' => now()->addMinute(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->withSession($this->adminSession())->get('/admin/courses?course=C501&tab=teaching-plans');
        $training = $response->viewData('trainingData')['C501'];
        $trainers = collect($training['trainers'])->keyBy('id');

        $this->assertSame(1, (int) $response->viewData('trainings')->first()->teaching_plan_attention);
        $response->assertSee('1 plan needs Admin review');

        $this->assertSame('Feedback Sent', $trainers['TR9001']['teachingPlan']['stateLabel']);
        $this->assertSame('inclusive-facilitation-teaching-plan-with-a-very-long-readable-filename.pdf', $trainers['TR9001']['teachingPlan']['current']['originalName']);
        $this->assertSame([
            'The sequence is clear.',
            'Keep the reflection prompt in the final activity.',
        ], collect($trainers['TR9001']['teachingPlan']['current']['comments'])->pluck('comment')->all());
        $this->assertArrayNotHasKey('version', $trainers['TR9001']['teachingPlan']['current']);
        $this->assertArrayNotHasKey('versions', $trainers['TR9001']['teachingPlan']);
        $this->assertSame('Not Submitted', $trainers['TR9002']['teachingPlan']['stateLabel']);
        $this->assertNull($trainers['TR9002']['teachingPlan']['current']);
        $this->assertSame('Needs Review', $trainers['TR9003']['teachingPlan']['stateLabel']);
        $this->assertSame([
            'needsReview' => 1,
            'feedbackSent' => 1,
            'notSubmitted' => 1,
        ], $training['teachingPlanCounts']);

        $this->withSession($this->adminSession())
            ->post(route('admin.courses.teaching-plans.comments.store', ['C501', 'TR9003', $needsReviewPlanId]), [
                'comment' => 'Add one worked example before the final activity.',
            ])
            ->assertRedirect(route('admin.courses', [
                'course' => 'C501',
                'tab' => 'teaching-plans',
                'trainer' => 'TR9003',
            ]));

        $updatedResponse = $this->withSession($this->adminSession())
            ->get('/admin/courses?course=C501&tab=teaching-plans&trainer=TR9003');
        $updatedTraining = $updatedResponse->viewData('trainingData')['C501'];
        $updatedTrainers = collect($updatedTraining['trainers'])->keyBy('id');

        $this->assertSame(0, (int) $updatedResponse->viewData('trainings')->first()->teaching_plan_attention);
        $updatedResponse->assertDontSee('plan needs Admin review');
        $this->assertSame('Feedback Sent', $updatedTrainers['TR9003']['teachingPlan']['stateLabel']);
        $this->assertSame([
            'needsReview' => 0,
            'feedbackSent' => 2,
            'notSubmitted' => 1,
        ], $updatedTraining['teachingPlanCounts']);
    }

    private function adminSession(): array
    {
        return [
            'user_id' => 'STAFF7001',
            'user_role' => 'admin',
            'user_name' => 'Administrator Test',
        ];
    }
}
