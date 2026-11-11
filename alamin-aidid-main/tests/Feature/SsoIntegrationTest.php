<?php

namespace Tests\Feature;

use App\Exceptions\SsoException;
use App\Services\GatewaySsoClient;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SsoIntegrationTest extends TestCase
{
    private string $code;

    protected function setUp(): void
    {
        parent::setUp();
        $this->code = str_repeat('a', 64);
        config([
            'gateway.url' => 'http://gateway.test',
            'gateway.portal_url' => 'http://gateway.test/systems',
            'gateway.client_id' => 'aidid',
            'gateway.client_secret' => 'unit-test-client-secret',
            'gateway.require_https' => false,
        ]);

        Schema::create('staff_edu', function (Blueprint $table): void {
            $table->string('staffID')->primary();
            $table->string('staffName');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('status')->nullable();
        });
        Schema::create('trainer', function (Blueprint $table): void {
            $table->string('trainerID')->primary();
            $table->string('trainerName');
            $table->string('trainerEmail')->unique();
            $table->string('trainerPassword');
            $table->string('status')->nullable();
        });
    }

    public function test_receiver_environment_uses_alamin_login_names_only(): void
    {
        $example = file_get_contents(base_path('.env.example'));
        $configuration = file_get_contents(config_path('gateway.php'));

        foreach ([
            'ALAMIN_LOGIN_URL',
            'ALAMIN_LOGIN_CLIENT_ID',
            'ALAMIN_LOGIN_CLIENT_SECRET',
            'ALAMIN_LOGIN_CONNECT_TIMEOUT',
            'ALAMIN_LOGIN_TIMEOUT',
            'ALAMIN_LOGIN_REQUIRE_HTTPS',
        ] as $name) {
            $this->assertStringContainsString($name, $example);
            $this->assertStringContainsString("env('{$name}'", $configuration);
        }

        $this->assertStringContainsString('ALAMIN_LOGIN_REQUIRE_HTTPS=true', $example);
        $this->assertStringContainsString("env('ALAMIN_LOGIN_REQUIRE_HTTPS', true)", $configuration);
    }

    public function test_login_redirects_to_alamin_login_and_direct_post_login_is_unavailable(): void
    {
        $this->get('/login')
            ->assertRedirect('http://gateway.test');

        $this->post('/login', [
            'role' => 'admin',
            'email' => 'local-login@example.test',
            'password' => 'not-used',
        ])->assertStatus(405)
            ->assertSessionMissing('user_id')
            ->assertSessionMissing('user_role');

        $this->assertFileDoesNotExist(resource_path('views/auth/login.blade.php'));
    }

    public function test_staff_edu_sso_revalidates_identity_creates_local_session_and_redirects(): void
    {
        $this->insertStaff('ST001', 'Staff Admin', 'staff@example.test', 'active');
        $this->fakeGateway($this->identity('staff_edu', 'staff_edu', 'ST001', 'staff@example.test'));

        $this->withSession(['stale_pre_auth' => true])
            ->get('/sso/callback?code='.$this->code)
            ->assertRedirect('/admin/dashboard')
            ->assertSessionMissing('stale_pre_auth')
            ->assertSessionHas('user_id', 'ST001')
            ->assertSessionHas('user_name', 'Staff Admin')
            ->assertSessionHas('user_email', 'staff@example.test')
            ->assertSessionHas('user_role', 'admin')
            ->assertSessionHas('auth_source', 'sso')
            ->assertSessionMissing('password');

        Http::assertSent(function ($request): bool {
            return $request->url() === 'http://gateway.test/sso/redeem'
                && $request->header('X-Alamin-SSO-Client')[0] === 'aidid'
                && $request->header('X-Alamin-SSO-Secret')[0] === 'unit-test-client-secret'
                && $request['code'] === $this->code
                && ! isset($request['role'], $request['email']);
        });
    }

    public function test_trainer_sso_revalidates_identity_and_redirects_to_trainer_dashboard(): void
    {
        $this->insertTrainer('TR001', 'Trainer User', 'trainer@example.test', 'Aktif');
        $this->fakeGateway($this->identity('trainer', 'trainer', 'TR001', 'trainer@example.test'));

        $this->get('/sso/callback?code='.$this->code)
            ->assertRedirect('/trainer/dashboard')
            ->assertSessionHas('user_id', 'TR001')
            ->assertSessionHas('user_name', 'Trainer User')
            ->assertSessionHas('user_email', 'trainer@example.test')
            ->assertSessionHas('user_role', 'trainer')
            ->assertSessionHas('auth_source', 'sso');
    }

    public function test_unauthorized_gateway_role_or_mismatched_source_is_denied(): void
    {
        $this->fakeGateway($this->identity('teacher', 'teacher', 'TC001', 'teacher@example.test'));
        $this->get('/sso/callback?code='.$this->code)
            ->assertRedirect('http://gateway.test')
            ->assertSessionMissing('error');

        $this->insertStaff('ST002', 'Staff User', 'staff2@example.test', 'active');
        $this->fakeGateway($this->identity('staff_edu', 'trainer', 'ST002', 'staff2@example.test'));
        $this->get('/sso/callback?code='.str_repeat('b', 64))
            ->assertRedirect('http://gateway.test')
            ->assertSessionMissing('error');
    }

    public function test_invalid_expired_replayed_or_malformed_handoff_returns_to_alamin_login(): void
    {
        Http::fake(['http://gateway.test/sso/redeem' => Http::response([
            'message' => 'This sign-in link is invalid, expired, or already used.',
        ], 410)]);

        $this->withSession(['stale_sso_attempt' => true])
            ->get('/sso/callback?code='.$this->code)
            ->assertRedirect('http://gateway.test')
            ->assertSessionMissing('stale_sso_attempt')
            ->assertSessionMissing('error');

        Http::preventStrayRequests();
        $this->get('/sso/callback?code=bad')
            ->assertRedirect('http://gateway.test')
            ->assertSessionMissing('error');

        $this->get('/login')
            ->assertRedirect('http://gateway.test');

        $this->assertFileDoesNotExist(resource_path('views/sso/error.blade.php'));
    }

    public function test_missing_or_inactive_canonical_identity_is_denied(): void
    {
        $this->fakeGateway($this->identity('staff_edu', 'staff_edu', 'MISSING', 'missing@example.test'));
        $this->get('/sso/callback?code='.$this->code)
            ->assertRedirect('http://gateway.test')
            ->assertSessionMissing('error');

        $this->insertTrainer('TR002', 'Inactive Trainer', 'inactive@example.test', 'inactive');
        $this->fakeGateway($this->identity('trainer', 'trainer', 'TR002', 'inactive@example.test'));
        $this->get('/sso/callback?code='.str_repeat('c', 64))
            ->assertRedirect('http://gateway.test')
            ->assertSessionMissing('error');
    }

    public function test_canonical_email_must_still_match_the_gateway_identity(): void
    {
        $this->insertStaff('ST003', 'Staff User', 'canonical@example.test', 'active');
        $this->fakeGateway($this->identity('staff_edu', 'staff_edu', 'ST003', 'changed@example.test'));

        $this->get('/sso/callback?code='.$this->code)
            ->assertRedirect('http://gateway.test')
            ->assertSessionMissing('error');
    }

    public function test_staff_sso_logout_destroys_local_session_and_returns_to_gateway(): void
    {
        $this->insertStaff('ST005', 'SSO Staff', 'sso.staff@example.test', 'active');
        $this->fakeGateway($this->identity('staff_edu', 'staff_edu', 'ST005', 'sso.staff@example.test'));

        $this->get('/sso/callback?code='.$this->code)->assertRedirect('/admin/dashboard');
        $this->post('/logout')
            ->assertRedirect('http://gateway.test/systems')
            ->assertSessionMissing('user_id')
            ->assertSessionMissing('user_role')
            ->assertSessionMissing('auth_source');

        Http::assertSentCount(1);
    }

    public function test_trainer_sso_logout_destroys_local_session_and_returns_to_gateway(): void
    {
        $this->insertTrainer('TR005', 'SSO Trainer', 'sso.trainer@example.test', 'active');
        $this->fakeGateway($this->identity('trainer', 'trainer', 'TR005', 'sso.trainer@example.test'));

        $this->get('/sso/callback?code='.str_repeat('d', 64))->assertRedirect('/trainer/dashboard');
        $this->post('/logout')
            ->assertRedirect('http://gateway.test/systems')
            ->assertSessionMissing('user_id')
            ->assertSessionMissing('user_role')
            ->assertSessionMissing('auth_source');

        Http::assertSentCount(1);
    }

    public function test_logout_ignores_browser_redirect_parameters(): void
    {
        $this->withSession([
            'user_id' => 'ST007',
            'user_name' => 'SSO User',
            'user_email' => 'sso@example.test',
            'user_role' => 'admin',
            'auth_source' => 'sso',
        ])->post('/logout', ['redirect' => 'https://evil.example/steal'])
            ->assertRedirect('http://gateway.test/systems');

        $this->withSession([
            'user_id' => 'TR007',
            'user_name' => 'Local User',
            'user_email' => 'local@example.test',
            'user_role' => 'trainer',
        ])->post('/logout', ['redirect' => 'https://evil.example/steal'])
            ->assertRedirect('http://gateway.test/systems');
    }

    public function test_logout_and_existing_dashboard_checks_remain_effective(): void
    {
        $this->get('/admin/dashboard')->assertRedirect('/login');
        $this->get('/trainer/dashboard')->assertRedirect('/login');

        $this->withSession([
            'user_id' => 'TR005', 'user_name' => 'User', 'user_email' => 'user@example.test', 'user_role' => 'trainer',
        ])->post('/logout')
            ->assertRedirect('http://gateway.test/systems')
            ->assertSessionMissing('user_id')
            ->assertSessionMissing('user_role');
    }

    public function test_https_policy_rejects_an_http_alamin_login_url_when_enabled(): void
    {
        config(['gateway.require_https' => true]);
        Http::preventStrayRequests();

        $this->get('/sso/callback?code='.$this->code)
            ->assertRedirect('http://gateway.test')
            ->assertSessionMissing('error');
    }

    public function test_missing_receiver_secret_returns_to_alamin_login_without_a_local_fallback(): void
    {
        config(['gateway.client_secret' => '']);
        Http::preventStrayRequests();

        $this->withSession(['stale_sso_attempt' => true])
            ->get('/sso/callback?code='.$this->code)
            ->assertRedirect('http://gateway.test')
            ->assertSessionMissing('stale_sso_attempt')
            ->assertSessionMissing('error');
    }

    public function test_missing_receiver_configuration_causes_the_documented_client_error(): void
    {
        config(['gateway.client_secret' => '']);
        Http::preventStrayRequests();

        $this->expectException(SsoException::class);
        $this->expectExceptionMessage('Single sign-on is not configured for this system.');

        app(GatewaySsoClient::class)->redeem($this->code);
    }

    public function test_explicit_http_policy_permits_http_redemption_in_production(): void
    {
        config(['app.env' => 'production', 'gateway.require_https' => false]);
        $this->insertTrainer('TR006', 'HTTP Trainer', 'http-trainer@example.test', 'Aktif');
        $this->fakeGateway($this->identity('trainer', 'trainer', 'TR006', 'http-trainer@example.test'));

        $this->get('/sso/callback?code='.$this->code)
            ->assertRedirect('/trainer/dashboard')
            ->assertSessionHas('user_id', 'TR006')
            ->assertSessionHas('user_role', 'trainer')
            ->assertSessionHas('auth_source', 'sso');

        Http::assertSent(fn ($request): bool => $request->url() === 'http://gateway.test/sso/redeem');
    }

    private function fakeGateway(array $identity): void
    {
        Http::fake(['http://gateway.test/sso/redeem' => Http::response([
            'identity' => $identity,
            'target' => 'aidid',
            'issued_at' => now()->timestamp,
            'expires_at' => now()->addSeconds(90)->timestamp,
        ])]);
    }

    private function identity(string $role, string $source, string $id, string $email): array
    {
        return compact('role', 'source', 'id', 'email') + ['name' => 'Gateway Name'];
    }

    private function insertStaff(
        string $id,
        string $name,
        string $email,
        string $status,
        string $password = 'unused-password',
    ): void {
        DB::table('staff_edu')->insert([
            'staffID' => $id,
            'staffName' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'status' => $status,
        ]);
    }

    private function insertTrainer(
        string $id,
        string $name,
        string $email,
        string $status,
        string $password = 'unused-password',
    ): void {
        DB::table('trainer')->insert([
            'trainerID' => $id,
            'trainerName' => $name,
            'trainerEmail' => $email,
            'trainerPassword' => Hash::make($password),
            'status' => $status,
        ]);
    }
}
