<?php

namespace Tests\Feature;

use App\Services\CanonicalIdentityService;
use App\Services\HandoffService;
use Mockery\MockInterface;
use Tests\TestCase;

class SystemLaunchAndRedemptionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'sso.require_https' => false,
            'sso.systems.aidid.secret' => 'unit-test-aidid-secret',
            'sso.systems.aidid.enabled' => true,
            'sso.systems.aidid.url' => 'http://aidid.test',
        ]);
        $this->mock(CanonicalIdentityService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('revalidate')
                ->byDefault()
                ->andReturnUsing(fn (array $identity): array => $identity);
        });
    }

    public function test_system_chooser_and_launch_enforce_matrix_and_availability(): void
    {
        $staff = $this->identity('staff_edu');
        $this->withSession(['gateway.identity' => $staff])->get('/systems')
            ->assertOk()
            ->assertSee('Trainer Management System')
            ->assertSee('TrainHub')
            ->assertSee('Teacher Employment System')
            ->assertDontSee('EduTrain')
            ->assertDontSee('Human Resources System');

        $this->withSession(['gateway.identity' => $staff])->post('/systems/izz/launch')->assertForbidden();
        $this->withSession(['gateway.identity' => $staff])->post('/systems/syiqin/launch')
            ->assertRedirectContains('http://syiqin.test/sso/callback?code=');

        $hr = $this->identity('hr');
        $this->withSession(['gateway.identity' => $hr])->get('/systems')
            ->assertOk()
            ->assertSee('Teacher Employment System')
            ->assertSee('Human Resources System')
            ->assertSee('EduTrain')
            ->assertDontSee('Trainer Management System')
            ->assertDontSee('TrainHub');
        $this->withSession(['gateway.identity' => $hr])->post('/systems/izz/launch')
            ->assertRedirectContains('http://izz.test/sso/callback?code=');

        $teacher = $this->identity('teacher');
        $this->withSession(['gateway.identity' => $teacher])->post('/systems/aidid/launch')->assertForbidden();
        $this->withSession(['gateway.identity' => $teacher])->post('/systems/unknown/launch')->assertForbidden();

        config(['sso.systems.aidid.enabled' => false]);
        $this->withSession(['gateway.identity' => $staff])->get('/systems')->assertDontSee('Trainer Management System');
        $this->withSession(['gateway.identity' => $staff])->post('/systems/aidid/launch')->assertForbidden();
    }

    public function test_launch_issues_only_an_opaque_target_bound_code_and_ignores_redirect_input(): void
    {
        $identity = $this->identity('trainer');
        $response = $this->withSession(['gateway.identity' => $identity])
            ->post('/systems/aidid/launch', ['redirect' => 'https://evil.example/steal']);

        $location = (string) $response->headers->get('Location');
        $response->assertRedirectContains('http://aidid.test/sso/callback?code=');
        $this->assertStringNotContainsString('evil.example', $location);
        $this->assertStringNotContainsString('email=', $location);
        $this->assertStringNotContainsString('role=', $location);

        parse_str((string) parse_url($location, PHP_URL_QUERY), $query);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $query['code']);
        $payload = app(HandoffService::class)->redeem($query['code'], 'aidid');
        $this->assertSame('trainer', $payload['identity']['role']);
        $this->assertSame('aidid', $payload['target']);
    }

    public function test_https_policy_rejects_http_target_and_insecure_redemption_when_enabled(): void
    {
        config(['sso.require_https' => true]);
        $identity = $this->identity('trainer');

        $this->withSession(['gateway.identity' => $identity])
            ->from('/systems')
            ->post('/systems/aidid/launch')
            ->assertRedirect('/systems')
            ->assertSessionHasErrors('system');

        $code = app(HandoffService::class)->issue('aidid', $this->role('trainer'));
        $this->withHeaders($this->headers())
            ->postJson('/sso/redeem', ['code' => $code])
            ->assertStatus(400)
            ->assertJsonPath('message', 'Secure transport is required.');
    }

    public function test_explicit_http_policy_permits_http_launch_and_redemption_in_production(): void
    {
        config(['app.env' => 'production', 'sso.require_https' => false]);
        $identity = $this->identity('trainer');

        $launch = $this->withSession(['gateway.identity' => $identity])
            ->post('/systems/aidid/launch')
            ->assertRedirectContains('http://aidid.test/sso/callback?code=');

        parse_str((string) parse_url((string) $launch->headers->get('Location'), PHP_URL_QUERY), $query);
        $this->withHeaders($this->headers())
            ->postJson('/sso/redeem', ['code' => $query['code']])
            ->assertOk()
            ->assertJsonPath('target', 'aidid')
            ->assertJsonPath('identity.role', 'trainer');
    }

    public function test_multi_role_handoff_is_bound_to_the_server_selected_role(): void
    {
        $staff = $this->role('staff_edu');
        $trainer = $this->role('trainer');
        $identity = [
            'email' => $staff['email'],
            'name' => $staff['name'],
            'roles' => ['staff_edu' => $staff, 'trainer' => $trainer],
            'selected_role' => 'staff_edu',
        ];

        $staffLaunch = $this->withSession(['gateway.identity' => $identity])
            ->post('/systems/aidid/launch');
        parse_str((string) parse_url((string) $staffLaunch->headers->get('Location'), PHP_URL_QUERY), $staffQuery);
        $this->assertSame('staff_edu', app(HandoffService::class)->redeem($staffQuery['code'], 'aidid')['identity']['role']);

        $identity['selected_role'] = 'trainer';
        $trainerLaunch = $this->withSession(['gateway.identity' => $identity])
            ->post('/systems/aidid/launch');
        parse_str((string) parse_url((string) $trainerLaunch->headers->get('Location'), PHP_URL_QUERY), $trainerQuery);
        $this->assertSame('trainer', app(HandoffService::class)->redeem($trainerQuery['code'], 'aidid')['identity']['role']);
    }

    public function test_redemption_authenticates_client_and_consumes_code_once(): void
    {
        $code = app(HandoffService::class)->issue('aidid', $this->role('staff_edu'));

        $this->postJson('/sso/redeem', ['code' => $code])->assertUnauthorized();
        $this->withHeaders($this->headers('wrong-secret'))
            ->postJson('/sso/redeem', ['code' => $code])->assertUnauthorized();
        $this->withHeaders([
            'X-Alamin-SSO-Client' => 'izz',
            'X-Alamin-SSO-Secret' => 'test-izz-secret',
        ])->postJson('/sso/redeem', ['code' => $code])->assertStatus(410);

        $this->withHeaders($this->headers())
            ->postJson('/sso/redeem', ['code' => $code])
            ->assertOk()
            ->assertJsonPath('target', 'aidid')
            ->assertJsonPath('identity.role', 'staff_edu');
        $this->withHeaders($this->headers())
            ->postJson('/sso/redeem', ['code' => $code])->assertStatus(410);
    }

    public function test_expired_wrong_target_and_malformed_codes_fail(): void
    {
        $service = app(HandoffService::class);
        $wrongTarget = $service->issue('aidid', $this->role('trainer'));
        $this->assertNull($service->redeem($wrongTarget, 'izz'));
        $this->assertNotNull($service->redeem($wrongTarget, 'aidid'));

        $expired = $service->issue('aidid', $this->role('trainer'));
        $this->travel(91)->seconds();
        $this->assertNull($service->redeem($expired, 'aidid'));
        $this->travelBack();

        $this->assertNull($service->redeem('not-a-code', 'aidid'));
        $this->withHeaders($this->headers())
            ->postJson('/sso/redeem', ['code' => 'not-a-code'])
            ->assertUnprocessable();
    }

    public function test_logged_out_user_cannot_launch(): void
    {
        $this->post('/systems/aidid/launch')->assertRedirect('/login');
    }

    public function test_launch_revalidates_selected_role_before_issuing_handoff(): void
    {
        $this->mock(CanonicalIdentityService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('revalidate')->once()->andReturnNull();
        });

        $this->withSession(['gateway.identity' => $this->identity('trainer')])
            ->post('/systems/aidid/launch')
            ->assertForbidden()
            ->assertSessionMissing('gateway.identity');
    }

    private function identity(string $role): array
    {
        $roleIdentity = $this->role($role);

        return [
            'email' => $roleIdentity['email'],
            'name' => $roleIdentity['name'],
            'roles' => [$role => $roleIdentity],
            'selected_role' => $role,
        ];
    }

    private function role(string $role): array
    {
        return [
            'role' => $role,
            'source' => $role,
            'id' => $role === 'trainer' ? 'TR001' : 'ST001',
            'name' => 'Portal User',
            'email' => 'portal@example.test',
        ];
    }

    private function headers(string $secret = 'unit-test-aidid-secret'): array
    {
        return ['X-Alamin-SSO-Client' => 'aidid', 'X-Alamin-SSO-Secret' => $secret];
    }
}
