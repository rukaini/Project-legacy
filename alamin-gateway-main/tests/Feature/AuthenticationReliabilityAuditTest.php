<?php

namespace Tests\Feature;

use App\Services\CanonicalIdentityService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AuthenticationReliabilityAuditTest extends TestCase
{
    private const PASSWORD = 'RolePassword123!';

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('staff_edu', function (Blueprint $table): void {
            $table->string('staffID');
            $table->string('staffName');
            $table->string('email');
            $table->string('password');
            $table->string('status')->nullable();
        });
        Schema::create('teacher', function (Blueprint $table): void {
            $table->string('teacherID')->primary();
            $table->string('teacherName');
            $table->string('email');
            $table->string('password');
            $table->string('role');
        });
        Schema::create('assign', function (Blueprint $table): void {
            $table->string('schoolID');
            $table->string('teacherID');
            $table->date('assignDate')->nullable();
            $table->string('status')->nullable();
        });
        Schema::create('observer', function (Blueprint $table): void {
            $table->string('observerID')->primary();
            $table->string('teacherID');
            $table->string('status')->nullable();
        });
        Schema::create('external_observer', function (Blueprint $table): void {
            $table->string('externalObserverID')->primary();
            $table->string('teacherID');
            $table->string('status')->nullable();
        });
        Schema::create('guru_new', function (Blueprint $table): void {
            $table->string('gn_id')->primary();
            $table->string('gn_name');
            $table->string('email');
            $table->string('password');
            $table->string('role');
            $table->string('current_status')->nullable();
        });
        Schema::create('principal', function (Blueprint $table): void {
            $table->string('principalID')->primary();
            $table->string('principalName');
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
            $table->string('phone_number')->nullable();
            $table->string('role');
            $table->boolean('password_change_required')->default(false);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
        Schema::create('trainer', function (Blueprint $table): void {
            $table->string('trainerID')->primary();
            $table->string('trainerName');
            $table->string('trainerEmail');
            $table->string('trainerPassword');
            $table->string('status')->nullable();
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

        DB::table('roles')->insert(['id' => 9, 'name' => 'outsider', 'guard_name' => 'web']);
    }

    public function test_every_supported_role_accepts_only_its_existing_record_and_correct_password(): void
    {
        foreach ($this->supportedRoles() as $index => $role) {
            $email = "{$role}@example.test";
            $this->insertRole($role, $index + 1, $email);

            $valid = app(CanonicalIdentityService::class)->authenticate('  '.strtoupper($email).'  ', self::PASSWORD);
            $wrong = app(CanonicalIdentityService::class)->authenticate($email, 'WrongPassword123!');
            $missing = app(CanonicalIdentityService::class)->authenticate("missing-{$role}@example.test", self::PASSWORD);

            $this->assertArrayHasKey($role, $valid, "{$role} must authenticate with its canonical credential.");
            $this->assertArrayNotHasKey($role, $wrong, "{$role} must reject a wrong password.");
            $this->assertArrayNotHasKey($role, $missing, "{$role} must reject a nonexistent identity.");
        }
    }

    public function test_only_roles_with_real_activity_concepts_are_blocked_as_inactive(): void
    {
        foreach (['staff_edu', 'teacher', 'new_teacher', 'principal', 'trainer', 'observer', 'external_observer'] as $index => $role) {
            $email = "inactive-{$role}@example.test";
            $this->insertRole($role, $index + 20, $email, false);

            $roles = app(CanonicalIdentityService::class)->authenticate($email, self::PASSWORD);

            $this->assertArrayNotHasKey($role, $roles, "{$role} must be blocked by its canonical activity rule.");
        }
    }

    public function test_exact_hr_schema_has_no_status_dependency_and_zero_change_flag_is_not_activity(): void
    {
        DB::table('hr_administrator')->insert([
            'hrid' => 'HR0006',
            'username' => 'hr_alamin_6',
            'email' => 'hr6@trainhub.test',
            'password' => Hash::make(self::PASSWORD),
            'phone_number' => null,
            'role' => 'hr',
            'password_change_required' => 0,
        ]);

        $roles = app(CanonicalIdentityService::class)->authenticate(' HR6@TRAINHUB.TEST ', self::PASSWORD);

        $this->assertSame(['hr'], array_keys($roles));
        $this->assertSame('HR0006', $roles['hr']['id']);
        $this->assertSame('hr_alamin_6', $roles['hr']['name']);
    }

    public function test_invalid_canonical_role_values_and_missing_public_assignment_are_rejected(): void
    {
        foreach (['teacher', 'new_teacher', 'principal', 'hr'] as $index => $role) {
            $email = "wrong-role-{$role}@example.test";
            $this->insertRole($role, $index + 40, $email, true, 'not_'.$role);

            $this->assertArrayNotHasKey(
                $role,
                app(CanonicalIdentityService::class)->authenticate($email, self::PASSWORD),
            );
        }

        $userId = DB::table('users')->insertGetId([
            'name' => 'Unassigned Public',
            'email' => 'unassigned-public@example.test',
            'password' => Hash::make(self::PASSWORD),
        ]);
        DB::table('outsider')->insert(['user_id' => $userId, 'name' => 'Unassigned Public']);

        $this->assertArrayNotHasKey(
            'outsider',
            app(CanonicalIdentityService::class)->authenticate('unassigned-public@example.test', self::PASSWORD),
        );
    }

    public function test_duplicate_email_inside_one_source_fails_closed_but_cross_role_email_is_independent(): void
    {
        $email = 'shared@example.test';
        DB::table('staff_edu')->insert([
            ['staffID' => 'ST70', 'staffName' => 'Duplicate One', 'email' => $email, 'password' => Hash::make(self::PASSWORD), 'status' => 'active'],
            ['staffID' => 'ST71', 'staffName' => 'Duplicate Two', 'email' => $email, 'password' => Hash::make(self::PASSWORD), 'status' => 'active'],
        ]);
        DB::table('trainer')->insert([
            'trainerID' => 'TR70', 'trainerName' => 'Independent Trainer', 'trainerEmail' => $email,
            'trainerPassword' => Hash::make(self::PASSWORD), 'status' => 'active',
        ]);

        $roles = app(CanonicalIdentityService::class)->authenticate($email, self::PASSWORD);

        $this->assertArrayNotHasKey('staff_edu', $roles);
        $this->assertSame(['trainer'], array_keys($roles));
    }

    public function test_unconfigured_roles_are_never_exposed_by_login(): void
    {
        $this->insertRole('hr', 80, 'configured-filter@example.test');
        config(['sso.roles.hr' => null]);

        $this->assertSame([], app(CanonicalIdentityService::class)->authenticate(
            'configured-filter@example.test',
            self::PASSWORD,
        ));
    }

    /** @return array<int, string> */
    private function supportedRoles(): array
    {
        return [
            'staff_edu', 'teacher', 'new_teacher', 'principal', 'hr',
            'trainer', 'observer', 'external_observer', 'outsider',
        ];
    }

    private function insertRole(
        string $role,
        int $sequence,
        string $email,
        bool $active = true,
        ?string $roleValue = null,
    ): void {
        $status = $active ? 'active' : 'inactive';
        $hash = Hash::make(self::PASSWORD);

        match ($role) {
            'staff_edu' => DB::table('staff_edu')->insert([
                'staffID' => "ST{$sequence}", 'staffName' => 'Staff EDU', 'email' => $email,
                'password' => $hash, 'status' => $status,
            ]),
            'teacher' => $this->insertTeacherRole($sequence, $email, $hash, $status, $roleValue, null),
            'observer' => $this->insertTeacherRole($sequence, $email, $hash, 'active', 'teacher', $status, 'observer'),
            'external_observer' => $this->insertTeacherRole(
                $sequence,
                $email,
                $hash,
                'active',
                'teacher',
                $status,
                'external_observer',
            ),
            'new_teacher' => DB::table('guru_new')->insert([
                'gn_id' => "GN{$sequence}", 'gn_name' => 'New Teacher', 'email' => $email,
                'password' => $hash, 'role' => $roleValue ?? 'new_teacher', 'current_status' => $status,
            ]),
            'principal' => DB::table('principal')->insert([
                'principalID' => "PR{$sequence}", 'principalName' => 'Principal', 'email' => $email,
                'password' => $hash, 'role' => $roleValue ?? 'principal', 'status' => $status,
            ]),
            'hr' => DB::table('hr_administrator')->insert([
                'hrid' => "HR{$sequence}", 'username' => 'HR', 'email' => $email, 'password' => $hash,
                'role' => $roleValue ?? 'hr', 'password_change_required' => 0,
            ]),
            'trainer' => DB::table('trainer')->insert([
                'trainerID' => "TR{$sequence}", 'trainerName' => 'Trainer', 'trainerEmail' => $email,
                'trainerPassword' => $hash, 'status' => $status,
            ]),
            'outsider' => $this->insertPublicRole($email, $hash),
            default => throw new \InvalidArgumentException("Unknown role {$role}"),
        };
    }

    private function insertTeacherRole(
        int $sequence,
        string $email,
        string $hash,
        string $assignmentStatus,
        ?string $roleValue,
        ?string $linkedStatus,
        ?string $linkedRole = null,
    ): void {
        $teacherId = "TC{$sequence}";
        DB::table('teacher')->insert([
            'teacherID' => $teacherId,
            'teacherName' => 'Teacher',
            'email' => $email,
            'password' => $hash,
            'role' => $roleValue ?? 'teacher',
        ]);
        DB::table('assign')->insert([
            'schoolID' => "SC{$sequence}",
            'teacherID' => $teacherId,
            'assignDate' => '2026-01-01',
            'status' => $assignmentStatus,
        ]);

        if ($linkedRole === 'observer') {
            DB::table('observer')->insert([
                'observerID' => "OB{$sequence}", 'teacherID' => $teacherId, 'status' => $linkedStatus,
            ]);
        } elseif ($linkedRole === 'external_observer') {
            DB::table('external_observer')->insert([
                'externalObserverID' => "EO{$sequence}", 'teacherID' => $teacherId, 'status' => $linkedStatus,
            ]);
        }
    }

    private function insertPublicRole(string $email, string $hash): void
    {
        $userId = DB::table('users')->insertGetId([
            'name' => 'Public User', 'email' => $email, 'password' => $hash,
        ]);
        DB::table('outsider')->insert(['user_id' => $userId, 'name' => 'Public User']);
        DB::table('model_has_roles')->insert([
            'role_id' => 9, 'model_type' => 'App\\Models\\User', 'model_id' => $userId,
        ]);
    }
}
