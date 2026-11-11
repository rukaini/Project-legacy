<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminProfileControllerTest extends TestCase
{
    private string $originalPublicPath;

    private string $testPublicPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());

        Schema::dropIfExists('course_proposal');
        Schema::dropIfExists('trainer');
        Schema::dropIfExists('staff_edu');
        Schema::create('staff_edu', function (Blueprint $table): void {
            $table->string('staffID', 20)->primary();
            $table->string('staffName');
            $table->string('email');
            $table->string('ICNumber')->nullable();
            $table->string('phoneNumber')->nullable();
            $table->string('maritalStatus')->nullable();
            $table->string('gender')->nullable();
            $table->string('address')->nullable();
            $table->string('race')->nullable();
            $table->date('appointedDate')->nullable();
            $table->string('serviceDate')->nullable();
            $table->date('pensionDate')->nullable();
            $table->unsignedInteger('latestAge')->nullable();
            $table->string('role');
            $table->string('password');
            $table->decimal('credit_hour', 5, 2)->nullable();
            $table->string('department')->nullable();
            $table->string('status')->nullable();
        });

        Schema::create('trainer', function (Blueprint $table): void {
            $table->string('trainerID', 20)->primary();
            $table->string('trainerName');
        });

        Schema::create('course_proposal', function (Blueprint $table): void {
            $table->unsignedInteger('proposal_id')->primary();
            $table->string('trainer_id', 20)->nullable();
            $table->string('proposed_title')->nullable();
            $table->string('status');
            $table->string('reviewed_by', 20)->nullable();
            $table->dateTime('reviewed_at')->nullable();
        });

        DB::table('staff_edu')->insert([
            'staffID' => 'STAFF7001',
            'staffName' => 'Administrator Test',
            'email' => 'administrator@example.test',
            'role' => 'Admin',
            'password' => Hash::make('current-password'),
            'appointedDate' => '2020-01-15',
            'pensionDate' => '2045-01-15',
            'latestAge' => 41,
            'credit_hour' => 120,
        ]);

        DB::table('trainer')->insert([
            'trainerID' => 'TR8001',
            'trainerName' => 'Trainer Example',
        ]);

        $this->originalPublicPath = public_path();
        $this->testPublicPath = storage_path('framework/testing/admin-profile-controller-public');
        File::ensureDirectoryExists($this->testPublicPath);
        app()->usePublicPath($this->testPublicPath);
    }

    protected function tearDown(): void
    {
        app()->usePublicPath($this->originalPublicPath);

        if (File::isDirectory($this->testPublicPath)) {
            File::deleteDirectory($this->testPublicPath);
        }

        Carbon::setTestNow();
        Schema::dropIfExists('course_proposal');
        Schema::dropIfExists('trainer');
        Schema::dropIfExists('staff_edu');

        parent::tearDown();
    }

    public function test_profile_renders_real_identity_account_details_and_review_summary_without_removed_profile_ui(): void
    {
        Carbon::setTestNow('2026-08-29 12:00:00');

        $this->insertProposal(101, 'Oldest Approved', 'Approved', '2026-08-27 09:00:00');
        $this->insertProposal(102, 'Rejected Review', 'Rejected', '2026-08-28 10:00:00');
        $this->insertProposal(103, 'Latest Approved', 'Approved', '2026-08-29 11:00:00');
        $this->insertProposal(104, 'Unfinished Proposal', 'Pending', '2026-08-29 11:30:00');
        $this->insertProposal(105, 'Another Administrator Review', 'Approved', '2026-08-29 11:45:00', 'STAFF7002');

        $response = $this->withSession($this->adminSession())->get('/admin/profile');

        $response
            ->assertOk()
            ->assertViewHas('staff', fn (object $staff): bool => ! property_exists($staff, 'password'))
            ->assertSee('Administrator Test')
            ->assertSee('administrator@example.test')
            ->assertSee('Admin')
            ->assertSee('System Role')
            ->assertSee('15 Jan 2020')
            ->assertSee('41')
            ->assertSee('120')
            ->assertSee('1 hour ago')
            ->assertSee('29 Aug 2026, 11:00 AM')
            ->assertSeeInOrder(['Latest Approved', 'Rejected Review', 'Oldest Approved'])
            ->assertDontSee('Unfinished Proposal')
            ->assertDontSee('Another Administrator Review')
            ->assertDontSee('Profile picture')
            ->assertDontSee('Change Password')
            ->assertDontSee('Update Password')
            ->assertDontSee('Account &amp; Security', false)
            ->assertDontSee('Administrator account')
            ->assertDontSee('Active account')
            ->assertDontSee('Account Status')
            ->assertDontSee('These details are managed by HR or the system')
            ->assertDontSee('Latest review');

        $html = $response->getContent();

        $this->assertStringContainsString('data-review-metric="reviewed"', $html);
        $this->assertStringContainsString('data-review-metric="approved"', $html);
        $this->assertStringContainsString('data-review-metric="rejected"', $html);
        $this->assertStringContainsString('data-review-metric="approval-rate"', $html);
        $this->assertMatchesRegularExpression('~data-review-metric="reviewed"[\s\S]*?pf-stat-value">3<~', $html);
        $this->assertMatchesRegularExpression('~data-review-metric="approved"[\s\S]*?pf-stat-value">2<~', $html);
        $this->assertMatchesRegularExpression('~data-review-metric="rejected"[\s\S]*?pf-stat-value">1<~', $html);
        $this->assertMatchesRegularExpression('~data-review-metric="approval-rate"[\s\S]*?pf-stat-value">67%<~', $html);
        $this->assertStringNotContainsString('pf-avatar', $html);
        $this->assertStringNotContainsString('pfCamBtn', $html);
        $this->assertStringNotContainsString('staff_pic', $html);
        $this->assertStringNotContainsString('pf-tab-', $html);
        $this->assertStringNotContainsString('current_password', $html);
        $this->assertStringNotContainsString('new_password', $html);
        $this->assertStringNotContainsString('pf-managed-note', $html);
        $this->assertStringNotContainsString('pf-last-review', $html);
        $this->assertStringNotContainsString('pf-badge', $html);
    }

    public function test_profile_handles_zero_reviews_and_missing_optional_account_dates_safely(): void
    {
        DB::table('staff_edu')->where('staffID', 'STAFF7001')->update([
            'appointedDate' => null,
            'pensionDate' => null,
            'latestAge' => null,
            'credit_hour' => null,
        ]);

        $response = $this->withSession($this->adminSession())->get('/admin/profile');

        $response
            ->assertOk()
            ->assertSee('No proposal reviews yet')
            ->assertSee('Not available')
            ->assertDontSee('NaN%')
            ->assertDontSee('ago</', false);

        $html = $response->getContent();

        $this->assertMatchesRegularExpression('~data-review-metric="reviewed"[\s\S]*?pf-stat-value">0<~', $html);
        $this->assertMatchesRegularExpression('~data-review-metric="approved"[\s\S]*?pf-stat-value">0<~', $html);
        $this->assertMatchesRegularExpression('~data-review-metric="rejected"[\s\S]*?pf-stat-value">0<~', $html);
        $this->assertMatchesRegularExpression('~data-review-metric="approval-rate"[\s\S]*?pf-stat-value">Not available<~', $html);
    }

    public function test_profile_activity_keeps_only_the_latest_ten_reviews_in_newest_first_order(): void
    {
        Carbon::setTestNow('2026-08-29 12:00:00');

        foreach (range(1, 11) as $index) {
            $this->insertProposal(
                200 + $index,
                sprintf('Review %02d', $index),
                $index % 2 === 0 ? 'Approved' : 'Rejected',
                Carbon::parse('2026-08-01 09:00:00')->addDays($index)->format('Y-m-d H:i:s'),
            );
        }

        $response = $this->withSession($this->adminSession())->get('/admin/profile');

        $response
            ->assertOk()
            ->assertSeeInOrder(['Review 11', 'Review 10', 'Review 09'])
            ->assertDontSee('Review 01');

        $this->assertSame(10, substr_count($response->getContent(), 'class="pf-activity-row"'));
    }

    public function test_administrator_can_change_password_with_existing_validation_and_redirects(): void
    {
        $response = $this->withSession($this->adminSession())
            ->post('/admin/profile/password', [
                'current_password' => 'current-password',
                'new_password' => 'new-secure-password',
                'new_password_confirmation' => 'new-secure-password',
            ]);

        $response
            ->assertRedirect(route('admin.profile', ['tab' => 'security']))
            ->assertSessionHas('success', 'Password changed successfully.');

        $hash = DB::table('staff_edu')->where('staffID', 'STAFF7001')->value('password');
        $this->assertTrue(Hash::check('new-secure-password', $hash));
    }

    public function test_profile_picture_writes_are_delegated_to_the_staff_owner(): void
    {
        $png = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Y9ZQmcAAAAASUVORK5CYII='
        );
        $picture = UploadedFile::fake()->createWithContent('avatar.png', $png);

        $upload = $this->withSession($this->adminSession())
            ->post('/admin/profile/picture', ['staff_pic' => $picture]);

        $upload->assertRedirect(route('admin.profile'))
            ->assertSessionHas('error', 'Staff profile pictures are managed by the Staff EDU owner.');

        $remove = $this->withSession($this->adminSession())
            ->post('/admin/profile/picture/remove');

        $remove->assertRedirect(route('admin.profile'))
            ->assertSessionHas('error', 'Staff profile pictures are managed by the Staff EDU owner.');
    }

    private function adminSession(): array
    {
        return [
            'user_id' => 'STAFF7001',
            'user_role' => 'admin',
            'user_name' => 'Administrator Test',
        ];
    }

    private function insertProposal(
        int $proposalId,
        string $title,
        string $status,
        ?string $reviewedAt,
        string $reviewedBy = 'STAFF7001',
    ): void {
        DB::table('course_proposal')->insert([
            'proposal_id' => $proposalId,
            'trainer_id' => 'TR8001',
            'proposed_title' => $title,
            'status' => $status,
            'reviewed_by' => $reviewedBy,
            'reviewed_at' => $reviewedAt,
        ]);
    }
}
