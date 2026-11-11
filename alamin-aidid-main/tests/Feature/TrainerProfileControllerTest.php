<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TrainerProfileControllerTest extends TestCase
{
    private string $originalPublicPath;

    private string $testPublicPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());

        Schema::dropIfExists('trainer_document');
        Schema::dropIfExists('trainer_certificate');
        Schema::dropIfExists('course_proposal');
        Schema::dropIfExists('feedback_response');
        Schema::dropIfExists('feedback_question');
        Schema::dropIfExists('feedback_category');
        Schema::dropIfExists('feedback_form');
        Schema::dropIfExists('session_trainer');
        Schema::dropIfExists('course_session');
        Schema::dropIfExists('course');
        Schema::dropIfExists('trainer_activity_log');
        Schema::dropIfExists('trainer');

        Schema::create('trainer', function (Blueprint $table): void {
            $table->string('trainerID', 20)->primary();
            $table->string('trainerName');
            $table->string('trainerEmail');
            $table->string('trainerPhoneNo')->nullable();
            $table->string('trainerIC')->nullable();
            $table->string('expertise')->nullable();
            $table->string('status')->nullable();
            $table->decimal('averageRating', 3, 2)->nullable();
            $table->string('trainerPassword')->nullable();
            $table->string('trainerPic')->nullable();
            $table->string('paymentStatus')->nullable();
        });

        Schema::create('course_session', function (Blueprint $table): void {
            $table->string('sessionID', 20)->primary();
            $table->string('courseID', 20);
        });

        Schema::create('session_trainer', function (Blueprint $table): void {
            $table->string('sessionID', 20);
            $table->string('trainerID', 20);
        });

        Schema::create('course', function (Blueprint $table): void {
            $table->string('courseID', 20)->primary();
            $table->string('courseName');
            $table->string('courseCategory')->nullable();
        });

        Schema::create('feedback_form', function (Blueprint $table): void {
            $table->string('formID', 20)->primary();
            $table->string('title');
            $table->string('sessionID', 20)->nullable();
            $table->string('courseID', 20)->nullable();
            $table->string('feedbackType', 20);
        });

        Schema::create('feedback_category', function (Blueprint $table): void {
            $table->string('categoryID', 20)->primary();
            $table->string('categoryName');
            $table->string('formID', 20);
        });

        Schema::create('feedback_question', function (Blueprint $table): void {
            $table->string('questionID', 20)->primary();
            $table->unsignedInteger('questionOrder');
            $table->string('questionText');
            $table->string('questionType');
            $table->string('categoryID', 20);
        });

        Schema::create('feedback_response', function (Blueprint $table): void {
            $table->string('responseID', 100)->primary();
            $table->decimal('rating', 4, 2)->nullable();
            $table->text('comment')->nullable();
            $table->timestamp('responseDate');
            $table->string('participantID', 100)->nullable();
            $table->string('staffID', 100)->nullable();
            $table->string('questionID', 20);
        });

        Schema::create('course_proposal', function (Blueprint $table): void {
            $table->unsignedInteger('proposal_id')->primary();
            $table->string('trainer_id', 20);
        });

        Schema::create('trainer_certificate', function (Blueprint $table): void {
            $table->increments('cert_id');
            $table->string('trainer_id', 20);
            $table->string('title')->nullable();
            $table->string('file_path');
            $table->dateTime('uploaded_at')->nullable();
        });

        Schema::create('trainer_document', function (Blueprint $table): void {
            $table->increments('document_id');
            $table->string('trainerID', 20);
            $table->string('sentByStaffID', 20);
            $table->string('title');
            $table->string('document_type')->nullable();
            $table->text('description')->nullable();
            $table->string('file_path')->nullable();
            $table->boolean('is_read')->default(false);
            $table->dateTime('created_at')->nullable();
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

        DB::table('trainer')->insert([
            'trainerID' => '9001',
            'trainerName' => 'Trainer Test',
            'trainerEmail' => 'trainer@example.test',
            'trainerPhoneNo' => '0123456789',
            'trainerIC' => '900101-01-1234',
            'expertise' => 'Instructional Design',
            'status' => 'Active',
            'averageRating' => 1.00,
            'trainerPassword' => bcrypt('current-password'),
            'trainerPic' => null,
        ]);

        $this->originalPublicPath = public_path();
        $this->testPublicPath = storage_path('framework/testing/profile-controller-public');
        File::ensureDirectoryExists($this->testPublicPath);
        app()->usePublicPath($this->testPublicPath);

        Storage::fake('public');
    }

    protected function tearDown(): void
    {
        app()->usePublicPath($this->originalPublicPath);

        if (File::isDirectory($this->testPublicPath)) {
            File::deleteDirectory($this->testPublicPath);
        }

        Schema::dropIfExists('trainer_document');
        Schema::dropIfExists('trainer_certificate');
        Schema::dropIfExists('course_proposal');
        Schema::dropIfExists('feedback_response');
        Schema::dropIfExists('feedback_question');
        Schema::dropIfExists('feedback_category');
        Schema::dropIfExists('feedback_form');
        Schema::dropIfExists('session_trainer');
        Schema::dropIfExists('course_session');
        Schema::dropIfExists('course');
        Schema::dropIfExists('trainer_activity_log');
        Schema::dropIfExists('trainer');

        parent::tearDown();
    }

    public function test_trainer_profile_renders_professional_sections_real_summary_and_preserved_resources(): void
    {
        DB::table('course')->insert([
            ['courseID' => '101', 'courseName' => 'Canonical Feedback Training', 'courseCategory' => 'Trainer Development'],
            ['courseID' => '102', 'courseName' => 'Second Assigned Training', 'courseCategory' => 'Trainer Development'],
        ]);
        DB::table('course_session')->insert([
            ['sessionID' => '1', 'courseID' => '101'],
            ['sessionID' => '2', 'courseID' => '101'],
            ['sessionID' => '3', 'courseID' => '102'],
        ]);
        DB::table('session_trainer')->insert([
            ['sessionID' => '1', 'trainerID' => '9001'],
            ['sessionID' => '2', 'trainerID' => '9001'],
            ['sessionID' => '3', 'trainerID' => '9001'],
        ]);
        DB::table('feedback_form')->insert([
            'formID' => 'FORM-101',
            'title' => 'Trainer Feedback',
            'sessionID' => '1',
            'courseID' => '101',
            'feedbackType' => 'participant',
        ]);
        DB::table('feedback_category')->insert([
            'categoryID' => 'CATEGORY-101',
            'categoryName' => 'Trainer Facilitation',
            'formID' => 'FORM-101',
        ]);
        DB::table('feedback_question')->insert([
            'questionID' => 'QUESTION-101',
            'questionOrder' => 1,
            'questionText' => 'How effectively did the trainer facilitate?',
            'questionType' => 'rating',
            'categoryID' => 'CATEGORY-101',
        ]);
        DB::table('feedback_response')->insert([
            ['responseID' => 'RESPONSE-101-A', 'rating' => 4, 'comment' => 'Clear and practical.', 'responseDate' => now(), 'participantID' => 'PARTICIPANT-A', 'questionID' => 'QUESTION-101'],
            ['responseID' => 'RESPONSE-101-B', 'rating' => 5, 'comment' => null, 'responseDate' => now(), 'participantID' => 'PARTICIPANT-B', 'questionID' => 'QUESTION-101'],
        ]);
        DB::table('course_proposal')->insert([
            ['proposal_id' => 11, 'trainer_id' => 9001],
            ['proposal_id' => 12, 'trainer_id' => 9001],
            ['proposal_id' => 13, 'trainer_id' => 9002],
        ]);
        DB::table('trainer_certificate')->insert([
            [
                'trainer_id' => '9001',
                'title' => 'Facilitation Certificate',
                'file_path' => 'trainer_certs/facilitation.pdf',
                'uploaded_at' => '2026-08-20 09:00:00',
            ],
            [
                'trainer_id' => '9001',
                'title' => 'Assessment Certificate',
                'file_path' => 'trainer_certs/assessment.pdf',
                'uploaded_at' => '2026-08-18 09:00:00',
            ],
        ]);
        DB::table('trainer_document')->insert([
            [
                'trainerID' => '9001',
                'sentByStaffID' => 'STAFF1',
                'title' => 'Official Appointment Letter',
                'document_type' => 'Letter',
                'description' => 'Trainer appointment confirmation.',
                'file_path' => 'trainer_documents/appointment.pdf',
                'is_read' => 0,
                'created_at' => '2026-08-25 10:00:00',
            ],
            [
                'trainerID' => '9001',
                'sentByStaffID' => 'STAFF1',
                'title' => 'Document Without File',
                'document_type' => 'Memo',
                'description' => null,
                'file_path' => null,
                'is_read' => 0,
                'created_at' => '2026-08-24 10:00:00',
            ],
        ]);

        $response = $this->withSession($this->trainerSession())->get('/trainer/profile');

        $response
            ->assertOk()
            ->assertViewHas('trainer', fn (object $trainer): bool => ! property_exists($trainer, 'trainerPassword') && ! property_exists($trainer, 'password'))
            ->assertSee('View your trainer account details, manage your certificates, and access official documents.')
            ->assertSee('Trainer Test')
            ->assertSee('trainer@example.test')
            ->assertSee('Trainer Summary')
            ->assertSee('Account Details')
            ->assertSee('Instructional Design')
            ->assertSee('Facilitation Certificate')
            ->assertSee('Official Appointment Letter')
            ->assertDontSee('Document Without File')
            ->assertDontSee('Change Password')
            ->assertDontSee('Update Password')
            ->assertDontSee('Account &amp; Security', false)
            ->assertDontSee('These details are managed by the admin office')
            ->assertDontSee('Account Status');

        $html = $response->getContent();

        $this->assertSame(3, substr_count($html, 'role="tab"'));
        $this->assertStringContainsString('role="tablist"', $html);
        $this->assertStringContainsString('data-panel="profile"', $html);
        $this->assertStringContainsString('data-panel="certificate"', $html);
        $this->assertStringContainsString('data-panel="documents"', $html);
        $this->assertMatchesRegularExpression('~data-trainer-metric="trainings"[\s\S]*?pf-stat-value">2<~', $html);
        $this->assertMatchesRegularExpression('~data-trainer-metric="proposals"[\s\S]*?pf-stat-value">2<~', $html);
        $this->assertMatchesRegularExpression('~data-trainer-metric="certificates"[\s\S]*?pf-stat-value">2<~', $html);
        $this->assertSame(4.5, $response->viewData('liveRating'));
        $this->assertMatchesRegularExpression('~data-trainer-metric="feedback-rating"[\s\S]*?4\.5~', $html);
        $this->assertStringContainsString(route('trainer.profile.certificate'), $html);
        $this->assertStringContainsString(route('trainer.profile.document.view', 1), $html);
        $this->assertStringNotContainsString('pf-avatar', $html);
        $this->assertStringNotContainsString('trainer_pic', $html);
        $this->assertStringNotContainsString('pf-badge', $html);
        $this->assertStringNotContainsString('data-tab="security"', $html);
        $this->assertStringNotContainsString('current_password', $html);
        $this->assertStringNotContainsString('new_password', $html);
    }

    public function test_certificate_upload_and_owned_removal_contract_remains_functional(): void
    {
        $certificate = UploadedFile::fake()->create('qualification.pdf', 120, 'application/pdf');

        $upload = $this->withSession($this->trainerSession())
            ->post('/trainer/profile/certificate', [
                'title' => 'Professional Qualification',
                'certificate' => $certificate,
            ]);

        $upload
            ->assertRedirect(route('trainer.profile', ['tab' => 'certificate']))
            ->assertSessionHas('success', 'Certificate uploaded successfully.');

        $stored = DB::table('trainer_certificate')->where('trainer_id', 9001)->first();
        $this->assertNotNull($stored);
        $this->assertSame('Professional Qualification', $stored->title);
        Storage::disk('public')->assertExists($stored->file_path);
        $this->assertDatabaseHas('trainer_activity_log', [
            'trainer_id' => 9001,
            'action' => 'certificate.upload',
            'detail' => 'Professional Qualification',
            'target_id' => $stored->cert_id,
        ]);

        $remove = $this->withSession($this->trainerSession())
            ->delete('/trainer/profile/certificate/'.$stored->cert_id);

        $remove
            ->assertRedirect(route('trainer.profile', ['tab' => 'certificate']))
            ->assertSessionHas('success', 'Certificate removed.');
        $this->assertDatabaseMissing('trainer_certificate', ['cert_id' => $stored->cert_id]);
        Storage::disk('public')->assertMissing($stored->file_path);
        $this->assertDatabaseHas('trainer_activity_log', [
            'trainer_id' => 9001,
            'action' => 'certificate.remove',
            'detail' => 'Professional Qualification',
            'target_id' => $stored->cert_id,
        ]);
        $this->assertSame(2, DB::table('trainer_activity_log')->count());
    }

    public function test_owned_official_document_view_marks_document_read_and_preserves_redirect(): void
    {
        $documentId = DB::table('trainer_document')->insertGetId([
            'trainerID' => '9001',
            'sentByStaffID' => 'STAFF1',
            'title' => 'Official Memo',
            'document_type' => 'Memo',
            'description' => 'An official trainer memo.',
            'file_path' => 'trainer_documents/memo.pdf',
            'is_read' => 0,
            'created_at' => '2026-08-25 10:00:00',
        ]);

        $response = $this->withSession($this->trainerSession())
            ->get('/trainer/profile/document/'.$documentId.'/view');

        $response->assertRedirect(asset('storage/trainer_documents/memo.pdf'));
        $this->assertSame(1, (int) DB::table('trainer_document')->where('document_id', $documentId)->value('is_read'));
        $this->assertSame(0, DB::table('trainer_activity_log')->count());
    }

    public function test_trainer_can_upload_a_profile_picture_with_the_existing_path_contract(): void
    {
        $png = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Y9ZQmcAAAAASUVORK5CYII='
        );
        $picture = UploadedFile::fake()->createWithContent('avatar.png', $png);

        $response = $this->withSession($this->trainerSession())
            ->post('/trainer/profile/picture', ['trainer_pic' => $picture]);

        $response
            ->assertRedirect(route('trainer.profile', ['tab' => 'profile']))
            ->assertSessionHas('success');

        $storedPath = DB::table('trainer')->where('trainerID', '9001')->value('trainerPic');

        $this->assertIsString($storedPath);
        $this->assertStringStartsWith('trainer_pics/trainer_9001_', $storedPath);
        $this->assertFileExists($this->testPublicPath.DIRECTORY_SEPARATOR.$storedPath);
        $this->assertSame(
            1,
            DB::table('trainer_activity_log')->where('action', 'profile.update')->count()
        );
    }

    public function test_password_change_requires_a_canonical_password_hash(): void
    {
        DB::table('trainer')->where('trainerID', '9001')->update([
            'trainerPassword' => 'current-password',
        ]);

        $response = $this->withSession($this->trainerSession())
            ->from('/trainer/profile')
            ->post('/trainer/profile/password', [
                'current_password' => 'current-password',
                'new_password' => 'new-secure-password',
                'new_password_confirmation' => 'new-secure-password',
            ]);

        $response
            ->assertRedirect('/trainer/profile')
            ->assertSessionHasErrors('current_password');
        $this->assertSame(
            'current-password',
            DB::table('trainer')->where('trainerID', '9001')->value('trainerPassword')
        );
    }

    public function test_trainer_can_remove_an_owned_profile_picture_reference(): void
    {
        Storage::disk('public')->put('trainer_pics/existing.png', 'picture');
        DB::table('trainer')->where('trainerID', '9001')->update([
            'trainerPic' => 'trainer_pics/existing.png',
        ]);

        $response = $this->withSession($this->trainerSession())
            ->delete('/trainer/profile/picture');

        $response
            ->assertRedirect(route('trainer.profile'))
            ->assertSessionHas('success');
        $this->assertNull(
            DB::table('trainer')->where('trainerID', '9001')->value('trainerPic')
        );
        Storage::disk('public')->assertMissing('trainer_pics/existing.png');
        $this->assertDatabaseHas('trainer_activity_log', [
            'trainer_id' => 9001,
            'action' => 'profile.update',
            'detail' => 'Profile photo removed',
        ]);
    }

    private function trainerSession(): array
    {
        return [
            'user_id' => 9001,
            'user_role' => 'trainer',
            'user_name' => 'Trainer Test',
        ];
    }
}
