<?php

namespace Tests\Feature;

use App\Services\AdministratorProvisioningAuditService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Mockery\MockInterface;
use Tests\TestCase;

class AdministratorAuditCommandTest extends TestCase
{
    public function test_all_roles_ready_returns_success(): void
    {
        $this->mockAudit($this->auditRows());

        $this->artisan('elit:audit-administrator', ['--no-interaction' => true])
            ->expectsOutputToContain('Teacher')
            ->expectsOutputToContain('HR')
            ->expectsOutputToContain('The Administrator is fully ready for login.')
            ->assertSuccessful();
    }

    public function test_one_missing_role_returns_failure_after_printing_complete_audit(): void
    {
        $this->mockAudit($this->auditRows(['hr' => 'missing']));

        $this->artisan('elit:audit-administrator', ['--no-interaction' => true])
            ->expectsOutputToContain('Teacher')
            ->expectsOutputToContain('HR')
            ->expectsOutputToContain('missing')
            ->expectsOutputToContain('The Administrator is NOT fully ready for login.')
            ->assertFailed();
    }

    public function test_one_not_login_ready_role_returns_failure(): void
    {
        $this->mockAudit($this->auditRows(['hr' => 'not login-ready']));

        $this->artisan('elit:audit-administrator', ['--no-interaction' => true])
            ->expectsOutputToContain('not login-ready')
            ->expectsOutputToContain('The Administrator is NOT fully ready for login.')
            ->assertFailed();
    }

    public function test_conflict_returns_failure(): void
    {
        $this->mockAudit($this->auditRows(['teacher' => 'conflict']));

        $this->artisan('elit:audit-administrator', ['--no-interaction' => true])
            ->expectsOutputToContain('conflict')
            ->expectsOutputToContain('The Administrator is NOT fully ready for login.')
            ->assertFailed();
    }

    public function test_audit_reports_existing_and_missing_roles_without_writing_data(): void
    {
        config(['sso.administrator.email' => 'AUDIT@EXAMPLE.TEST']);
        Schema::create('staff_edu', function (Blueprint $table): void {
            $table->string('staffID')->primary();
            $table->string('staffName');
            $table->string('email');
            $table->string('password');
            $table->string('status');
        });
        DB::table('staff_edu')->insert([
            'staffID' => 'ST900', 'staffName' => 'Audit Account', 'email' => 'audit@example.test',
            'password' => Hash::make('unchanged-password'), 'status' => 'active',
        ]);
        $before = DB::table('staff_edu')->where('staffID', 'ST900')->value('password');

        $this->artisan('elit:audit-administrator', ['--no-interaction' => true])
            ->expectsOutputToContain('ELIT Portal Administrator audit (read-only)')
            ->expectsOutputToContain('No canonical records, passwords, assignments, or relationships were created or changed.')
            ->expectsOutputToContain('The Administrator is NOT fully ready for login.')
            ->assertFailed();

        $this->assertSame(1, DB::table('staff_edu')->count());
        $this->assertSame($before, DB::table('staff_edu')->where('staffID', 'ST900')->value('password'));
    }

    public function test_noninteractive_audit_requires_configured_email(): void
    {
        config(['sso.administrator.email' => '']);

        $this->artisan('elit:audit-administrator', ['--no-interaction' => true])
            ->expectsOutputToContain('Configure a valid ELIT_ADMIN_EMAIL')
            ->assertFailed();
    }

    /**
     * @param  array<int, array{role: string, label: string, status: string, requirements: string, workflow: string}>  $rows
     */
    private function mockAudit(array $rows): void
    {
        config(['sso.administrator.email' => 'audit@example.test']);

        $this->mock(AdministratorProvisioningAuditService::class, function (MockInterface $mock) use ($rows): void {
            $mock->shouldReceive('audit')
                ->once()
                ->with('audit@example.test')
                ->andReturn($rows);
        });
    }

    /**
     * @param  array<string, string>  $statuses
     * @return array<int, array{role: string, label: string, status: string, requirements: string, workflow: string}>
     */
    private function auditRows(array $statuses = []): array
    {
        return collect(config('sso.roles'))
            ->map(fn (array $configuration, string $role): array => $this->auditRow(
                $role,
                $configuration['label'],
                $statuses[$role] ?? 'ready for login',
            ))
            ->values()
            ->all();
    }

    /**
     * @return array{role: string, label: string, status: string, requirements: string, workflow: string}
     */
    private function auditRow(string $role, string $label, string $status): array
    {
        return [
            'role' => $role,
            'label' => $label,
            'status' => $status,
            'requirements' => 'Audit requirement.',
            'workflow' => 'Normal workflow.',
        ];
    }
}
