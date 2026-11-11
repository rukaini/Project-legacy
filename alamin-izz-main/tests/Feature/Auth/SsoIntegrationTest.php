<?php

namespace Tests\Feature\Auth;

use App\Enums\Role;
use App\Models\CanonicalSsoUser;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role as SpatieRole;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Builds only the tables it needs: the full migration set is MySQL-specific
 * and cannot run on the in-memory SQLite test database.
 *
 * The "role|source" pairs below are TEST FIXTURES, not the real gateway values.
 */
class SsoIntegrationTest extends TestCase
{
    private string $code;

    protected function setUp(): void
    {
        parent::setUp();

        $this->code = str_repeat('a', 64);
        config([
            'gateway.url' => 'http://gateway.test',
            'gateway.portal_url' => 'http://gateway.test',
            'gateway.client_id' => 'edutrain',
            'gateway.client_secret' => 'unit-test-client-secret',
            'gateway.require_https' => false,
            'gateway.roles' => [
                'fx_teacher|fx_teacher' => Role::Teacher->value,
                'fx_new|fx_new' => Role::GuruBaru->value,
                'fx_principal|fx_principal' => Role::GuruBesar->value,
                'fx_hr|fx_hr' => Role::HR->value,
                'fx_outsider|fx_outsider' => Role::Outsider->value,
            ],
        ]);

        $this->migrate('0001_01_01_000000_create_users_table.php');
        $this->migrate('2026_05_14_161448_create_permission_tables.php');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Schema::create('teacher', function (Blueprint $t): void {
            $t->string('teacherID')->primary();
            $t->string('teacherName');
            $t->string('email');
            $t->string('role')->nullable();
            $t->string('password')->nullable();
        });
        Schema::create('assign', function (Blueprint $t): void {
            $t->string('teacherID');
            $t->string('schoolID')->nullable();
            $t->dateTime('assignDate')->nullable();
            $t->string('status')->nullable();
        });
        Schema::create('principal', function (Blueprint $t): void {
            $t->string('principalID')->primary();
            $t->string('principalName');
            $t->string('email');
            $t->string('role')->nullable();
            $t->string('status')->nullable();
            $t->string('password')->nullable();
        });
        Schema::create('guru_new', function (Blueprint $t): void {
            $t->string('gn_id')->primary();
            $t->string('gn_name');
            $t->string('email');
            $t->string('role')->nullable();
            $t->string('current_status')->nullable();
            $t->string('password')->nullable();
        });

        // HandleInertiaRequests counts these for guests on every web request.
        Schema::create('course', function (Blueprint $t): void {
            $t->string('courseID')->primary();
        });
        Schema::create('notification_ladap', function (Blueprint $t): void {
            $t->unsignedBigInteger('user_id');
            $t->boolean('is_read')->default(false);
        });
        Schema::create('attendance', function (Blueprint $t): void {
            $t->id('attendance_id');
            $t->decimal('hours_ladap')->default(0);
        });

        foreach (Role::cases() as $role) {
            SpatieRole::findOrCreate($role->value, 'web');
        }
    }

    public function test_receiver_environment_uses_alamin_login_names_only(): void
    {
        $example = file_get_contents(base_path('.env.example'));
        $configuration = file_get_contents(config_path('gateway.php'));

        foreach ([
            'ALAMIN_LOGIN_URL', 'ALAMIN_LOGIN_CLIENT_ID', 'ALAMIN_LOGIN_CLIENT_SECRET',
            'ALAMIN_LOGIN_CONNECT_TIMEOUT', 'ALAMIN_LOGIN_TIMEOUT', 'ALAMIN_LOGIN_REQUIRE_HTTPS',
        ] as $name) {
            $this->assertStringContainsString($name, $example);
            $this->assertStringContainsString("env('{$name}'", $configuration);
        }

        $this->assertMatchesRegularExpression('/^ALAMIN_LOGIN_CLIENT_SECRET=$/m', $example);
        $this->assertMatchesRegularExpression('/^ALAMIN_LOGIN_URL=$/m', $example);
    }

    public function test_env_file_is_gitignored(): void
    {
        $this->assertMatchesRegularExpression('/^\.env$/m', file_get_contents(base_path('.gitignore')));
    }

    public function test_teacher_signs_in_without_a_users_row(): void
    {
        DB::table('teacher')->insert(['teacherID' => 'TC0001', 'teacherName' => 'Teacher One', 'email' => 't@example.test', 'role' => 'teacher']);
        $this->fakeGateway('fx_teacher', 'fx_teacher', 'TC0001', 't@example.test');

        $this->withSession(['stale_pre_auth' => true])
            ->get('/sso/callback?code='.$this->code)
            ->assertRedirect('/dashboard')
            ->assertSessionMissing('stale_pre_auth')
            ->assertSessionHas('auth_source', 'sso');

        $this->assertAuthenticated();
        $this->assertInstanceOf(CanonicalSsoUser::class, auth()->user());
        $this->assertTrue(auth()->user()->hasRole(Role::Teacher->value));
        $this->assertSame(0, User::count());

        Http::assertSent(fn ($request): bool => $request->url() === 'http://gateway.test/sso/redeem'
            && $request->header('X-Alamin-SSO-Client')[0] === 'edutrain'
            && $request->header('X-Alamin-SSO-Secret')[0] === 'unit-test-client-secret'
            && $request['code'] === $this->code
            && ! isset($request['role'], $request['email']));
    }

