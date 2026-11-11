<?php

namespace Tests\Feature;

use App\Services\CanonicalIdentityService;
use Mockery\MockInterface;
use Tests\TestCase;

class GatewayAuthenticationTest extends TestCase
{
    public function test_invalid_credentials_and_zero_eligible_roles_fail_safely(): void
    {
        $this->mock(CanonicalIdentityService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('authenticate')->once()->andReturn([]);
        });

        $this->from('/login')->post('/login', [
            'email' => 'unknown@example.test',
            'password' => 'incorrect',
        ])->assertRedirect('/login')
            ->assertSessionHasErrors('email')
            ->assertSessionMissing('gateway.identity');
    }

    public function test_one_role_logs_in_regenerates_state_and_selects_role(): void
    {
        $this->mock(CanonicalIdentityService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('authenticate')->once()->andReturn([
                'trainer' => $this->role('trainer', 'TR001', 'Trainer User'),
            ]);
        });

        $this->withSession(['pre_auth_marker' => 'must-be-removed'])
            ->post('/login', ['email' => 'trainer@example.test', 'password' => 'valid-password'])
            ->assertRedirect('/systems')
            ->assertSessionMissing('pre_auth_marker')
            ->assertSessionHas('gateway.identity.selected_role', 'trainer')
            ->assertSessionHas('gateway.identity.roles.trainer.id', 'TR001');
    }

    public function test_multiple_roles_require_server_validated_selection_and_support_switching(): void
    {
        $roles = [
            'staff_edu' => $this->role('staff_edu', 'ST001', 'Multi Role'),
            'trainer' => $this->role('trainer', 'TR001', 'Multi Role'),
        ];
        $this->mock(CanonicalIdentityService::class, function (MockInterface $mock) use ($roles): void {
            $mock->shouldReceive('authenticate')->once()->andReturn($roles);
        });

        $this->post('/login', ['email' => 'multi@example.test', 'password' => 'valid-password'])
            ->assertRedirect('/systems')
            ->assertSessionHas('gateway.identity', fn (array $identity): bool => array_key_exists('selected_role', $identity) && $identity['selected_role'] === null
            );

        $this->get('/systems')
            ->assertOk()
            ->assertSee('Select Role')
            ->assertSee('Staff EDU')
            ->assertSee('Trainer')
            ->assertSee('name="role"', false)
            ->assertSee('<option value="staff_edu"', false)
            ->assertSee('<option value="trainer"', false)
            ->assertDontSee('<option value="teacher"', false)
            ->assertDontSee('Role:')
            ->assertSee('Select a role to view your available systems.');
        $this->get('/roles')->assertRedirect('/systems');
        $this->post('/roles/select', ['role' => 'teacher'])->assertForbidden();
        $this->post('/roles/select', ['role' => 'staff_edu'])
            ->assertRedirect('/systems')
            ->assertSessionHas('gateway.identity.selected_role', 'staff_edu');

        $staffSystems = $this->get('/systems')
            ->assertOk()
            ->assertSee('Role:')
            ->assertSee('<option value="staff_edu" selected', false)
            ->assertSee('TrainHub')
            ->assertSee('Trainer Management System')
            ->assertSee('Teacher Employment System')
            ->assertDontSee('EduTrain')
            ->assertDontSee('Human Resources System');
        $this->assertSame(1, substr_count(strip_tags($staffSystems->getContent()), 'TrainHub'));
        $this->assertSame(1, substr_count(strip_tags($staffSystems->getContent()), 'Trainer Management System'));
        $this->assertSame(1, substr_count(strip_tags($staffSystems->getContent()), 'Teacher Employment System'));

        $this->post('/roles/select', ['role' => 'trainer'])
            ->assertRedirect('/systems')
            ->assertSessionHas('gateway.identity.selected_role', 'trainer');
        $trainerSystems = $this->get('/systems')
            ->assertOk()
            ->assertSee('Role:')
            ->assertSee('<option value="trainer" selected', false)
            ->assertSee('Trainer Management System')
            ->assertDontSee('TrainHub')
            ->assertDontSee('EduTrain');
        $this->assertSame(1, substr_count(strip_tags($trainerSystems->getContent()), 'Trainer Management System'));
    }

    public function test_protected_administrator_uses_only_authenticated_roles_and_normal_role_matrix(): void
    {
        config(['sso.administrator.email' => 'protected@example.test']);
        $roles = [
            'staff_edu' => $this->role('staff_edu', 'ST900', 'Protected User'),
            'trainer' => $this->role('trainer', 'TR900', 'Protected User'),
        ];
        $this->mock(CanonicalIdentityService::class, function (MockInterface $mock) use ($roles): void {
            $mock->shouldReceive('authenticate')->once()->andReturn($roles);
        });

        $this->post('/login', ['email' => 'PROTECTED@EXAMPLE.TEST', 'password' => 'valid-password'])
            ->assertRedirect('/systems')
            ->assertSessionHas('gateway.identity.is_administrator', true)
            ->assertSessionHas('gateway.identity.roles', fn (array $actual): bool => array_keys($actual) === ['staff_edu', 'trainer']);

        $this->get('/systems')
            ->assertOk()
            ->assertSee('Account:')
            ->assertSee('Administrator')
            ->assertDontSee('Role:')
            ->assertSee('Staff EDU')
            ->assertSee('Trainer')
            ->assertDontSee('Principal');

        $this->post('/roles/select', ['role' => 'hr'])->assertForbidden();
        $this->post('/roles/select', ['role' => 'trainer'])->assertRedirect('/systems');
        $this->get('/systems')
            ->assertSee('Account:')
            ->assertSee('Administrator')
            ->assertSee('Role:')
            ->assertSee('Trainer')
            ->assertSee('Trainer Management System')
            ->assertDontSee('TrainHub')
            ->assertDontSee('Human Resources System');

        $this->post('/roles/select', ['role' => 'staff_edu'])->assertRedirect('/systems');
        $this->get('/systems')
            ->assertSee('Account:')
            ->assertSee('Administrator')
            ->assertSee('Role:')
            ->assertSee('Staff EDU');
    }

    public function test_configured_administrator_email_never_grants_a_role(): void
    {
        config(['sso.administrator.email' => 'protected@example.test']);
        $this->mock(CanonicalIdentityService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('authenticate')->once()->andReturn([]);
        });

        $this->from('/login')->post('/login', [
            'email' => 'protected@example.test',
            'password' => 'not-a-canonical-password',
        ])->assertRedirect('/login')
            ->assertSessionHasErrors('email')
            ->assertSessionMissing('gateway.identity');
    }

    public function test_login_is_throttled_by_email_and_ip(): void
    {
        $this->mock(CanonicalIdentityService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('authenticate')->andReturn([]);
        });

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->post('/login', ['email' => 'rate@example.test', 'password' => 'incorrect'])
                ->assertRedirect();
        }
        $this->post('/login', ['email' => 'rate@example.test', 'password' => 'incorrect'])
            ->assertTooManyRequests();
    }

    public function test_hr_and_teacher_multi_role_switches_role_first_systems_without_another_password(): void
    {
        $roles = [
            'hr' => $this->role('hr', 'HR001', 'HR Teacher'),
            'teacher' => $this->role('teacher', 'TC001', 'HR Teacher'),
        ];
        $this->mock(CanonicalIdentityService::class, function (MockInterface $mock) use ($roles): void {
            $mock->shouldReceive('authenticate')->once()->andReturn($roles);
        });

        $this->post('/login', ['email' => 'hr.teacher@example.test', 'password' => 'valid-password'])
            ->assertRedirect('/systems');

        $this->post('/roles/select', ['role' => 'hr'])->assertRedirect('/systems');
        $hrSystems = $this->get('/systems')
            ->assertOk()
            ->assertSee('Teacher Employment System')
            ->assertSee('Human Resources System')
            ->assertSee('EduTrain')
            ->assertDontSee('Trainer Management System')
            ->assertDontSee('TrainHub');
        $this->assertSame(1, substr_count(strip_tags($hrSystems->getContent()), 'EduTrain'));

        $this->post('/roles/select', ['role' => 'teacher'])->assertRedirect('/systems');
        $teacherSystems = $this->get('/systems')
            ->assertOk()
            ->assertSee('EduTrain')
            ->assertDontSee('Teacher Employment System')
            ->assertDontSee('Human Resources System')
            ->assertDontSee('Trainer Management System')
            ->assertDontSee('TrainHub');
        $this->assertSame(1, substr_count(strip_tags($teacherSystems->getContent()), 'EduTrain'));
    }

    public function test_logout_invalidates_gateway_authority(): void
    {
        $this->withSession(['gateway.identity' => $this->sessionIdentity('trainer')])
            ->post('/logout')
            ->assertRedirect('/login')
            ->assertSessionMissing('gateway.identity');

        $this->post('/systems/aidid/launch')->assertRedirect('/login');
    }

    private function role(string $role, string $id, string $name): array
    {
        return [
            'role' => $role,
            'source' => $role,
            'id' => $id,
            'name' => $name,
            'email' => strtolower(str_replace(' ', '.', $name)).'@example.test',
        ];
    }

    private function sessionIdentity(string $selected): array
    {
        $role = $this->role($selected, 'ID001', 'Portal User');

        return ['email' => $role['email'], 'name' => $role['name'], 'roles' => [$selected => $role], 'selected_role' => $selected];
    }
}
