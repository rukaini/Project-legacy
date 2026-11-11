<?php

namespace Tests\Feature;

use App\Mail\AdministratorPasswordResetMail;
use App\Services\AdministratorPasswordResetService;
use App\Services\CanonicalIdentityService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdministratorWebPasswordRecoveryTest extends TestCase
{
    private string $email = 'protected.admin@example.test';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'sso.administrator.email' => $this->email,
            'sso.administrator.reset_cache_store' => 'array',
            'sso.administrator.reset_ttl_seconds' => 900,
        ]);

        Schema::create('staff_edu', function (Blueprint $table): void {
            $table->string('staffID')->primary();
            $table->string('staffName');
            $table->string('ICNumber')->nullable();
            $table->string('email');
            $table->string('password');
            $table->string('status')->nullable();
        });
        Schema::create('trainer', function (Blueprint $table): void {
            $table->string('trainerID')->primary();
            $table->string('trainerName');
            $table->string('trainerIC')->nullable();
            $table->string('trainerEmail');
            $table->string('trainerPassword');
            $table->string('status')->nullable();
        });
        Schema::create('teacher', function (Blueprint $table): void {
            $table->string('teacherID')->primary();
            $table->string('teacherName');
            $table->string('ICNumber')->nullable();
            $table->string('email');
            $table->string('password');
            $table->string('role');
        });
        Schema::create('assign', function (Blueprint $table): void {
            $table->string('schoolID');
            $table->string('teacherID');
            $table->date('assignDate')->nullable();
            $table->string('status')->nullable();
            $table->primary(['schoolID', 'teacherID']);
        });
        Schema::create('observer', function (Blueprint $table): void {
            $table->string('observerID')->primary();
            $table->string('teacherID');
            $table->string('status');
        });
        Schema::create('external_observer', function (Blueprint $table): void {
            $table->string('externalObserverID')->primary();
            $table->string('teacherID');
            $table->string('status');
        });
        Schema::create('guru_new', function (Blueprint $table): void {
            $table->string('gn_id')->primary();
            $table->string('gn_name');
            $table->string('ic_number')->nullable();
            $table->string('email');
            $table->string('password');
            $table->string('role');
            $table->string('current_status')->nullable();
        });
        Schema::create('principal', function (Blueprint $table): void {
            $table->string('principalID')->primary();
            $table->string('principalName');
            $table->string('ICNumber')->nullable();
            $table->string('email');
            $table->string('password');
            $table->string('role');
            $table->string('status')->nullable();
        });
        Schema::create('hr_administrator', function (Blueprint $table): void {
            $table->string('hrid')->primary();
            $table->string('username');
            $table->string('email');
            $table->string('password');
            $table->string('role');
        });
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('password');
        });
        Schema::create('outsider', function (Blueprint $table): void {
            $table->id('outsider_id');
            $table->unsignedBigInteger('user_id');
            $table->string('name')->nullable();
            $table->string('ICNumber')->nullable();
        });
        Schema::create('roles', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
        });
        Schema::create('model_has_roles', function (Blueprint $table): void {
            $table->unsignedBigInteger('role_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
        });
        DB::table('roles')->insert(['id' => 1, 'name' => 'outsider', 'guard_name' => 'web']);

        $this->insertAdministrator();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_email_request_sends_secure_link_and_changes_zero_credentials(): void
    {
        Mail::fake();
        $before = $this->hashes();

        $response = $this->post('/forgot-password', ['identifier' => ' PROTECTED.ADMIN@EXAMPLE.TEST '])
            ->assertOk()
            ->assertSee('secure password reset link has been sent')
            ->assertDontSee('data-generated-password', false);

        $token = $this->sentToken();
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $token);
        $this->assertSame($before, $this->hashes());
        $this->assertStringNotContainsString($token, $response->getContent());
        Mail::assertSent(AdministratorPasswordResetMail::class, fn (AdministratorPasswordResetMail $mail): bool => str_starts_with(
            $mail->resetUrl,
            rtrim((string) config('app.url'), '/').'/administrator/reset-password/',
        ));

        $reset = app(AdministratorPasswordResetService::class);
        $payload = Cache::store('array')->get('administrator-password-reset:'.$reset->digest($token));
        $this->assertIsArray($payload);
        $this->assertSame($this->email, $payload['email']);
        $this->assertStringNotContainsString($token, json_encode($payload, JSON_THROW_ON_ERROR));
        $this->assertNull(Cache::store('array')->get('administrator-password-reset:'.$token));
    }

    public function test_administrator_ic_gives_email_guidance_and_changes_zero_credentials(): void
    {
        Mail::fake();
        $before = $this->hashes();

        $this->post('/forgot-password', ['identifier' => 'shared-admin-ic'])
            ->assertUnprocessable()
            ->assertSee('enter the registered email address')
            ->assertDontSee('data-generated-password', false);

        Mail::assertNothingSent();
        $this->assertSame($before, $this->hashes());
    }

    public function test_reset_page_requires_confirmation_and_twelve_characters(): void
    {
        Mail::fake();
        $this->post('/forgot-password', ['identifier' => $this->email])->assertOk();
        $token = $this->sentToken();
        $before = $this->hashes();

        $this->get(route('password.admin.reset', ['token' => $token]))
            ->assertOk()
            ->assertSee('New Password')
            ->assertSee('Confirm New Password')
            ->assertSee('minlength="12"', false)
            ->assertSee('images/al-amin-edu-oasis-logo.jpg', false);

        $this->from(route('password.admin.reset', ['token' => $token]))
            ->post(route('password.admin.update', ['token' => $token]), [
                'password' => 'short',
                'password_confirmation' => 'different',
            ])
            ->assertSessionHasErrors('password');

        $this->assertTrue(app(AdministratorPasswordResetService::class)->valid($token));
        $this->assertSame($before, $this->hashes());
    }

    public function test_successful_reset_synchronizes_targets_and_token_is_single_use(): void
    {
        Mail::fake();
        $this->post('/forgot-password', ['identifier' => $this->email])->assertOk();
        $token = $this->sentToken();
        $newPassword = 'A-Secure-New-Password-8642';

        $this->post(route('password.admin.update', ['token' => $token]), [
            'password' => $newPassword,
            'password_confirmation' => $newPassword,
        ])->assertRedirect('/login')
            ->assertSessionHas('status');

        [$staffHash, $trainerHash] = $this->hashes();
        $this->assertTrue(Hash::check($newPassword, $staffHash));
        $this->assertTrue(Hash::check($newPassword, $trainerHash));
        $this->assertFalse(Hash::check('old-staff-password', $staffHash));
        $this->assertFalse(Hash::check('old-trainer-password', $trainerHash));
        $this->assertNotSame($staffHash, $trainerHash);

        $beforeReuse = $this->hashes();
        $this->post(route('password.admin.update', ['token' => $token]), [
            'password' => 'Another-Secure-Password-8642',
            'password_confirmation' => 'Another-Secure-Password-8642',
        ])->assertStatus(410);
        $this->assertSame($beforeReuse, $this->hashes());
    }

    public function test_successful_reset_covers_all_credential_sources_and_linked_roles(): void
    {
        $this->insertRemainingAdministratorRoles();
        Mail::fake();
        $this->post('/forgot-password', ['identifier' => $this->email])->assertOk();
        $token = $this->sentToken();
        $newPassword = 'Complete-Administrator-Password-8642';

        $this->post(route('password.admin.update', ['token' => $token]), [
            'password' => $newPassword,
            'password_confirmation' => $newPassword,
        ])->assertRedirect('/login');

        $passwords = [
            DB::table('staff_edu')->value('password'),
            DB::table('teacher')->value('password'),
            DB::table('guru_new')->value('password'),
            DB::table('principal')->value('password'),
            DB::table('hr_administrator')->value('password'),
            DB::table('trainer')->value('trainerPassword'),
            DB::table('users')->value('password'),
        ];
        foreach ($passwords as $stored) {
            $this->assertTrue(Hash::check($newPassword, (string) $stored));
            $this->assertFalse(Hash::check('old-role-password', (string) $stored));
        }

        $identities = app(CanonicalIdentityService::class);
        $this->assertSame([], $identities->authenticate($this->email, 'old-role-password'));
        $this->assertSame([
            'staff_edu',
            'teacher',
            'observer',
            'external_observer',
            'new_teacher',
            'principal',
            'hr',
            'trainer',
            'outsider',
        ], array_keys($identities->authenticate($this->email, $newPassword)));
    }

    public function test_invalid_and_expired_tokens_change_nothing(): void
    {
        $before = $this->hashes();
        $invalid = str_repeat('b', 64);
        $this->post(route('password.admin.update', ['token' => $invalid]), [
            'password' => 'Invalid-Token-Password-8642',
            'password_confirmation' => 'Invalid-Token-Password-8642',
        ])->assertStatus(410);

        Mail::fake();
        $this->post('/forgot-password', ['identifier' => $this->email])->assertOk();
        $token = $this->sentToken();
        Carbon::setTestNow(now()->addMinutes(16));
        $this->post(route('password.admin.update', ['token' => $token]), [
            'password' => 'Expired-Token-Password-8642',
            'password_confirmation' => 'Expired-Token-Password-8642',
        ])->assertStatus(410);

        $this->assertSame($before, $this->hashes());
    }

    public function test_mail_failure_revokes_token_and_changes_nothing(): void
    {
        $before = $this->hashes();
        Mail::shouldReceive('to')->once()->andThrow(new \RuntimeException('synthetic mail failure'));

        $this->post('/forgot-password', ['identifier' => $this->email])
            ->assertUnprocessable()
            ->assertSee('could not send the secure reset email')
            ->assertDontSee('synthetic mail failure');

        $this->assertSame($before, $this->hashes());
    }

    public function test_reset_submission_is_throttled(): void
    {
        $token = str_repeat('c', 64);
        for ($attempt = 0; $attempt < 10; $attempt++) {
            $this->post(route('password.admin.update', ['token' => $token]), [
                'password' => 'Rate-Limited-Password-8642',
                'password_confirmation' => 'Rate-Limited-Password-8642',
            ])->assertStatus(410);
        }

        $this->post(route('password.admin.update', ['token' => $token]), [
            'password' => 'Rate-Limited-Password-8642',
            'password_confirmation' => 'Rate-Limited-Password-8642',
        ])->assertTooManyRequests();
    }

    private function insertAdministrator(): void
    {
        DB::table('staff_edu')->insert([
            'staffID' => 'ST900', 'staffName' => 'System Administrator', 'ICNumber' => 'shared-admin-ic',
            'email' => strtoupper($this->email), 'password' => Hash::make('old-staff-password'), 'status' => 'active',
        ]);
        DB::table('trainer')->insert([
            'trainerID' => 'TR900', 'trainerName' => 'System Administrator', 'trainerIC' => 'shared-admin-ic',
            'trainerEmail' => $this->email, 'trainerPassword' => Hash::make('old-trainer-password'), 'status' => 'aktif',
        ]);
    }

    private function insertRemainingAdministratorRoles(): void
    {
        $hash = fn (): string => Hash::make('old-role-password');
        DB::table('teacher')->insert([
            'teacherID' => 'TC900', 'teacherName' => 'System Administrator', 'ICNumber' => 'shared-admin-ic',
            'email' => $this->email, 'password' => $hash(), 'role' => 'teacher',
        ]);
        DB::table('assign')->insert([
            'schoolID' => 'SC900', 'teacherID' => 'TC900', 'assignDate' => '2026-01-01', 'status' => 'active',
        ]);
        DB::table('observer')->insert([
            'observerID' => 'OB900', 'teacherID' => 'TC900', 'status' => 'active',
        ]);
        DB::table('external_observer')->insert([
            'externalObserverID' => 'EO900', 'teacherID' => 'TC900', 'status' => 'active',
        ]);
        DB::table('guru_new')->insert([
            'gn_id' => 'GN900', 'gn_name' => 'System Administrator', 'ic_number' => 'shared-admin-ic',
            'email' => $this->email, 'password' => $hash(), 'role' => 'new_teacher', 'current_status' => 'active',
        ]);
        DB::table('principal')->insert([
            'principalID' => 'PR900', 'principalName' => 'System Administrator', 'ICNumber' => 'shared-admin-ic',
            'email' => $this->email, 'password' => $hash(), 'role' => 'principal', 'status' => 'active',
        ]);
        DB::table('hr_administrator')->insert([
            'hrid' => 'HR900', 'username' => 'System Administrator', 'email' => $this->email,
            'password' => $hash(), 'role' => 'hr',
        ]);
        $userId = DB::table('users')->insertGetId([
            'name' => 'System Administrator', 'email' => $this->email, 'password' => $hash(),
        ]);
        DB::table('outsider')->insert([
            'user_id' => $userId, 'name' => 'System Administrator', 'ICNumber' => 'shared-admin-ic',
        ]);
        DB::table('model_has_roles')->insert([
            'role_id' => 1, 'model_type' => 'App\\Models\\User', 'model_id' => $userId,
        ]);
    }

    private function sentToken(): string
    {
        $sent = null;
        Mail::assertSent(AdministratorPasswordResetMail::class, function (AdministratorPasswordResetMail $mail) use (&$sent): bool {
            $sent = $mail;

            return true;
        });
        $this->assertInstanceOf(AdministratorPasswordResetMail::class, $sent);
        $path = (string) parse_url($sent->resetUrl, PHP_URL_PATH);

        return basename($path);
    }

    /** @return array{0: string, 1: string} */
    private function hashes(): array
    {
        return [
            (string) DB::table('staff_edu')->where('staffID', 'ST900')->value('password'),
            (string) DB::table('trainer')->where('trainerID', 'TR900')->value('trainerPassword'),
        ];
    }
}