    public function test_principal_signs_in(): void
    {
        DB::table('principal')->insert(['principalID' => 'PR0001', 'principalName' => 'Principal', 'email' => 'p@example.test', 'role' => 'principal', 'status' => 'active']);
        $this->fakeGateway('fx_principal', 'fx_principal', 'PR0001', 'p@example.test');

        $this->get('/sso/callback?code='.$this->code)->assertRedirect('/dashboard');
        $this->assertTrue(auth()->user()->hasRole(Role::GuruBesar->value));
    }

    public function test_new_teacher_signs_in(): void
    {
        DB::table('guru_new')->insert(['gn_id' => 'GN0001', 'gn_name' => 'New Teacher', 'email' => 'n@example.test', 'role' => 'new_teacher', 'current_status' => 'active']);
        $this->fakeGateway('fx_new', 'fx_new', 'GN0001', 'n@example.test');

        $this->get('/sso/callback?code='.$this->code)->assertRedirect('/dashboard');
        $this->assertTrue(auth()->user()->hasRole(Role::GuruBaru->value));
    }

    public function test_hr_signs_in_through_users_table_and_spatie_role(): void
    {
        $hr = User::create(['name' => 'HR', 'email' => 'hr@example.test', 'password' => 'x']);
        $hr->assignRole(Role::HR->value);
        $this->fakeGateway('fx_hr', 'fx_hr', '1', 'hr@example.test');

        $this->get('/sso/callback?code='.$this->code)->assertRedirect('/dashboard');
        $this->assertTrue(auth()->user()->is($hr));
    }

    public function test_outsider_signs_in_through_users_table_and_spatie_role(): void
    {
        $out = User::create(['name' => 'Out', 'email' => 'out@example.test', 'password' => 'x']);
        $out->assignRole(Role::Outsider->value);
        $this->fakeGateway('fx_outsider', 'fx_outsider', '2', 'out@example.test');

        $this->get('/sso/callback?code='.$this->code)->assertRedirect('/dashboard');
        $this->assertTrue(auth()->user()->is($out));
    }

    public function test_users_row_without_the_mapped_spatie_role_is_denied(): void
    {
        $user = User::create(['name' => 'Wrong', 'email' => 'wrong@example.test', 'password' => 'x']);
        $user->assignRole(Role::Outsider->value);
        $this->fakeGateway('fx_hr', 'fx_hr', '1', 'wrong@example.test');

        $this->get('/sso/callback?code='.$this->code)->assertRedirect('/login')->assertSessionHas('error');
        $this->assertGuest();
    }

    public function test_unmapped_role_is_denied_and_logged_without_pii(): void
    {
        Log::spy();
        $this->fakeGateway('mystery', 'mystery', 'X1', 'secret@example.test');

        $this->get('/sso/callback?code='.$this->code)
            ->assertRedirect('/login')
            ->assertSessionHas('error', 'Your verified account is not active or authorized for EduTrain.');
        $this->assertGuest();

        Log::shouldHaveReceived('warning')->withArgs(
            fn ($message, $context): bool => $context === ['role' => 'mystery', 'source' => 'mystery']
        )->once();
    }

