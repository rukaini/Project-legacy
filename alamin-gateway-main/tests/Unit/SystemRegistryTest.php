<?php

namespace Tests\Unit;

use App\Services\SystemRegistry;
use Tests\TestCase;

class SystemRegistryTest extends TestCase
{
    public function test_deployer_environment_uses_alamin_login_secret_names_only(): void
    {
        $example = file_get_contents(base_path('.env.example'));
        $configuration = file_get_contents(config_path('sso.php'));

        foreach (['AIDID', 'NUREEN', 'SYIQIN', 'IZZ', 'TYA'] as $system) {
            $this->assertStringContainsString("ALAMIN_LOGIN_CLIENT_SECRET_{$system}=", $example);
            $this->assertStringContainsString("env('ALAMIN_LOGIN_CLIENT_SECRET_{$system}')", $configuration);
        }

        $this->assertStringContainsString('ALAMIN_LOGIN_REQUIRE_HTTPS=true', $example);
        $this->assertStringContainsString("\$enabled('ALAMIN_LOGIN_REQUIRE_HTTPS', true)", $configuration);
        $this->assertStringContainsString('ELIT_ADMIN_EMAIL=', $example);
        $this->assertStringContainsString("env('ELIT_ADMIN_EMAIL', '')", $configuration);
    }

    public function test_system_directory_contains_only_the_five_real_applications(): void
    {
        $systems = config('sso.systems');

        $this->assertSame(['aidid', 'nureen', 'syiqin', 'izz', 'tya'], array_keys($systems));
        $this->assertSame([
            'aidid' => 'Trainer Management System',
            'nureen' => 'TrainHub',
            'syiqin' => 'Teacher Employment System',
            'izz' => 'EduTrain',
            'tya' => 'Human Resources System',
        ], array_map(
            fn (array $system): string => $system['display_name'],
            $systems,
        ));
        foreach ($systems as $system) {
            $this->assertArrayNotHasKey('name', $system);
            $this->assertArrayNotHasKey('application_name', $system);
        }
    }

    public function test_role_access_matrix_is_exact(): void
    {
        $registry = app(SystemRegistry::class);
        $expected = [
            'teacher' => ['izz'],
            'new_teacher' => ['syiqin', 'izz'],
            'outsider' => ['izz'],
            'principal' => ['syiqin', 'izz'],
            'staff_edu' => ['nureen', 'aidid', 'syiqin'],
            'hr' => ['syiqin', 'tya', 'izz'],
            'trainer' => ['aidid'],
            'observer' => ['syiqin'],
            'external_observer' => ['syiqin'],
        ];
        $expectedLabels = [
            'teacher' => 'Teacher',
            'new_teacher' => 'New Teacher',
            'outsider' => 'Public',
            'principal' => 'Principal',
            'staff_edu' => 'Staff EDU',
            'hr' => 'HR',
            'trainer' => 'Trainer',
            'observer' => 'Observer',
            'external_observer' => 'External Observer',
        ];

        foreach ($expected as $role => $systems) {
            $this->assertSame($expectedLabels[$role], $registry->role($role)['label']);
            $this->assertSame($systems, array_keys($registry->systemsFor($role)));
            foreach (['aidid', 'nureen', 'syiqin', 'izz', 'tya'] as $system) {
                $this->assertSame(
                    in_array($system, $systems, true),
                    $registry->allows($role, $system),
                    $role.' access mismatch for '.$system,
                );
            }
        }
        $this->assertArrayHasKey('outsider', config('sso.roles'));
        $this->assertArrayNotHasKey('public', config('sso.roles'));
        $this->assertSame(['izz'], config('sso.roles.outsider.systems'));
        $this->assertFalse($registry->allows('teacher', 'aidid'));
        $this->assertFalse($registry->allows('trainer', 'izz'));
        $this->assertFalse($registry->allows('hr', 'aidid'));
        $this->assertTrue($registry->allows('hr', 'izz'));
        $this->assertTrue($registry->allows('staff_edu', 'aidid'));
        $this->assertTrue($registry->allows('staff_edu', 'syiqin'));
        $this->assertFalse($registry->allows('staff_edu', 'izz'));
        $this->assertFalse($registry->allows('teacher', 'unknown'));
        $this->assertFalse($registry->allows('unknown', 'izz'));
    }
}
