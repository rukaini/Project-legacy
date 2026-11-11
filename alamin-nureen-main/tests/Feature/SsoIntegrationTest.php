<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
            'database.default' => 'sqlite',
            'database.connections.sqlite' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => true,
            ],
            'alamin_login.url' => 'http://gateway.test',
            'alamin_login.systems_url' => 'http://gateway.test/systems',
            'alamin_login.redeem_url' => 'http://gateway.test/sso/redeem',
            'alamin_login.client_id' => 'nureen',
            'alamin_login.client_secret' => 'unit-test-client-secret',
            'alamin_login.require_https' => false,
        ]);

        DB::purge();
        DB::reconnect();

        Schema::create('staff_edu', function (Blueprint $table): void {
            $table->string('staffID')->primary();
            $table->string('staffName');
            $table->string('email')->unique();
            $table->string('status')->nullable();
            $table->string('role')->nullable();
            $table->unsignedTinyInteger('password_changed_required')->default(0);
        });
        Schema::create('login_session', function (Blueprint $table): void {
            $table->increments('sessionID');
            $table->string('staffID');
            $table->timestamp('loginTime');
            $table->timestamp('logoutTime')->nullable();
            $table->string('sessionStatus');
        });
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
        parent::tearDown();
    }

    public function test_valid_staff_edu_handoff_creates_trainhub_session(): void
    {
        $this->insertStaff();
        $this->fakeGateway();

        $this->get('/sso/callback?code='.$this->code)
            ->assertRedirect('/dashboard.php')
            ->assertSessionHas('staffID', 'ST001')
            ->assertSessionHas('staffName', 'Staff Admin')
            ->assertSessionHas('auth_source', 'sso');

        $this->assertSame('ST001', $_SESSION['staffID']);
        $this->assertSame('sso', $_SESSION['auth_source']);
        $this->assertDatabaseHas('login_session', [
            'staffID' => 'ST001',
            'sessionStatus' => 'active',
        ]);

        Http::assertSent(fn ($request): bool => $request->url() === 'http://gateway.test/sso/redeem'
            && $request->header('X-Alamin-SSO-Client')[0] === 'nureen'
            && $request->header('X-Alamin-SSO-Secret')[0] === 'unit-test-client-secret'
            && $request['code'] === $this->code
            && ! isset($request['role'], $request['email']));
    }

    public function test_wrong_role_source_target_or_canonical_email_is_rejected(): void
    {
        $this->insertStaff();

        foreach ([
            ['role' => 'trainer'],
            ['source' => 'trainer'],
            ['email' => 'changed@example.test'],
        ] as $changes) {
            $this->fakeGateway($changes);
            $this->get('/sso/callback?code='.$this->code)
                ->assertRedirect('http://gateway.test');
            $this->assertDatabaseCount('login_session', 0);
        }

        $this->fakeGateway([], 'aidid');
        $this->get('/sso/callback?code='.$this->code)
            ->assertRedirect('http://gateway.test');
        $this->assertDatabaseCount('login_session', 0);
    }

    public function test_malformed_expired_or_replayed_code_returns_to_alamin_login(): void
    {
        Http::preventStrayRequests();
        $this->get('/sso/callback?code=bad')->assertRedirect('http://gateway.test');

        Http::fake(['http://gateway.test/sso/redeem' => Http::response([
            'message' => 'This sign-in link is invalid, expired, or already used.',
        ], 410)]);
        $this->get('/sso/callback?code='.$this->code)->assertRedirect('http://gateway.test');
    }

    public function test_inactive_or_missing_local_staff_is_rejected(): void
    {
        $this->insertStaff('inactive');
        $this->fakeGateway();
        $this->get('/sso/callback?code='.$this->code)->assertRedirect('http://gateway.test');

        DB::table('staff_edu')->delete();
        $this->get('/sso/callback?code='.$this->code)->assertRedirect('http://gateway.test');
        $this->assertDatabaseCount('login_session', 0);
    }

    public function test_https_policy_blocks_http_gateway_when_enabled(): void
    {
        config(['alamin_login.require_https' => true]);
        Http::preventStrayRequests();

        $this->get('/sso/callback?code='.$this->code)->assertStatus(503);
        Http::assertNothingSent();
    }

    public function test_logout_ends_only_local_session_and_returns_to_system_chooser(): void
    {
        DB::table('login_session')->insert([
            'sessionID' => 5,
            'staffID' => 'ST001',
            'loginTime' => now(),
            'sessionStatus' => 'active',
        ]);
        $session = [
            'staffID' => 'ST001',
            'staffName' => 'Staff Admin',
            'loginSessionID' => 5,
            'auth_source' => 'sso',
        ];

        $this->withSession($session)->get('/logout.php')
            ->assertRedirect('http://gateway.test/systems')
            ->assertSessionMissing('staffID')
            ->assertSessionMissing('auth_source');

        $this->assertDatabaseHas('login_session', [
            'sessionID' => 5,
            'sessionStatus' => 'ended',
        ]);
    }

    private function insertStaff(string $status = 'active'): void
    {
        DB::table('staff_edu')->insert([
            'staffID' => 'ST001',
            'staffName' => 'Staff Admin',
            'email' => 'staff@example.test',
            'status' => $status,
            'role' => 'STAFF_EDU',
            'password_changed_required' => 0,
        ]);
    }

    private function fakeGateway(array $identityChanges = [], string $target = 'nureen'): void
    {
        $identity = array_replace([
            'role' => 'staff_edu',
            'source' => 'staff_edu',
            'id' => 'ST001',
            'name' => 'Gateway Name',
            'email' => 'staff@example.test',
        ], $identityChanges);

        Http::fake(['http://gateway.test/sso/redeem' => Http::response([
            'identity' => $identity,
            'target' => $target,
            'issued_at' => now()->timestamp,
            'expires_at' => now()->addSeconds(90)->timestamp,
        ])]);
    }
}
