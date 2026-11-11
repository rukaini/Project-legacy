<?php

namespace Tests\Feature;

use App\Support\ExternalParticipantIdentity;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminDirectoryControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        Storage::fake('public');

        DB::connection()->getPdo()->sqliteCreateFunction(
            'CURDATE',
            fn (): string => now()->toDateString(),
        );

        Schema::create('trainer', function (Blueprint $table): void {
            $table->string('trainerID', 20)->primary();
            $table->string('trainerName');
            $table->string('trainerIC', 20)->nullable();
            $table->string('trainerEmail')->nullable();
            $table->string('trainerPhoneNo')->nullable();
            $table->string('trainerPic')->nullable();
            $table->string('trainerPassword')->default('synthetic-only');
            $table->decimal('averageRating', 3, 2)->default(0);
            $table->string('expertise')->nullable();
            $table->string('status')->nullable();
            $table->string('paymentStatus')->nullable();
        });
        Schema::create('trainer_certificate', function (Blueprint $table): void {
            $table->increments('cert_id');
            $table->string('trainer_id', 20);
            $table->string('title')->nullable();
            $table->string('file_path')->nullable();
            $table->dateTime('uploaded_at')->nullable();
        });
        Schema::create('course', function (Blueprint $table): void {
            $table->string('courseID', 20)->primary();
            $table->string('courseName');
            $table->string('courseCategory')->nullable();
            $table->string('mode')->nullable();
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
            $table->string('teacherName', 250)->nullable();
            $table->string('ICNumber', 20)->nullable()->unique();
            $table->string('phoneNumber', 20)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('maritalStatus', 20)->nullable();
            $table->string('gender', 10)->nullable();
            $table->string('address', 255)->nullable();
            $table->string('race', 50)->nullable();
            $table->date('appointedDate')->nullable();
            $table->integer('serviceDate')->nullable();
            $table->date('pensionDate')->nullable();
            $table->integer('latestAge')->nullable();
            $table->string('password', 255);
            $table->boolean('password_change_required')->default(true);
            $table->string('role', 20)->nullable()->default('teacher');
            $table->date('resignation_request_date')->nullable();
            $table->text('resignation_request_reason')->nullable();
            $table->string('resignation_request_status', 20)->nullable();
        });
        Schema::create('guru_new', function (Blueprint $table): void {
            $table->string('gn_id', 20)->primary();
            $table->string('ic_number', 20)->unique();
            $table->string('gn_name', 255);
            $table->string('phone_number', 20);
            $table->string('email', 100)->unique();
            $table->string('marital_status')->nullable();
            $table->string('gender', 10)->nullable();
            $table->string('address', 255)->nullable();
            $table->string('race', 50)->nullable();
            $table->date('appointed_date');
            $table->string('role', 20)->nullable()->default('new_teacher');
            $table->string('current_status')->default('Inactive');
            $table->string('hrID', 20);
            $table->string('schoolID', 50);
            $table->string('password', 255);
        });
        Schema::create('outsider', function (Blueprint $table): void {
            $table->unsignedInteger('outsider_id')->primary();
            $table->string('name');
        });

        DB::table('trainer')->insert([
            'trainerID' => 'TR9001',
            'trainerName' => 'Trainer Directory',
            'expertise' => 'Inclusive education',
            'status' => 'Active',
        ]);
        DB::table('course')->insert([
            'courseID' => 'C501',
            'courseName' => 'Inclusive Classroom Practice',
            'courseCategory' => 'Teacher Training',
            'mode' => 'hybrid',
            'courseType' => 'LMS',
        ]);
        DB::table('course_session')->insert([
            'sessionID' => 'S601',
            'courseID' => 'C501',
            'sessionName' => 'Practical Strategies',
            'sessionDate' => now()->toDateString(),
        ]);
        DB::table('session_trainer')->insert([
            'sessionID' => 'S601',
            'trainerID' => 'TR9001',
        ]);
        DB::table('teacher')->insert([
            'teacherID' => 'TEACHER-A7',
            'teacherName' => 'Teacher Directory',
            'password' => 'synthetic-only',
        ]);
        DB::table('enroll_teacher')->insert([
            'course_id' => 'C501',
            'teacherID' => 'TEACHER-A7',
        ]);
        DB::table('outsider')->insert([
            'outsider_id' => 802,
            'name' => 'Community Participant',
        ]);
        DB::table('enroll_outsider')->insert([
            'course_id' => 'C501',
            'outsider_id' => 802,
        ]);
        DB::table('guru_new')->insert([
            [
                'gn_id' => 'GN0001',
                'ic_number' => 'SYNTHETIC-IC-001',
                'gn_name' => 'New Teacher Directory',
                'phone_number' => '0000000000',
                'email' => 'new-teacher@example.test',
                'appointed_date' => '2026-01-01',
                'current_status' => 'Active',
                'hrID' => 'HR-SYNTHETIC',
                'schoolID' => 'SCHOOL-SYNTHETIC',
                'password' => 'synthetic-only',
            ],
            [
                'gn_id' => 'GN0002',
                'ic_number' => 'SYNTHETIC-IC-002',
                'gn_name' => 'Inactive New Teacher',
                'phone_number' => '0000000001',
                'email' => 'inactive-new-teacher@example.test',
                'appointed_date' => '2026-01-02',
                'current_status' => 'Inactive',
                'hrID' => 'HR-SYNTHETIC',
                'schoolID' => 'SCHOOL-SYNTHETIC',
                'password' => 'synthetic-only',
            ],
            [
                'gn_id' => 'GN0003',
                'ic_number' => 'SYNTHETIC-IC-003',
                'gn_name' => 'Complete New Teacher',
                'phone_number' => '0000000002',
                'email' => 'complete-new-teacher@example.test',
                'appointed_date' => '2026-01-03',
                'current_status' => 'Complete',
                'hrID' => 'HR-SYNTHETIC',
                'schoolID' => 'SCHOOL-SYNTHETIC',
                'password' => 'synthetic-only',
            ],
        ]);
        DB::table('enroll_guru_baru')->insert([
            'course_id' => 'C501',
            'gn_id' => 'GN0001',
        ]);
    }

    protected function tearDown(): void
    {
        foreach ([
            'enroll_outsider',
            'enroll_guru_baru',
            'enroll_teacher',
            'session_trainer',
            'course_session',
            'trainer_certificate',
            'outsider',
            'guru_new',
            'teacher',
            'course',
            'trainer',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    public function test_directory_renders_shared_records_without_writing_them(): void
    {
        $this->assertSame('teacherID', ExternalParticipantIdentity::teacherIdColumn());
        $this->assertSame('teacherName', ExternalParticipantIdentity::teacherNameColumn());
        $this->assertSame('gn_name', ExternalParticipantIdentity::newTeacherNameColumn());

        $response = $this->withSession($this->adminSession())
            ->get('/admin/directory');

        $response
            ->assertOk()
            ->assertSee('Trainer Directory')
            ->assertSee('Teacher Directory')
            ->assertSee('Community Participant')
            ->assertSee('Public')
            ->assertSee('New Teacher Directory')
            ->assertSee('Inactive New Teacher')
            ->assertSee('Complete New Teacher')
            ->assertSee('New Teacher')
            ->assertSee('Inclusive Classroom Practice')
            ->assertSee('Practical Strategies')
            ->assertSee('Hybrid')
            ->assertSee('Session Schedule')
            ->assertSee('elapsed / total by session date')
            ->assertDontSee('synthetic-only');

        $publicParticipant = $response->viewData('participants')->firstWhere('name', 'Community Participant');
        $this->assertSame('Public', $publicParticipant->type);
        $this->assertSame('Outsider', $publicParticipant->type_value);
        $newTeacher = $response->viewData('participants')->firstWhere('name', 'New Teacher Directory');
        $this->assertSame('New Teacher', $newTeacher->type);
        $this->assertSame('Guru Baru', $newTeacher->type_value);
        $this->assertSame(['C501'], $newTeacher->course_ids);
        $this->assertSame('Active', $newTeacher->details['Status']);
        $this->assertArrayNotHasKey('Password', $newTeacher->details);
        $this->assertSame('Inactive', $response->viewData('participants')->firstWhere('name', 'Inactive New Teacher')->details['Status']);
        $this->assertSame('Complete', $response->viewData('participants')->firstWhere('name', 'Complete New Teacher')->details['Status']);
        $teacher = $response->viewData('participants')->firstWhere('name', 'Teacher Directory');
        $this->assertSame(['C501'], $teacher->course_ids);
        $this->assertArrayNotHasKey('Password', $teacher->details);
        $training = $response->viewData('courses')->firstWhere('course_id', 'C501');
        $this->assertSame(['Trainer Directory'], $training->trainer_names);
        $this->assertFalse(property_exists($training, 'progress'));
        $session = $response->viewData('sessions')->firstWhere('session_id', 'S601');
        $this->assertSame('Hybrid', $session->session_type);

        $this->assertSame(1, DB::table('trainer')->count());
        $this->assertSame(1, DB::table('teacher')->count());
        $this->assertSame(1, DB::table('course')->count());
        $this->assertSame(3, DB::table('enroll_teacher')->count()
            + DB::table('enroll_guru_baru')->count()
            + DB::table('enroll_outsider')->count());
    }

    public function test_trainer_details_expose_only_their_existing_certificate_metadata(): void
    {
        DB::table('trainer')->insert([
            'trainerID' => 'TR9002',
            'trainerName' => 'Trainer Without Certificates',
            'expertise' => 'Coaching',
            'status' => 'Active',
        ]);
        DB::table('trainer')->insert([
            'trainerID' => 'TR9003',
            'trainerName' => 'Trainer With Different Certificate',
            'expertise' => 'Mentoring',
            'status' => 'Active',
        ]);
        Storage::disk('public')->put('trainer_certs/directory-certificate.pdf', 'certificate contents');
        Storage::disk('public')->put('trainer_certs/different-certificate.pdf', 'different certificate contents');
        DB::table('trainer_certificate')->insert([
            [
                'cert_id' => 41,
                'trainer_id' => 'TR9001',
                'title' => 'Inclusive Practice Certificate',
                'file_path' => 'trainer_certs/directory-certificate.pdf',
                'uploaded_at' => '2026-08-20 09:00:00',
            ],
            [
                'cert_id' => 42,
                'trainer_id' => 'TR9001',
                'title' => null,
                'file_path' => 'trainer_certs/missing.pdf',
                'uploaded_at' => null,
            ],
            [
                'cert_id' => 43,
                'trainer_id' => 'TR9003',
                'title' => 'Different Trainer Certificate',
                'file_path' => 'trainer_certs/different-certificate.pdf',
                'uploaded_at' => '2026-08-19 09:00:00',
            ],
        ]);

        $response = $this->withSession($this->adminSession())->get('/admin/directory');

        $response
            ->assertOk()
            ->assertSee('data-d-certificates=', false)
            ->assertSee('id="vmCertificatesSection"', false)
            ->assertSee('No certificates uploaded.');

        $trainers = $response->viewData('trainers');
        $selectedTrainer = $trainers->firstWhere('trainer_id', 'TR9001');
        $emptyTrainer = $trainers->firstWhere('trainer_id', 'TR9002');
        $differentTrainer = $trainers->firstWhere('trainer_id', 'TR9003');

        $this->assertCount(2, $selectedTrainer->certificates);
        $this->assertSame([41, 42], $selectedTrainer->certificates->pluck('id')->all());
        $this->assertSame('Inclusive Practice Certificate', $selectedTrainer->certificates[0]['title']);
        $this->assertSame('PDF', $selectedTrainer->certificates[0]['file_type']);
        $this->assertSame('20 Aug 2026', $selectedTrainer->certificates[0]['uploaded_at']);
        $this->assertTrue($selectedTrainer->certificates[0]['available']);
        $this->assertSame(
            route('admin.directory.trainer.certificate.view', ['trainerId' => 'TR9001', 'certId' => 41]),
            $selectedTrainer->certificates[0]['view_url'],
        );
        $this->assertSame('Certificate', $selectedTrainer->certificates[1]['title']);
        $this->assertFalse($selectedTrainer->certificates[1]['available']);
        $this->assertNull($selectedTrainer->certificates[1]['view_url']);
        $this->assertTrue($emptyTrainer->certificates->isEmpty());
        $this->assertSame([43], $differentTrainer->certificates->pluck('id')->all());
        $this->assertNotContains(43, $selectedTrainer->certificates->pluck('id')->all());
    }

    public function test_admin_can_view_only_an_existing_certificate_owned_by_the_selected_trainer(): void
    {
        DB::table('trainer')->insert([
            'trainerID' => 'TR9002',
            'trainerName' => 'Other Trainer',
            'expertise' => null,
            'status' => 'Active',
        ]);
        Storage::disk('public')->put('trainer_certs/owned.pdf', 'owned certificate');
        Storage::disk('public')->put('unrelated/private.pdf', 'unrelated file');
        DB::table('trainer_certificate')->insert([
            [
                'cert_id' => 51,
                'trainer_id' => 'TR9001',
                'title' => 'Owned Certificate',
                'file_path' => 'trainer_certs/owned.pdf',
                'uploaded_at' => now(),
            ],
            [
                'cert_id' => 52,
                'trainer_id' => 'TR9001',
                'title' => 'Unsafe Path',
                'file_path' => 'unrelated/private.pdf',
                'uploaded_at' => now(),
            ],
            [
                'cert_id' => 53,
                'trainer_id' => 'TR9001',
                'title' => 'Missing File',
                'file_path' => 'trainer_certs/missing.pdf',
                'uploaded_at' => now(),
            ],
        ]);

        $authorizedUrl = route('admin.directory.trainer.certificate.view', ['trainerId' => 'TR9001', 'certId' => 51]);

        $this->withSession($this->adminSession())
            ->get($authorizedUrl)
            ->assertOk()
            ->assertHeader('content-disposition', 'inline; filename=owned.pdf');

        $this->withSession($this->adminSession())
            ->get(route('admin.directory.trainer.certificate.view', ['trainerId' => 'TR9002', 'certId' => 51]))
            ->assertNotFound();
        $this->withSession($this->adminSession())
            ->get(route('admin.directory.trainer.certificate.view', ['trainerId' => 9999, 'certId' => 51]))
            ->assertNotFound();
        $this->withSession($this->adminSession())
            ->get(route('admin.directory.trainer.certificate.view', ['trainerId' => 'TR9001', 'certId' => 52]))
            ->assertNotFound();
        $this->withSession($this->adminSession())
            ->get(route('admin.directory.trainer.certificate.view', ['trainerId' => 'TR9001', 'certId' => 53]))
            ->assertNotFound();
        $this->withSession($this->adminSession())
            ->delete($authorizedUrl)
            ->assertStatus(405);
        $this->withSession(['user_id' => 'TR9001', 'user_role' => 'trainer'])
            ->get($authorizedUrl)
            ->assertRedirect(route('login'));

        $this->assertSame(3, DB::table('trainer_certificate')->count());
        Storage::disk('public')->assertExists('trainer_certs/owned.pdf');
    }

    private function adminSession(): array
    {
        return [
            'user_id' => 7001,
            'user_role' => 'admin',
            'user_name' => 'Administrator Test',
        ];
    }
}
