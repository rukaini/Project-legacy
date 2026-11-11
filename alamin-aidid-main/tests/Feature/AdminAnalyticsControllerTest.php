<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminAnalyticsControllerTest extends TestCase
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
        $this->createCanonicalTables();
        $this->seedSyntheticRecords();
    }

    public function test_admin_can_view_canonical_organization_analytics_without_writes(): void
    {
        $before = $this->snapshot();

        $response = $this->withSession($this->adminSession())
            ->get(route('admin.analytics'))
            ->assertOk()
            ->assertSee('Organization Learning')
            ->assertSee('Community Workshop');

        $metrics = $response->viewData('organizationMetrics')->pluck('value', 'label');
        $this->assertSame(2, $metrics['Total Trainings']);
        $this->assertSame(3, $metrics['Total Participants']);
        $this->assertSame(4, $metrics['Total Sessions']);
        $this->assertSame(17, $response->viewData('organizationAttendance')['rate']);
        $this->assertSame([
            'Recorded' => 1,
            'Not Recorded' => 5,
        ], $response->viewData('organizationAttendance')['counts']);
        $this->assertSame($before, $this->snapshot());
    }

    public function test_canonical_string_course_and_session_filters_are_preserved(): void
    {
        $response = $this->withSession($this->adminSession())
            ->get(route('admin.analytics', ['course_id' => 'COURSE-SIMPLE', 'session_id' => 'SESSION-SIMPLE']))
            ->assertOk()
            ->assertSee('Community Workshop')
            ->assertSee('Community Session');

        $this->assertSame('COURSE-SIMPLE', $response->viewData('selectedTraining')->course_id);
        $this->assertSame('SESSION-SIMPLE', $response->viewData('selectedSessionId'));
    }

    public function test_attendance_uses_canonical_row_existence_without_exposing_owner_status(): void
    {
        $response = $this->withSession($this->adminSession())
            ->get(route('admin.analytics', ['course_id' => 'COURSE-LMS', 'session_id' => 'SESSION-LMS']))
            ->assertOk();

        $participants = $response->viewData('participantAttendance');
        $this->assertSame([
            'Not recorded',
            'Recorded',
        ], $participants->pluck('attendance_status')->sort()->values()->all());
        $this->assertSame('1.25', (string) $participants->firstWhere('participant_name', 'Teacher One')->timestamp_status);
        $this->assertSame(50, $response->viewData('attendanceSummary')['rate']);
        $this->assertSame([
            'Recorded' => 1,
            'Not Recorded' => 1,
        ], $response->viewData('attendanceSummary')['counts']);
        $response
            ->assertSee('records captured')
            ->assertSee('Recorded')
            ->assertDontSee('Approved')
            ->assertDontSee('Pending')
            ->assertDontSee('Rejected')
            ->assertDontSee('owner status not interpreted')
            ->assertDontSee('>Present<', false)
            ->assertDontSee('>Late<', false)
            ->assertDontSee('>Absent<', false);
    }

    public function test_trainer_cannot_access_admin_analytics(): void
    {
        $this->withSession(['user_role' => 'trainer', 'user_id' => 'TR-01'])
            ->get(route('admin.analytics'))
            ->assertRedirect(route('login'));
    }

    private function createCanonicalTables(): void
    {
        Schema::create('course', function (Blueprint $table): void {
            $table->string('courseID', 20)->primary();
            $table->string('courseName');
            $table->string('courseCategory')->nullable();
            $table->string('status')->nullable();
            $table->string('courseType')->nullable();
        });
        Schema::create('course_session', function (Blueprint $table): void {
            $table->string('sessionID', 20)->primary();
            $table->string('courseID', 20);
            $table->string('sessionName')->nullable();
            $table->date('sessionDate')->nullable();
        });
        Schema::create('enroll_teacher', function (Blueprint $table): void {
            $table->string('teacherID', 20);
            $table->string('course_id', 20);
            $table->string('status')->default('pending');
            $table->timestamp('applied_at')->nullable();
        });
        Schema::create('enroll_guru_baru', function (Blueprint $table): void {
            $table->increments('enrollment_id');
            $table->string('gn_id', 20);
            $table->string('course_id', 20);
            $table->string('status')->default('pending');
            $table->timestamp('applied_at')->nullable();
        });
        Schema::create('enroll_outsider', function (Blueprint $table): void {
            $table->unsignedInteger('outsider_id');
            $table->string('course_id', 20);
            $table->string('status')->default('pending');
            $table->timestamp('applied_at')->nullable();
        });
        Schema::create('teacher', function (Blueprint $table): void {
            $table->string('teacherID', 20)->primary();
            $table->string('teacherName');
        });
        Schema::create('guru_new', function (Blueprint $table): void {
            $table->string('gn_id', 20)->primary();
            $table->string('gn_name');
        });
        Schema::create('outsider', function (Blueprint $table): void {
            $table->increments('outsider_id');
            $table->string('name');
        });
        Schema::create('attendance', function (Blueprint $table): void {
            $table->increments('attendance_id');
            $table->string('attendance_status')->nullable();
            $table->string('teacher_id', 20);
            $table->string('session_id', 20);
            $table->decimal('timestamp_status', 5, 2)->nullable();
            $table->decimal('hours_ladap', 5, 2)->nullable();
            $table->timestamps();
        });
        Schema::create('attendance_guru_baru', function (Blueprint $table): void {
            $table->increments('attendGuruBaru_id');
            $table->string('attendance_status')->nullable();
            $table->string('gn_id', 20);
            $table->string('session_id', 20);
            $table->decimal('timestamp_status', 5, 2)->nullable();
            $table->decimal('hours_ladap', 5, 2)->nullable();
            $table->timestamps();
        });
        Schema::create('attendance_outsider', function (Blueprint $table): void {
            $table->increments('attendOutsider_id');
            $table->string('attendance_status')->nullable();
            $table->unsignedInteger('outsider_id');
            $table->string('session_id', 20);
            $table->decimal('timestamp_status', 5, 2)->nullable();
            $table->timestamps();
        });
    }

    private function seedSyntheticRecords(): void
    {
        DB::table('course')->insert([
            ['courseID' => 'COURSE-LMS', 'courseName' => 'Organization Learning', 'courseCategory' => 'Professional', 'status' => 'Active', 'courseType' => 'lms'],
            ['courseID' => 'COURSE-SIMPLE', 'courseName' => 'Community Workshop', 'courseCategory' => 'Community', 'status' => 'Active', 'courseType' => 'standard'],
        ]);
        DB::table('course_session')->insert([
            ['sessionID' => 'SESSION-LMS', 'courseID' => 'COURSE-LMS', 'sessionName' => 'LMS Opening', 'sessionDate' => '2026-08-10'],
            ['sessionID' => 'SESSION-LMS-2', 'courseID' => 'COURSE-LMS', 'sessionName' => 'LMS Follow-up', 'sessionDate' => '2026-08-11'],
            ['sessionID' => 'SESSION-SIMPLE', 'courseID' => 'COURSE-SIMPLE', 'sessionName' => 'Community Session', 'sessionDate' => '2026-08-20'],
            ['sessionID' => 'SESSION-SIMPLE-2', 'courseID' => 'COURSE-SIMPLE', 'sessionName' => 'Community Follow-up', 'sessionDate' => '2026-08-21'],
        ]);
        DB::table('teacher')->insert([
            ['teacherID' => 'TEACHER-01', 'teacherName' => 'Teacher One'],
            ['teacherID' => 'TEACHER-02', 'teacherName' => 'Teacher Without Attendance'],
        ]);
        DB::table('outsider')->insert(['outsider_id' => 1, 'name' => 'Community Participant']);
        DB::table('enroll_teacher')->insert([
            ['teacherID' => 'TEACHER-01', 'course_id' => 'COURSE-LMS', 'status' => 'approved'],
            ['teacherID' => 'TEACHER-02', 'course_id' => 'COURSE-LMS', 'status' => 'approved'],
        ]);
        DB::table('enroll_outsider')->insert(['outsider_id' => 1, 'course_id' => 'COURSE-SIMPLE', 'status' => 'approved']);
        DB::table('attendance')->insert([
            'teacher_id' => 'TEACHER-01',
            'session_id' => 'SESSION-LMS',
            'attendance_status' => 'approved',
            'timestamp_status' => 1.25,
            'hours_ladap' => 2.00,
        ]);
    }

    private function adminSession(): array
    {
        return ['user_role' => 'admin', 'user_id' => 'STAFF-01', 'user_name' => 'Administrator Test'];
    }

    private function snapshot(): array
    {
        return collect(['course', 'course_session', 'enroll_teacher', 'enroll_guru_baru', 'enroll_outsider', 'attendance'])
            ->mapWithKeys(fn (string $table) => [$table => DB::table($table)->get()->map(fn ($row) => (array) $row)->all()])
            ->all();
    }
}