    public function test_empty_default_mapping_denies_everyone(): void
    {
        config(['gateway.roles' => []]);
        DB::table('teacher')->insert(['teacherID' => 'TC0001', 'teacherName' => 'T', 'email' => 't@example.test', 'role' => 'teacher']);
        $this->fakeGateway('fx_teacher', 'fx_teacher', 'TC0001', 't@example.test');

        $this->get('/sso/callback?code='.$this->code)->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_missing_inactive_or_email_mismatched_identity_is_denied(): void
    {
        $this->fakeGateway('fx_teacher', 'fx_teacher', 'MISSING', 'm@example.test');
        $this->get('/sso/callback?code='.$this->code)->assertRedirect('/login');

        DB::table('teacher')->insert(['teacherID' => 'TC0002', 'teacherName' => 'Left', 'email' => 'left@example.test', 'role' => 'teacher']);
        DB::table('assign')->insert(['teacherID' => 'TC0002', 'assignDate' => now(), 'status' => 'berhenti']);
        $this->fakeGateway('fx_teacher', 'fx_teacher', 'TC0002', 'left@example.test');
        $this->get('/sso/callback?code='.str_repeat('b', 64))->assertRedirect('/login');

        DB::table('teacher')->insert(['teacherID' => 'TC0003', 'teacherName' => 'T3', 'email' => 'real@example.test', 'role' => 'teacher']);
        $this->fakeGateway('fx_teacher', 'fx_teacher', 'TC0003', 'changed@example.test');
        $this->get('/sso/callback?code='.str_repeat('c', 64))->assertRedirect('/login');

        $this->assertGuest();
    }

    public function test_invalid_replayed_or_malformed_handoff_is_rejected(): void
    {
        Http::fake(['http://gateway.test/sso/redeem' => Http::response([
            'message' => 'This sign-in link is invalid, expired, or already used.',
        ], 410)]);

        $this->withSession(['stale_sso_attempt' => true])
            ->get('/sso/callback?code='.$this->code)
            ->assertRedirect('/login')
            ->assertSessionMissing('stale_sso_attempt')
            ->assertSessionHas('error', 'This sign-in link is invalid, expired, or already used.');

        Http::preventStrayRequests();
        $this->get('/sso/callback?code=bad')
            ->assertRedirect('/login')
            ->assertSessionHas('error', 'This sign-in link is invalid or incomplete.');
        $this->get('/sso/callback')->assertRedirect('/login');
    }

    public function test_response_for_another_target_or_expired_is_rejected(): void
    {
        DB::table('teacher')->insert(['teacherID' => 'TC0001', 'teacherName' => 'T', 'email' => 't@example.test', 'role' => 'teacher']);

        $this->fakeGateway('fx_teacher', 'fx_teacher', 'TC0001', 't@example.test', target: 'other-system');
        $this->get('/sso/callback?code='.$this->code)->assertRedirect('/login');

        $this->fakeGateway('fx_teacher', 'fx_teacher', 'TC0001', 't@example.test', expiresAt: now()->subMinute()->timestamp);
        $this->get('/sso/callback?code='.$this->code)->assertRedirect('/login');

        $this->assertGuest();
    }

    public function test_https_policy_rejects_an_http_gateway_url_when_enabled(): void
    {
        config(['gateway.require_https' => true]);
        Http::preventStrayRequests();

        $this->get('/sso/callback?code='.$this->code)
            ->assertRedirect('/login')
            ->assertSessionHas('error', 'Single sign-on is not securely configured for this system.');
    }

    public function test_missing_secret_is_not_securely_configured(): void
    {
        config(['gateway.client_secret' => null]);
        Http::preventStrayRequests();

        $this->get('/sso/callback?code='.$this->code)
            ->assertRedirect('/login')
            ->assertSessionHas('error', 'Single sign-on is not securely configured for this system.');
    }

    public function test_login_route_redirects_guests_to_al_amin_login(): void
    {
        $this->get('/login')->assertRedirect('http://gateway.test');
        $this->post('/login', ['email' => 'a@b.test', 'password' => 'x'])->assertStatus(405);
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_login_route_sends_signed_in_users_to_their_dashboard(): void
    {
        DB::table('teacher')->insert(['teacherID' => 'TC0001', 'teacherName' => 'T', 'email' => 't@example.test', 'role' => 'teacher']);
        $this->fakeGateway('fx_teacher', 'fx_teacher', 'TC0001', 't@example.test');
        $this->get('/sso/callback?code='.$this->code);

        $this->get('/login')->assertRedirect('/dashboard');
    }

    public function test_logout_destroys_only_local_session_and_returns_to_gateway_ignoring_request_redirects(): void
    {
        DB::table('teacher')->insert(['teacherID' => 'TC0001', 'teacherName' => 'T', 'email' => 't@example.test', 'role' => 'teacher']);
        $this->fakeGateway('fx_teacher', 'fx_teacher', 'TC0001', 't@example.test');
        $this->get('/sso/callback?code='.$this->code)->assertRedirect('/dashboard');

        Http::preventStrayRequests();
        $this->post('/logout', ['redirect' => 'https://evil.example/steal'])
            ->assertRedirect('http://gateway.test')
            ->assertSessionMissing('auth_source');

        $this->assertGuest();
    }

    public function test_role_middleware_still_enforces_route_access(): void
    {
        DB::table('teacher')->insert(['teacherID' => 'TC0001', 'teacherName' => 'T', 'email' => 't@example.test', 'role' => 'teacher']);
        $this->fakeGateway('fx_teacher', 'fx_teacher', 'TC0001', 't@example.test');
        $this->get('/sso/callback?code='.$this->code);

        $this->get('/dashboard/principal')->assertForbidden();
        $this->get('/hr/dashboard')->assertForbidden();
    }

    private function fakeGateway(string $role, string $source, string $id, string $email, string $target = 'edutrain', ?int $expiresAt = null): void
    {
        Http::fake(['http://gateway.test/sso/redeem' => Http::response([
            'identity' => compact('role', 'source', 'id', 'email') + ['name' => 'Gateway Name'],
            'target' => $target,
            'issued_at' => now()->timestamp,
            'expires_at' => $expiresAt ?? now()->addSeconds(90)->timestamp,
        ])]);
    }

    private function migrate(string $file): void
    {
        $this->artisan('migrate', [
            '--path' => 'database/migrations/'.$file,
            '--realpath' => false,
        ])->run();
    }
}
