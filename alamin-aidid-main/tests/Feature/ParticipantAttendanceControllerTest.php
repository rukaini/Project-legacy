<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ParticipantAttendanceControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite');

        $this->createTables();
        $this->seedReadOnlyRecords();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_participant_page_renders_external_records_without_writing_them(): void
    {
        $before = $this->sharedTableCounts();

        $this->trainerRequest()
            ->get(route('trainer.courses.participants', 10))
            ->assertOk()
            ->assertSee('Participant List')
            ->assertSee('Teacher Example')
            ->assertSee('teacher@example.test')
            ->assertSee('Accessed')
            ->assertSee('Last Accessed')
            ->assertDontSee('TCH-30');

        $this->assertSame($before, $this->sharedTableCounts());
    }

    public function test_shared_lms_header_uses_only_stable_identity_badges(): void
    {
        $response = $this->trainerRequest()
            ->get(route('trainer.courses.participants', 10))
            ->assertOk()
            ->assertSee('data-lms-workspace-header', false)
            ->assertSee('LMS')
            ->assertDontSee('Teacher Training')
            ->assertSee('Teacher');

        $header = $response->viewData('course');
        $this->assertSame('Teacher Training', $header->course_category);
        $response->assertDontSee('1 enrolled')->assertDontSee('1 accessed');
    }

    public function test_participant_filters_and_exact_server_page_sizes_are_course_scoped_and_read_only(): void
    {
        $this->seedAdditionalParticipants(29);
        $before = $this->sharedTableCounts();

        foreach ([10, 25, 50] as $rows) {
            $response = $this->trainerRequest()->get(route('trainer.courses.participants', ['course_id' => 10, 'rows' => $rows]));
            $response->assertOk();
            $this->assertSame($rows, $response->viewData('participantPaginator')->perPage());
        }

        $filtered = $this->trainerRequest()->get(route('trainer.courses.participants', [
            'course_id' => 10,
            'search' => 'Teacher Example',
            'access' => 'accessed',
            'letter' => 'T',
        ]));
        $filtered->assertOk()->assertSee('Teacher Example')->assertDontSee('Other Course Person');
        $this->assertSame(1, $filtered->viewData('participantPaginator')->total());
        $this->assertSame($before, $this->sharedTableCounts());
    }

    public function test_attendance_page_renders_external_records_without_writing_them(): void
    {
        Carbon::setTestNow('2026-08-05 10:00:00');
        $before = $this->sharedTableCounts();

        $response = $this->trainerRequest()
            ->get(route('trainer.courses.attendance', [
                'course_id' => 10,
                'session_id' => 20,
            ]))
            ->assertOk()
            ->assertDontSee('Attendance Roster')
            ->assertDontSee('lmsAttendanceRosterSummary', false)
            ->assertSee('Review attendance records and display the session QR code during active sessions.')
            ->assertSee('Teacher Example')
            ->assertSee('Recorded')
            ->assertSee('Not Recorded')
            ->assertSee('QR available')
            ->assertSee('View QR Code')
            ->assertSee('data-qr-state="active"', false)
            ->assertSee('Available until 05 Aug 2026, 11:00 AM')
            ->assertDontSee('Present Full Screen')
            ->assertDontSee('Open QR in New Tab')
            ->assertSee('Open QR Display')
            ->assertSee(route('trainer.courses.attendance.qr', [10, 20]), false)
            ->assertSee('Opening Workshop')
            ->assertSee('05 Aug 2026')
            ->assertSee('09:00 AM')
            ->assertDontSee('QR-LMS-20');
        $this->assertDoesNotMatchRegularExpression('/<div class="lw-att-status[^>]*">(?:Approved|Pending|Rejected)<\/div>/', $response->getContent());

        foreach (['RSVP', 'Will Come', 'Not Coming', 'Maybe', 'No Response'] as $removedLabel) {
            $response->assertDontSee($removedLabel);
        }

        $this->assertSame($before, $this->sharedTableCounts());
    }

    public function test_completed_session_with_null_qr_expiry_does_not_expose_qr_controls_or_url(): void
    {
        Carbon::setTestNow('2026-09-05 10:00:00');
        DB::table('course_session')->where('sessionID', '20')->update([
            'sessionDate' => '2026-05-03',
            'startTime' => '09:00:00',
            'endTime' => '12:00:00',
        ]);
        $before = DB::table('trainer_activity_log')->count();

        $response = $this->trainerRequest()
            ->get(route('trainer.courses.attendance', ['course_id' => 10, 'session_id' => 20]))
            ->assertOk()
            ->assertSee('View QR Code')
            ->assertSee('This session has ended or the QR code has expired.')
            ->assertDontSee('https://example.test/lms-attendance.png');

        $response->assertDontSee(route('trainer.courses.attendance.qr', [10, 20]), false);

        $this->assertMatchesRegularExpression('/<button[^>]*class="attendance-qr-button"[^>]*disabled[^>]*aria-disabled="true"[^>]*>/s', $response->getContent());
        $this->assertDoesNotMatchRegularExpression('/<button[^>]*class="attendance-qr-button"[^>]*data-attendance-qr-open[^>]*>/s', $response->getContent());
        $this->assertSame('expired', $response->viewData('attendanceQr')['state']);
        $this->assertFalse($response->viewData('attendanceQr')['can_view']);
        $this->assertNull($response->viewData('attendanceQr')['image_url']);
        $this->assertSame($before, DB::table('trainer_activity_log')->count());
    }

    public function test_missing_qr_does_not_expose_qr_controls_or_create_activity(): void
    {
        Carbon::setTestNow('2026-08-05 10:00:00');
        DB::table('qr_session')->where('sessionID', '20')->delete();
        $before = DB::table('trainer_activity_log')->count();

        $response = $this->trainerRequest()
            ->get(route('trainer.courses.attendance', ['course_id' => 10, 'session_id' => 20]))
            ->assertOk()
            ->assertSee('View QR Code')
            ->assertSee('QR code is not available.')
            ->assertDontSee('https://example.test/lms-attendance.png');

        $this->assertMatchesRegularExpression('/<button[^>]*class="attendance-qr-button"[^>]*disabled[^>]*aria-disabled="true"[^>]*>/s', $response->getContent());
        $this->assertDoesNotMatchRegularExpression('/<button[^>]*class="attendance-qr-button"[^>]*data-attendance-qr-open[^>]*>/s', $response->getContent());
        $this->assertSame('missing', $response->viewData('attendanceQr')['state']);
        $this->assertFalse($response->viewData('attendanceQr')['can_view']);
        $this->assertSame($before, DB::table('trainer_activity_log')->count());
    }

    public function test_attendance_keeps_the_existing_missing_selection_state(): void
    {
        $before = $this->sharedTableCounts();

        $this->trainerRequest()
            ->get(route('trainer.courses.attendance', [
                'course_id' => 10,
                'session_id' => 999,
            ]))
            ->assertOk()
            ->assertSee('No Session Selected');

        $this->assertSame($before, $this->sharedTableCounts());
    }

    public function test_attendance_status_counts_filters_and_pagination_are_session_scoped_and_read_only(): void
    {
        Carbon::setTestNow('2026-08-13 10:00:00');
        $this->seedAdditionalParticipants(29);
        $before = $this->sharedTableCounts();

        foreach ([10, 25, 50] as $rows) {
            $response = $this->trainerRequest()->get(route('trainer.courses.attendance', ['course_id' => 10, 'session_id' => 20, 'rows' => $rows]));
            $response->assertOk();
            $this->assertSame($rows, $response->viewData('attendancePaginator')->perPage());
            $this->assertSame(30, $response->viewData('attendanceCounts')['total']);
        }

        $filtered = $this->trainerRequest()->get(route('trainer.courses.attendance', [
            'course_id' => 10,
            'session_id' => 20,
            'search' => 'Teacher Example',
            'status' => 'all',
        ]));
        $filtered->assertOk()->assertSee('Teacher Example')->assertSee('Completed');
        $this->assertSame(1, $filtered->viewData('attendancePaginator')->total());
        $this->assertSame(30, $filtered->viewData('attendanceCounts')['total']);
        $this->assertSame(1, $filtered->viewData('attendanceCounts')['recorded']);
        $this->assertSame(29, $filtered->viewData('attendanceCounts')['not_recorded']);
        $recorded = $this->trainerRequest()->get(route('trainer.courses.attendance', [
            'course_id' => 10,
            'session_id' => 20,
            'status' => 'recorded',
        ]));
        $this->assertSame(1, $recorded->viewData('attendancePaginator')->total());
        $recorded->assertSee('Recorded')->assertDontSee('owner status not interpreted');
        $this->assertDoesNotMatchRegularExpression('/<div class="lw-att-status[^>]*">(?:Approved|Pending|Rejected)<\/div>/', $recorded->getContent());
        $this->assertSame($before, $this->sharedTableCounts());
        Carbon::setTestNow();
    }

    public function test_foreign_session_query_cannot_expose_another_course_roster(): void
    {
        DB::table('course')->insert(['courseID' => '11', 'courseName' => 'Other LMS', 'courseCategory' => 'Other', 'courseType' => 'lms', 'targetAudience' => 'Outsider']);
        DB::table('course_session')->insert(['sessionID' => '99', 'courseID' => '11', 'sessionName' => 'Foreign Session', 'sessionDate' => '2026-08-13', 'startTime' => '09:00:00', 'endTime' => '10:00:00', 'location' => null]);
        DB::table('qr_session')->insert(['qrID' => 'QR-FOREIGN', 'qrCode' => 'https://example.test/foreign-qr.png', 'sessionID' => '99']);

        $this->trainerRequest()
            ->get(route('trainer.courses.attendance', ['course_id' => 10, 'session_id' => 99]))
            ->assertOk()
            ->assertSee('No Session Selected')
            ->assertDontSee('Foreign Session')
            ->assertDontSee('foreign-qr.png');
    }

    public function test_unassigned_trainer_cannot_open_participant_or_attendance_pages(): void
    {
        $before = $this->sharedTableCounts();
        $unassignedTrainer = $this->withSession([
            'user_role' => 'trainer',
            'user_id' => 8,
        ]);

        $unassignedTrainer
            ->get(route('trainer.courses.participants', 10))
            ->assertRedirect(route('trainer.courses'))
            ->assertSessionHas('error', 'You are not assigned to this training.');

        $unassignedTrainer
            ->get(route('trainer.courses.attendance', 10))
            ->assertRedirect(route('trainer.courses'))
            ->assertSessionHas('error', 'You are not assigned to this training.');

        $this->assertSame($before, $this->sharedTableCounts());
    }

    private function trainerRequest()
    {
        return $this->withSession([
            'user_role' => 'trainer',
            'user_id' => 7,
            'user_name' => 'Trainer Test',
        ]);
    }

    private function createTables(): void
    {
        Schema::create('course', function (Blueprint $table): void {
            $table->string('courseID', 20)->primary();
            $table->string('courseName');
            $table->string('courseCategory')->nullable();
            $table->text('description')->nullable();
            $table->integer('capacity')->nullable();
            $table->decimal('price')->nullable();
            $table->string('status')->nullable();
            $table->string('courseType')->nullable();
            $table->string('targetAudience')->nullable();
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

        Schema::create('enroll_teacher', function (Blueprint $table): void {
            $table->string('teacherID', 20);
            $table->string('course_id', 20);
            $table->string('status')->default('approved');
            $table->timestamp('applied_at')->nullable();
        });
        Schema::create('enroll_guru_baru', function (Blueprint $table): void {
            $table->increments('enrollment_id');
            $table->string('gn_id', 20);
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
            $table->string('email')->nullable();
            $table->string('gender')->nullable();
        });

        Schema::create('guru_new', function (Blueprint $table): void {
            $table->string('gn_id', 20)->primary();
            $table->string('gn_name')->nullable();
            $table->string('email')->nullable();
        });

        Schema::create('outsider', function (Blueprint $table): void {
            $table->increments('outsider_id');
            $table->string('name')->nullable();
            $table->string('gender')->nullable();
        });

        Schema::create('training_access_log', function (Blueprint $table): void {
            $table->integer('enrollment_id');
            $table->string('courseID', 20);
            $table->timestamp('accessed_at')->nullable();
        });

        Schema::create('attendance', function (Blueprint $table): void {
            $table->integer('attendance_id')->primary();
            $table->string('attendance_status')->nullable();
            $table->decimal('timestamp_status', 5, 2)->default(0);
            $table->decimal('hours_ladap', 5, 2)->default(0);
            $table->string('teacher_id', 20);
            $table->string('session_id', 20);
            $table->timestamps();
        });
        Schema::create('attendance_guru_baru', function (Blueprint $table): void {
            $table->integer('attendGuruBaru_id')->primary();
            $table->string('attendance_status')->nullable();
            $table->decimal('timestamp_status', 5, 2)->default(0);
            $table->decimal('hours_ladap', 5, 2)->default(0);
            $table->string('gn_id', 20);
            $table->string('session_id', 20);
            $table->timestamps();
        });
        Schema::create('attendance_outsider', function (Blueprint $table): void {
            $table->integer('attendOutsider_id')->primary();
            $table->string('attendance_status')->nullable();
            $table->decimal('timestamp_status', 5, 2)->default(0);
            $table->unsignedInteger('outsider_id');
            $table->string('session_id', 20);
            $table->timestamps();
        });

        Schema::create('qr_session', function (Blueprint $table): void {
            $table->string('qrID', 20)->primary();
            $table->text('qrCode');
            $table->string('attendanceLink', 255)->nullable();
            $table->dateTime('expiryTime')->nullable();
            $table->string('sessionID', 20)->index('qr_session_session_id_index');
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
    }

    private function seedReadOnlyRecords(): void
    {
        DB::table('course')->insert([
            'courseID' => '10',
            'courseName' => 'Inclusive Practice',
            'courseCategory' => 'Teacher Training',
            'courseType' => 'lms',
            'targetAudience' => 'Teacher',
        ]);

        DB::table('course_session')->insert([
            'sessionID' => '20',
            'courseID' => '10',
            'sessionName' => 'Opening Workshop',
            'sessionDate' => '2026-08-05',
            'startTime' => '09:00:00',
            'endTime' => '11:00:00',
            'location' => 'Training Room',
        ]);

        DB::table('session_trainer')->insert([
            'sessionID' => '20',
            'trainerID' => '7',
        ]);

        DB::table('guru_new')->insert([
            'gn_id' => 30,
            'gn_name' => 'Teacher Example',
            'email' => 'teacher@example.test',
        ]);

        DB::table('enroll_guru_baru')->insert([
            'enrollment_id' => 40,
            'course_id' => '10',
            'gn_id' => 30,
            'status' => 'approved',
            'applied_at' => '2026-07-01 09:00:00',
        ]);

        DB::table('training_access_log')->insert([
            'enrollment_id' => 40,
            'courseID' => '10',
            'accessed_at' => '2026-07-30 10:00:00',
        ]);

        DB::table('attendance_guru_baru')->insert([
            'attendGuruBaru_id' => 50,
            'attendance_status' => 'approved',
            'timestamp_status' => 1,
            'hours_ladap' => 2,
            'gn_id' => 30,
            'session_id' => '20',
            'created_at' => '2026-08-05 08:55:00',
            'updated_at' => '2026-08-05 08:55:00',
        ]);

        DB::table('qr_session')->insert([
            'qrID' => 'QR-LMS-20',
            'qrCode' => 'https://example.test/lms-attendance.png',
            'attendanceLink' => 'http://localhost/trainhub/attendance.php?sessionID=20',
            'expiryTime' => null,
            'sessionID' => '20',
        ]);
    }

    private function seedAdditionalParticipants(int $count): void
    {
        for ($index = 1; $index <= $count; $index++) {
            $teacherId = 100 + $index;
            DB::table('teacher')->insert([
                'teacherID' => 'T'.$teacherId,
                'teacherName' => sprintf('Roster Person %02d', $index),
                'email' => "roster{$index}@example.test",
                'gender' => $index % 2 === 0 ? 'Female' : 'Male',
            ]);
            DB::table('enroll_teacher')->insert([
                'teacherID' => 'T'.$teacherId,
                'course_id' => '10',
                'status' => 'approved',
                'applied_at' => '2026-07-01 09:00:00',
            ]);
        }
    }

    private function sharedTableCounts(): array
    {
        return collect([
            'course',
            'course_session',
            'session_trainer',
            'enroll_teacher',
            'enroll_guru_baru',
            'enroll_outsider',
            'teacher',
            'guru_new',
            'outsider',
            'training_access_log',
            'attendance',
            'attendance_guru_baru',
            'attendance_outsider',
            'qr_session',
            'trainer_activity_log',
        ])->mapWithKeys(
            fn (string $table): array => [$table => DB::table($table)->count()]
        )->all();
    }
}
