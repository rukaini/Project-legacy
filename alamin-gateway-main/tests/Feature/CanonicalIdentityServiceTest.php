<?php

namespace Tests\Feature;

use App\Services\CanonicalIdentityService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CanonicalIdentityServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
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

    public function test_same_email_can_resolve_two_independently_authenticated_roles(): void
    {
        $hash = password_hash('shared-valid-password', PASSWORD_BCRYPT);
        DB::table('staff_edu')->insert([
            'staffID' => 'ST001', 'staffName' => 'Multi Role', 'email' => 'multi@example.test', 'password' => $hash, 'status' => 'active',
        ]);
        DB::table('trainer')->insert([
            'trainerID' => 'TR001', 'trainerName' => 'Multi Role', 'trainerEmail' => 'multi@example.test', 'trainerPassword' => $hash, 'status' => 'Active',
        ]);

        $roles = app(CanonicalIdentityService::class)->authenticate(' MULTI@example.test ', 'shared-valid-password');
        $this->assertSame(['staff_edu', 'trainer'], array_keys($roles));
        $this->assertSame('ST001', $roles['staff_edu']['id']);
        $this->assertSame('TR001', $roles['trainer']['id']);
    }

    public function test_different_password_sources_are_not_merged_and_inactive_rows_are_excluded(): void
    {
        DB::table('staff_edu')->insert([
            'staffID' => 'ST002', 'staffName' => 'Staff Only', 'email' => 'overlap@example.test',
            'password' => password_hash('staff-password', PASSWORD_BCRYPT), 'status' => 'active',
        ]);
        DB::table('trainer')->insert([
            'trainerID' => 'TR002', 'trainerName' => 'Trainer Other Password', 'trainerEmail' => 'overlap@example.test',
            'trainerPassword' => password_hash('trainer-password', PASSWORD_BCRYPT), 'status' => 'active',
        ]);
        DB::table('trainer')->insert([
            'trainerID' => 'TR003', 'trainerName' => 'Inactive', 'trainerEmail' => 'inactive@example.test',
            'trainerPassword' => password_hash('inactive-password', PASSWORD_BCRYPT), 'status' => 'inactive',
        ]);

        $staffRoles = app(CanonicalIdentityService::class)->authenticate('overlap@example.test', 'staff-password');
        $this->assertSame(['staff_edu'], array_keys($staffRoles));
        $trainerRoles = app(CanonicalIdentityService::class)->authenticate('overlap@example.test', 'trainer-password');
        $this->assertSame(['trainer'], array_keys($trainerRoles));
        $this->assertSame([], app(CanonicalIdentityService::class)->authenticate('inactive@example.test', 'inactive-password'));
        $this->assertSame([], app(CanonicalIdentityService::class)->authenticate('overlap@example.test', 'wrong-password'));
    }

    public function test_plaintext_canonical_passwords_are_never_accepted(): void
    {
        DB::table('staff_edu')->insert([
            'staffID' => 'ST003', 'staffName' => 'Legacy', 'email' => 'legacy@example.test',
            'password' => 'plaintext-password', 'status' => 'active',
        ]);

        $this->assertSame([], app(CanonicalIdentityService::class)->authenticate('legacy@example.test', 'plaintext-password'));
    }

    public function test_all_configured_overlapping_canonical_roles_are_collected(): void
    {
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
            $table->string('email');
            $table->string('password');
            $table->string('role');
            $table->string('current_status');
        });
        Schema::create('principal', function (Blueprint $table): void {
            $table->string('principalID')->primary();
            $table->string('principalName');
            $table->string('email');
            $table->string('password');
            $table->string('role');
            $table->string('status');
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
            $table->string('name');
        });
        $this->createPublicRoleTables();

        $email = 'all.roles@example.test';
        $hash = password_hash('all-roles-password', PASSWORD_BCRYPT);
        DB::table('staff_edu')->insert(['staffID' => 'ST009', 'staffName' => 'All Roles', 'email' => $email, 'password' => $hash, 'status' => 'active']);
        DB::table('teacher')->insert(['teacherID' => 'TC009', 'teacherName' => 'All Roles', 'email' => $email, 'password' => $hash, 'role' => 'teacher']);
        DB::table('assign')->insert(['schoolID' => 'SC009', 'teacherID' => 'TC009', 'assignDate' => '2026-01-01', 'status' => 'active']);
        DB::table('observer')->insert(['observerID' => 'OB009', 'teacherID' => 'TC009', 'status' => 'active']);
        DB::table('external_observer')->insert(['externalObserverID' => 'EO009', 'teacherID' => 'TC009', 'status' => 'aktif']);
        DB::table('guru_new')->insert(['gn_id' => 'GN009', 'gn_name' => 'All Roles', 'email' => $email, 'password' => $hash, 'role' => 'new_teacher', 'current_status' => 'Active']);
        DB::table('principal')->insert(['principalID' => 'PR009', 'principalName' => 'All Roles', 'email' => $email, 'password' => $hash, 'role' => 'principal', 'status' => 'active']);
        DB::table('hr_administrator')->insert(['hrid' => 'HR009', 'username' => 'All Roles', 'email' => $email, 'password' => $hash, 'role' => 'hr']);
        DB::table('trainer')->insert(['trainerID' => 'TR009', 'trainerName' => 'All Roles', 'trainerEmail' => $email, 'trainerPassword' => $hash, 'status' => 'active']);
        $userId = DB::table('users')->insertGetId(['name' => 'All Roles', 'email' => $email, 'password' => $hash]);
        DB::table('outsider')->insert(['user_id' => $userId, 'name' => 'All Roles']);
        $this->assignPublicRole($userId);

        $roles = app(CanonicalIdentityService::class)->authenticate($email, 'all-roles-password');
        $this->assertSame([
            'staff_edu', 'teacher', 'observer', 'external_observer', 'new_teacher',
            'principal', 'hr', 'trainer', 'outsider',
        ], array_keys($roles));
        $this->assertSame('HR009', $roles['hr']['id']);
        $this->assertSame('All Roles', $roles['hr']['name']);
    }

    public function test_staff_edu_requires_a_normalized_active_status(): void
    {
        foreach ($this->statusCases() as $index => [$label, $status, $eligible]) {
            $email = "staff-status-{$index}@example.test";
            DB::table('staff_edu')->insert([
                'staffID' => 'ST'.($index + 100),
                'staffName' => 'Staff '.$label,
                'email' => $email,
                'password' => password_hash('staff-status-password', PASSWORD_BCRYPT),
                'status' => $status,
            ]);

            $roles = app(CanonicalIdentityService::class)->authenticate($email, 'staff-status-password');
            $this->assertSame($eligible, isset($roles['staff_edu']), $label);
        }
    }

    public function test_trainer_requires_a_normalized_active_status(): void
    {
        foreach ($this->statusCases() as $index => [$label, $status, $eligible]) {
            $email = "trainer-status-{$index}@example.test";
            DB::table('trainer')->insert([
                'trainerID' => 'TR'.($index + 100),
                'trainerName' => 'Trainer '.$label,
                'trainerEmail' => $email,
                'trainerPassword' => password_hash('trainer-status-password', PASSWORD_BCRYPT),
                'status' => $status,
            ]);

            $roles = app(CanonicalIdentityService::class)->authenticate($email, 'trainer-status-password');
            $this->assertSame($eligible, isset($roles['trainer']), $label);
        }
    }

    public function test_teacher_latest_assignment_requires_a_normalized_active_status(): void
    {
        $this->createTeacherTable();
        $this->createAssignTable();
        $cases = [
            ['active', 'active', true],
            ['aktif', ' Aktif ', true],
            ['inactive', 'inactive', false],
            ['berhenti', ' Berhenti ', false],
            ['tidak aktif', 'Tidak Aktif', false],
            ['blank', '', false],
            ['null', null, false],
            ['unknown', 'pending', false],
        ];

        foreach ($cases as $index => [$label, $status, $eligible]) {
            $teacherId = 'TC'.($index + 100);
            $email = "teacher-status-{$index}@example.test";
            $this->insertTeacher($teacherId, $email, 'teacher-status-password');
            DB::table('assign')->insert([
                'schoolID' => 'SC'.($index + 100),
                'teacherID' => $teacherId,
                'assignDate' => '2026-01-01',
                'status' => $status,
            ]);

            $roles = app(CanonicalIdentityService::class)->authenticate($email, 'teacher-status-password');
            $this->assertSame($eligible, isset($roles['teacher']), $label);
        }
    }

    public function test_teacher_without_an_assignment_preserves_current_eligibility(): void
    {
        $this->createTeacherTable();
        $this->createAssignTable();
        $this->insertTeacher('TC200', 'teacher-no-assignment@example.test', 'teacher-password');

        $roles = app(CanonicalIdentityService::class)->authenticate(
            'teacher-no-assignment@example.test',
            'teacher-password',
        );

        $this->assertArrayHasKey('teacher', $roles);
    }

    public function test_teacher_fails_closed_when_assign_table_is_missing(): void
    {
        $this->createTeacherTable();
        $this->insertTeacher('TC201', 'teacher-missing-assign@example.test', 'teacher-password');

        $roles = app(CanonicalIdentityService::class)->authenticate(
            'teacher-missing-assign@example.test',
            'teacher-password',
        );

        $this->assertArrayNotHasKey('teacher', $roles);
    }

    public function test_teacher_fails_closed_when_assign_required_columns_are_missing(): void
    {
        $this->createTeacherTable();
        Schema::create('assign', function (Blueprint $table): void {
            $table->string('schoolID');
            $table->string('teacherID');
            $table->date('assignDate')->nullable();
            $table->primary(['schoolID', 'teacherID']);
        });
        $this->insertTeacher('TC202', 'teacher-incomplete-assign@example.test', 'teacher-password');

        $roles = app(CanonicalIdentityService::class)->authenticate(
            'teacher-incomplete-assign@example.test',
            'teacher-password',
        );

        $this->assertArrayNotHasKey('teacher', $roles);
    }

    public function test_teacher_uses_the_latest_assignment_status(): void
    {
        $this->createTeacherTable();
        $this->createAssignTable();
        $this->insertTeacher('TC210', 'teacher-newer-active@example.test', 'teacher-password');
        $this->insertTeacher('TC211', 'teacher-newer-inactive@example.test', 'teacher-password');
        DB::table('assign')->insert([
            ['schoolID' => 'SC210A', 'teacherID' => 'TC210', 'assignDate' => '2025-01-01', 'status' => 'inactive'],
            ['schoolID' => 'SC210B', 'teacherID' => 'TC210', 'assignDate' => '2026-01-01', 'status' => 'active'],
            ['schoolID' => 'SC211A', 'teacherID' => 'TC211', 'assignDate' => '2025-01-01', 'status' => 'active'],
            ['schoolID' => 'SC211B', 'teacherID' => 'TC211', 'assignDate' => '2026-01-01', 'status' => 'inactive'],
        ]);

        $active = app(CanonicalIdentityService::class)->authenticate(
            'teacher-newer-active@example.test',
            'teacher-password',
        );
        $inactive = app(CanonicalIdentityService::class)->authenticate(
            'teacher-newer-inactive@example.test',
            'teacher-password',
        );

        $this->assertArrayHasKey('teacher', $active);
        $this->assertArrayNotHasKey('teacher', $inactive);
    }

    public function test_new_teacher_requires_a_normalized_active_status(): void
    {
        Schema::create('guru_new', function (Blueprint $table): void {
            $table->string('gn_id')->primary();
            $table->string('gn_name');
            $table->string('email');
            $table->string('password');
            $table->string('role');
            $table->string('current_status')->nullable();
        });

        foreach ($this->statusCases() as $index => [$label, $status, $eligible]) {
            $email = "new-teacher-status-{$index}@example.test";
            DB::table('guru_new')->insert([
                'gn_id' => 'GN'.($index + 100),
                'gn_name' => 'New Teacher '.$label,
                'email' => $email,
                'password' => password_hash('new-teacher-password', PASSWORD_BCRYPT),
                'role' => 'new_teacher',
                'current_status' => $status,
            ]);

            $roles = app(CanonicalIdentityService::class)->authenticate($email, 'new-teacher-password');
            $this->assertSame($eligible, isset($roles['new_teacher']), $label);
        }
    }

    public function test_principal_requires_a_normalized_active_status(): void
    {
        Schema::create('principal', function (Blueprint $table): void {
            $table->string('principalID')->primary();
            $table->string('principalName');
            $table->string('email');
            $table->string('password');
            $table->string('role');
            $table->string('status')->nullable();
        });
        $cases = array_merge($this->statusCases(), [['berhenti', ' Berhenti ', false]]);

        foreach ($cases as $index => [$label, $status, $eligible]) {
            $email = "principal-status-{$index}@example.test";
            DB::table('principal')->insert([
                'principalID' => 'PR'.($index + 100),
                'principalName' => 'Principal '.$label,
                'email' => $email,
                'password' => password_hash('principal-password', PASSWORD_BCRYPT),
                'role' => 'principal',
                'status' => $status,
            ]);

            $roles = app(CanonicalIdentityService::class)->authenticate($email, 'principal-password');
            $this->assertSame($eligible, isset($roles['principal']), $label);
        }
    }

    public function test_observer_requires_both_linked_and_teacher_owner_activity(): void
    {
        $this->createTeacherTable();
        $this->createAssignTable();
        Schema::create('observer', function (Blueprint $table): void {
            $table->string('observerID')->primary();
            $table->string('teacherID');
            $table->string('status')->nullable();
        });

        $this->insertLinkedTeacher('TC300', 'observer-active@example.test', 'active');
        $this->insertLinkedTeacher('TC301', 'observer-inactive@example.test', 'active');
        $this->insertLinkedTeacher('TC302', 'observer-owner-inactive@example.test', 'inactive');
        DB::table('observer')->insert([
            ['observerID' => 'OB300', 'teacherID' => 'TC300', 'status' => ' active '],
            ['observerID' => 'OB301', 'teacherID' => 'TC301', 'status' => 'inactive'],
            ['observerID' => 'OB302', 'teacherID' => 'TC302', 'status' => 'active'],
        ]);

        $active = app(CanonicalIdentityService::class)->authenticate('observer-active@example.test', 'teacher-password');
        $linkedInactive = app(CanonicalIdentityService::class)->authenticate('observer-inactive@example.test', 'teacher-password');
        $ownerInactive = app(CanonicalIdentityService::class)->authenticate('observer-owner-inactive@example.test', 'teacher-password');

        $this->assertArrayHasKey('observer', $active);
        $this->assertArrayNotHasKey('observer', $linkedInactive);
        $this->assertArrayHasKey('teacher', $linkedInactive);
        $this->assertArrayNotHasKey('observer', $ownerInactive);
        $this->assertArrayNotHasKey('teacher', $ownerInactive);
    }

    public function test_external_observer_requires_both_linked_and_teacher_owner_activity(): void
    {
        $this->createTeacherTable();
        $this->createAssignTable();
        Schema::create('external_observer', function (Blueprint $table): void {
            $table->string('externalObserverID')->primary();
            $table->string('teacherID');
            $table->string('status')->nullable();
        });

        $this->insertLinkedTeacher('TC310', 'external-active@example.test', 'Aktif');
        $this->insertLinkedTeacher('TC311', 'external-inactive@example.test', 'active');
        $this->insertLinkedTeacher('TC312', 'external-owner-inactive@example.test', 'Berhenti');
        DB::table('external_observer')->insert([
            ['externalObserverID' => 'EO310', 'teacherID' => 'TC310', 'status' => ' Aktif '],
            ['externalObserverID' => 'EO311', 'teacherID' => 'TC311', 'status' => 'inactive'],
            ['externalObserverID' => 'EO312', 'teacherID' => 'TC312', 'status' => 'active'],
        ]);

        $active = app(CanonicalIdentityService::class)->authenticate('external-active@example.test', 'teacher-password');
        $linkedInactive = app(CanonicalIdentityService::class)->authenticate('external-inactive@example.test', 'teacher-password');
        $ownerInactive = app(CanonicalIdentityService::class)->authenticate('external-owner-inactive@example.test', 'teacher-password');

        $this->assertArrayHasKey('external_observer', $active);
        $this->assertArrayNotHasKey('external_observer', $linkedInactive);
        $this->assertArrayHasKey('teacher', $linkedInactive);
        $this->assertArrayNotHasKey('external_observer', $ownerInactive);
        $this->assertArrayNotHasKey('teacher', $ownerInactive);
    }

    public function test_inactive_role_is_omitted_while_an_independently_active_role_remains(): void
    {
        $email = 'active-staff-inactive-trainer@example.test';
        $hash = password_hash('multi-role-password', PASSWORD_BCRYPT);
        DB::table('staff_edu')->insert([
            'staffID' => 'ST400', 'staffName' => 'Active Staff', 'email' => $email,
            'password' => $hash, 'status' => 'active',
        ]);
        DB::table('trainer')->insert([
            'trainerID' => 'TR400', 'trainerName' => 'Inactive Trainer', 'trainerEmail' => $email,
            'trainerPassword' => $hash, 'status' => 'inactive',
        ]);

        $roles = app(CanonicalIdentityService::class)->authenticate($email, 'multi-role-password');

        $this->assertSame(['staff_edu'], array_keys($roles));
    }

    public function test_hr_remains_role_based_because_its_canonical_contract_has_no_activity_column(): void
    {
        Schema::create('hr_administrator', function (Blueprint $table): void {
            $table->string('hrid')->primary();
            $table->string('username');
            $table->string('email');
            $table->string('password');
            $table->string('role');
        });
        DB::table('hr_administrator')->insert([
            'hrid' => 'HR400',
            'username' => 'HR User',
            'email' => 'hr-no-status@example.test',
            'password' => password_hash('hr-password', PASSWORD_BCRYPT),
            'role' => 'HR_Administrator',
        ]);

        $roles = app(CanonicalIdentityService::class)->authenticate('hr-no-status@example.test', 'hr-password');

        $this->assertSame(['hr'], array_keys($roles));
    }

    public function test_outsider_login_remains_relationship_based_without_an_activity_field(): void
    {
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
        $this->createPublicRoleTables();
        $userId = DB::table('users')->insertGetId([
            'name' => 'Public User',
            'email' => 'public-no-status@example.test',
            'password' => password_hash('public-password', PASSWORD_BCRYPT),
        ]);
        DB::table('outsider')->insert(['user_id' => $userId, 'name' => 'Public User']);
        $this->assignPublicRole($userId);

        $roles = app(CanonicalIdentityService::class)->authenticate('public-no-status@example.test', 'public-password');

        $this->assertSame(['outsider'], array_keys($roles));
    }

    private function createPublicRoleTables(): void
    {
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
    }

    private function assignPublicRole(int $userId): void
    {
        DB::table('model_has_roles')->insert([
            'role_id' => 1,
            'model_type' => 'App\\Models\\User',
            'model_id' => $userId,
        ]);
    }

    private function statusCases(): array
    {
        return [
            ['active', 'active', true],
            ['aktif', ' Aktif ', true],
            ['inactive', 'inactive', false],
            ['blank', '', false],
            ['null', null, false],
            ['unknown', 'pending', false],
        ];
    }

    private function createTeacherTable(): void
    {
        Schema::create('teacher', function (Blueprint $table): void {
            $table->string('teacherID')->primary();
            $table->string('teacherName');
            $table->string('email');
            $table->string('password');
            $table->string('role');
        });
    }

    private function createAssignTable(): void
    {
        Schema::create('assign', function (Blueprint $table): void {
            $table->string('schoolID', 50);
            $table->string('teacherID', 20);
            $table->date('assignDate')->nullable();
            $table->string('status', 50)->nullable();
            $table->primary(['schoolID', 'teacherID']);
        });
    }

    private function insertTeacher(string $teacherId, string $email, string $password): void
    {
        DB::table('teacher')->insert([
            'teacherID' => $teacherId,
            'teacherName' => 'Teacher '.$teacherId,
            'email' => $email,
            'password' => password_hash($password, PASSWORD_BCRYPT),
            'role' => 'teacher',
        ]);
    }

    private function insertLinkedTeacher(string $teacherId, string $email, ?string $status): void
    {
        $this->insertTeacher($teacherId, $email, 'teacher-password');
        DB::table('assign')->insert([
            'schoolID' => 'SC'.$teacherId,
            'teacherID' => $teacherId,
            'assignDate' => '2026-01-01',
            'status' => $status,
        ]);
    }
}
