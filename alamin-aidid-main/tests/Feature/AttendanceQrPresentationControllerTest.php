<?php

namespace Tests\Feature;

use App\Services\AttendanceQrService;
use App\Support\ExternalTrainingSchema;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AttendanceQrPresentationControllerTest extends TestCase
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
        Carbon::setTestNow('2026-09-05 10:00:00');

        $this->createTables();
        $this->seedRecords();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_assigned_trainer_can_open_active_qr_presentation_without_activity_logging(): void
    {
        $before = DB::table('trainer_activity_log')->count();
        $presentationUrl = route('trainer.courses.attendance.qr', [10, 20]);

        $response = $this->trainerRequest()
            ->get($presentationUrl)
            ->assertOk()
            ->assertSee('<title>Attendance QR — Opening Workshop</title>', false)
            ->assertSee('Al Amin Edu Oasis · Trainer')
            ->assertSee('Attendance QR')
            ->assertSee('Opening Workshop')
            ->assertSee('05 Sep 2026')
            ->assertSee('09:00 AM – 11:00 AM')
            ->assertSee('https://example.test/active-attendance.png', false)
            ->assertSee('Scan this QR code to record your attendance.')
            ->assertSee('Available until 05 Sep 2026, 10:30 AM')
            ->assertSee(route('trainer.courses.attendance', ['course_id' => 10, 'session_id' => 20]), false)
            ->assertDontSee('QR-ACTIVE-20')
            ->assertDontSee('sessionID=20');

        $response->assertHeader('Cache-Control', 'max-age=0, no-store, private');
        $this->assertSame($before, DB::table('trainer_activity_log')->count());
    }

    public function test_non_trainer_is_redirected_to_login(): void
    {
        $this->get(route('trainer.courses.attendance.qr', [10, 20]))
            ->assertRedirect(route('login'));
    }

    public function test_unassigned_trainer_is_denied(): void
    {
        $this->withSession(['user_role' => 'trainer', 'user_id' => 8])
            ->get(route('trainer.courses.attendance.qr', [10, 20]))
            ->assertForbidden();
    }

    public function test_session_from_another_course_is_not_found(): void
    {
        $this->trainerRequest()
            ->get(route('trainer.courses.attendance.qr', [10, 30]))
            ->assertNotFound()
            ->assertDontSee('foreign-attendance.png');
    }

    public function test_upcoming_completed_expired_missing_and_invalid_qrs_cannot_open_presentation(): void
    {
        foreach ([21, 22, 23, 24, 25] as $sessionId) {
            $this->trainerRequest()
                ->get(route('trainer.courses.attendance.qr', [10, $sessionId]))
                ->assertNotFound();
        }
    }

    public function test_same_presentation_route_supports_simple_training(): void
    {
        $this->trainerRequest()
            ->get(route('trainer.courses.attendance.qr', [12, 40]))
            ->assertOk()
            ->assertSee('<title>Attendance QR — Simple Workshop</title>', false)
            ->assertSee('https://example.test/simple-active.png', false)
            ->assertSee(route('trainer.simple.attendance', ['course_id' => 12, 'session_id' => 40]), false);
    }

    public function test_canonical_string_course_session_and_trainer_ids_authorize_qr_display_directly(): void
    {
        Schema::drop('session_trainer');
        Schema::drop('course_session');
        Schema::drop('course');

        Schema::create('course', function (Blueprint $table): void {
            $table->string('courseID', 20)->primary();
            $table->string('courseName', 255);
            $table->string('courseType', 20)->nullable();
        });
        Schema::create('course_session', function (Blueprint $table): void {
            $table->string('sessionID', 20)->primary();
            $table->date('sessionDate');
            $table->string('sessionName', 255);
            $table->time('startTime');
            $table->time('endTime');
            $table->string('courseID', 20);
        });
        Schema::create('session_trainer', function (Blueprint $table): void {
            $table->string('trainerID', 20);
            $table->string('sessionID', 20);
            $table->primary(['trainerID', 'sessionID']);
        });

        DB::table('course')->insert([
            'courseID' => 'CTEST01',
            'courseName' => 'Synthetic Canonical Training',
            'courseType' => 'LMS',
        ]);
        DB::table('course_session')->insert([
            'sessionID' => 'CSTEST01',
            'sessionDate' => '2026-09-05',
            'sessionName' => 'Synthetic Canonical Session',
            'startTime' => '09:00:00',
            'endTime' => '11:00:00',
            'courseID' => 'CTEST01',
        ]);
        DB::table('session_trainer')->insert([
            'trainerID' => 'TRTEST01',
            'sessionID' => 'CSTEST01',
        ]);
        DB::table('qr_session')->insert([
            'qrID' => 'QR-CANONICAL',
            'qrCode' => 'https://example.test/canonical-attendance.png',
            'attendanceLink' => null,
            'expiryTime' => null,
            'sessionID' => 'CSTEST01',
        ]);

        $this->assertSame('courseID', ExternalTrainingSchema::courseIdColumn());
        $this->assertSame('sessionID', ExternalTrainingSchema::sessionIdColumn());
        $this->assertSame('trainerID', ExternalTrainingSchema::assignmentTrainerIdColumn());
        $session = DB::table('course_session')->where('sessionID', 'CSTEST01')->first();
        $this->assertTrue(app(AttendanceQrService::class)->forSession($session, 'CTEST01')['can_view']);
        $this->assertTrue(DB::table('course_session')
            ->join('session_trainer', 'course_session.sessionID', '=', 'session_trainer.sessionID')
            ->where('course_session.courseID', 'CTEST01')
            ->where('session_trainer.trainerID', 'TRTEST01')
            ->exists());

        $this->withSession([
            'user_role' => 'trainer',
            'user_id' => 'TRTEST01',
            'user_name' => 'Synthetic Trainer',
        ])->get(route('trainer.courses.attendance.qr', ['CTEST01', 'CSTEST01']))
            ->assertOk()
            ->assertSee('Synthetic Canonical Session')
            ->assertSee('https://example.test/canonical-attendance.png', false)
            ->assertSee(route('trainer.courses.attendance', [
                'course_id' => 'CTEST01',
                'session_id' => 'CSTEST01',
            ]), false);
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
            $table->string('courseType')->nullable();
        });

        Schema::create('course_session', function (Blueprint $table): void {
            $table->string('sessionID', 20)->primary();
            $table->string('courseID', 20);
            $table->string('sessionName')->nullable();
            $table->date('sessionDate')->nullable();
            $table->time('time')->nullable();
            $table->time('startTime')->nullable();
            $table->time('endTime')->nullable();
        });

        Schema::create('session_trainer', function (Blueprint $table): void {
            $table->string('sessionID', 20);
            $table->string('trainerID', 20);
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
            $table->string('trainer_id', 20);
            $table->string('action', 60);
            $table->string('detail', 255)->nullable();
            $table->string('course_id', 20)->nullable();
            $table->string('target_type', 40)->nullable();
            $table->bigInteger('target_id')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    private function seedRecords(): void
    {
        DB::table('course')->insert([
            ['courseID' => '10', 'courseName' => 'LMS Training', 'courseType' => 'lms'],
            ['courseID' => '11', 'courseName' => 'Other LMS', 'courseType' => 'lms'],
            ['courseID' => '12', 'courseName' => 'Simple Training', 'courseType' => 'simple'],
        ]);

        DB::table('course_session')->insert([
            ['sessionID' => '20', 'courseID' => '10', 'sessionName' => 'Opening Workshop', 'sessionDate' => '2026-09-05', 'time' => null, 'startTime' => '09:00:00', 'endTime' => '11:00:00'],
            ['sessionID' => '21', 'courseID' => '10', 'sessionName' => 'Upcoming Workshop', 'sessionDate' => '2026-09-06', 'time' => null, 'startTime' => '09:00:00', 'endTime' => '11:00:00'],
            ['sessionID' => '22', 'courseID' => '10', 'sessionName' => 'Completed Workshop', 'sessionDate' => '2026-09-04', 'time' => null, 'startTime' => '09:00:00', 'endTime' => '11:00:00'],
            ['sessionID' => '23', 'courseID' => '10', 'sessionName' => 'Early Expiry Workshop', 'sessionDate' => '2026-09-05', 'time' => null, 'startTime' => '09:00:00', 'endTime' => '11:00:00'],
            ['sessionID' => '24', 'courseID' => '10', 'sessionName' => 'Missing QR Workshop', 'sessionDate' => '2026-09-05', 'time' => null, 'startTime' => '09:00:00', 'endTime' => '11:00:00'],
            ['sessionID' => '25', 'courseID' => '10', 'sessionName' => 'Invalid Schedule Workshop', 'sessionDate' => '2026-09-05', 'time' => null, 'startTime' => '09:00:00', 'endTime' => null],
            ['sessionID' => '30', 'courseID' => '11', 'sessionName' => 'Foreign Workshop', 'sessionDate' => '2026-09-05', 'time' => null, 'startTime' => '09:00:00', 'endTime' => '11:00:00'],
            ['sessionID' => '40', 'courseID' => '12', 'sessionName' => 'Simple Workshop', 'sessionDate' => '2026-09-05', 'time' => null, 'startTime' => '09:00:00', 'endTime' => '11:00:00'],
        ]);

        DB::table('session_trainer')->insert([
            ['sessionID' => '20', 'trainerID' => '7'],
            ['sessionID' => '30', 'trainerID' => '7'],
            ['sessionID' => '40', 'trainerID' => '7'],
        ]);

        DB::table('qr_session')->insert([
            ['qrID' => 'QR-ACTIVE-20', 'qrCode' => 'https://example.test/active-attendance.png', 'attendanceLink' => null, 'expiryTime' => '2026-09-05 10:30:00', 'sessionID' => '20'],
            ['qrID' => 'QR-UPCOMING-21', 'qrCode' => 'https://example.test/upcoming-attendance.png', 'attendanceLink' => null, 'expiryTime' => null, 'sessionID' => '21'],
            ['qrID' => 'QR-COMPLETE-22', 'qrCode' => 'https://example.test/completed-attendance.png', 'attendanceLink' => null, 'expiryTime' => null, 'sessionID' => '22'],
            ['qrID' => 'QR-EXPIRED-23', 'qrCode' => 'https://example.test/expired-attendance.png', 'attendanceLink' => null, 'expiryTime' => '2026-09-05 09:30:00', 'sessionID' => '23'],
            ['qrID' => 'QR-INVALID-25', 'qrCode' => 'https://example.test/invalid-attendance.png', 'attendanceLink' => null, 'expiryTime' => null, 'sessionID' => '25'],
            ['qrID' => 'QR-FOREIGN-30', 'qrCode' => 'https://example.test/foreign-attendance.png', 'attendanceLink' => null, 'expiryTime' => null, 'sessionID' => '30'],
            ['qrID' => 'QR-SIMPLE-40', 'qrCode' => 'https://example.test/simple-active.png', 'attendanceLink' => null, 'expiryTime' => null, 'sessionID' => '40'],
        ]);
    }
}
