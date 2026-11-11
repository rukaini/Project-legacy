<?php

namespace Tests\Feature;

use App\Services\CanonicalIdentityService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PasswordRecoveryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('staff_edu', function (Blueprint $table): void {
            $table->string('staffID')->primary();
            $table->string('staffName');
            $table->string('ICNumber')->nullable();
            $table->string('email')->nullable();
            $table->string('password');
            $table->string('status')->nullable();
        });
        Schema::create('trainer', function (Blueprint $table): void {
            $table->string('trainerID')->primary();
            $table->string('trainerName');
            $table->string('trainerIC')->nullable();
            $table->string('trainerEmail')->nullable();
            $table->string('trainerPassword');
            $table->string('status')->nullable();
        });
        Schema::create('teacher', function (Blueprint $table): void {
            $table->string('teacherID')->primary();
            $table->string('teacherName');
            $table->string('ICNumber')->nullable();
            $table->string('email')->nullable();
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
        Schema::create('guru_new', function (Blueprint $table): void {
            $table->string('gn_id')->primary();
            $table->string('gn_name');
            $table->string('ic_number')->nullable();
            $table->string('email')->nullable();
            $table->string('password');
            $table->string('role');
            $table->string('current_status')->nullable();
        });
        Schema::create('principal', function (Blueprint $table): void {
            $table->string('principalID')->primary();
            $table->string('principalName');
            $table->string('ICNumber')->nullable();
            $table->string('email')->nullable();
            $table->string('password');
            $table->string('role');
            $table->string('status')->nullable();
        });
        Schema::create('hr_administrator', function (Blueprint $table): void {
            $table->string('hrid')->primary();
            $table->string('username');
            $table->string('email')->nullable();
            $table->string('password');
            $table->string('role');
        });
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
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
    }

    public function test_recovery_page_loads_and_login_links_to_it(): void
    {
        $this->get('/forgot-password')
            ->assertOk()
            ->assertHeader('Cache-Control', 'max-age=0, no-store, private')
            ->assertSee('ELIT Portal')
            ->assertSee('EduOasis Learning &amp; Insight Tracking', false)
            ->assertSee('Recover account')
            ->assertSee('IC Number or Email')
            ->assertSee('name="identifier"', false)
            ->assertSee('Recover password')
            ->assertSee('Back to Login')
            ->assertSee('images/al-amin-edu-oasis-logo.jpg', false)
            ->assertDontSee('name="password"', false)
            ->assertDontSee('Outsider');

        $this->get('/login')
            ->assertOk()
            ->assertSee('Forgot password?')
            ->assertSee('href="'.route('password.request').'"', false);
    }

    public function test_staff_edu_can_recover_by_ic(): void
    {
        $oldHash = Hash::make('old-staff-password');
        DB::table('staff_edu')->insert([
            'staffID' => 'ST001', 'staffName' => 'Staff', 'ICNumber' => ' 901010101010 ',
            'email' => 'staff@example.test', 'password' => $oldHash, 'status' => ' Active ',
        ]);

        $password = $this->recoverSuccessfully('  901010101010  ');
        $stored = DB::table('staff_edu')->where('staffID', 'ST001')->value('password');

        $this->assertRecoveredPassword($password, $stored, 'old-staff-password');
    }

    public function test_trainer_can_recover_by_ic(): void
    {
        DB::table('trainer')->insert([
            'trainerID' => 'TR001', 'trainerName' => 'Trainer', 'trainerIC' => '901010101011',
            'trainerEmail' => 'trainer@example.test', 'trainerPassword' => Hash::make('old-trainer-password'),
            'status' => 'aktif',
        ]);

        $password = $this->recoverSuccessfully('901010101011');
        $stored = DB::table('trainer')->where('trainerID', 'TR001')->value('trainerPassword');

        $this->assertRecoveredPassword($password, $stored, 'old-trainer-password');
    }

    public function test_teacher_can_recover_by_ic(): void
    {
        $this->insertTeacher('TC001', '901010101012', 'teacher@example.test', 'old-teacher-password');
        $this->insertAssignment('TC001', 'active');

        $password = $this->recoverSuccessfully('901010101012');
        $stored = DB::table('teacher')->where('teacherID', 'TC001')->value('password');

        $this->assertRecoveredPassword($password, $stored, 'old-teacher-password');
    }

    public function test_new_teacher_can_recover_by_ic(): void
    {
        DB::table('guru_new')->insert([
            'gn_id' => 'GN001', 'gn_name' => 'New Teacher', 'ic_number' => '901010101013',
            'email' => 'new-teacher@example.test', 'password' => Hash::make('old-new-teacher-password'),
            'role' => 'new_teacher', 'current_status' => 'ACTIVE',
        ]);

        $password = $this->recoverSuccessfully('901010101013');
        $stored = DB::table('guru_new')->where('gn_id', 'GN001')->value('password');

        $this->assertRecoveredPassword($password, $stored, 'old-new-teacher-password');
    }

    public function test_principal_can_recover_by_ic(): void
    {
        DB::table('principal')->insert([
            'principalID' => 'PR001', 'principalName' => 'Principal', 'ICNumber' => '901010101014',
            'email' => 'principal@example.test', 'password' => Hash::make('old-principal-password'),
            'role' => 'principal', 'status' => 'Aktif',
        ]);

        $password = $this->recoverSuccessfully('901010101014');
        $stored = DB::table('principal')->where('principalID', 'PR001')->value('password');

        $this->assertRecoveredPassword($password, $stored, 'old-principal-password');
    }

    public function test_public_account_can_recover_by_ic(): void
    {
        $userId = $this->insertPublic('public@example.test', 'old-public-password', '901010101015');

        $password = $this->recoverSuccessfully('901010101015');
        $stored = DB::table('users')->where('id', $userId)->value('password');

        $this->assertRecoveredPassword($password, $stored, 'old-public-password');
    }

    public function test_hr_can_recover_by_case_insensitive_trimmed_email(): void
    {
        DB::table('hr_administrator')->insert([
            'hrid' => 'HR001', 'username' => 'HR', 'email' => 'hr@example.test',
            'password' => Hash::make('old-hr-password'), 'role' => 'HR_Administrator',
        ]);

        $password = $this->recoverSuccessfully('  HR@Example.Test ');
        $stored = DB::table('hr_administrator')->where('hrid', 'HR001')->value('password');

        $this->assertRecoveredPassword($password, $stored, 'old-hr-password');
    }

    public function test_public_account_without_ic_can_recover_by_email(): void
    {
        $userId = $this->insertPublic('no-ic@example.test', 'old-public-password', null);

        $password = $this->recoverSuccessfully(' NO-IC@EXAMPLE.TEST ');
        $stored = DB::table('users')->where('id', $userId)->value('password');

        $this->assertRecoveredPassword($password, $stored, 'old-public-password');
    }

    public function test_teacher_email_recovery_updates_the_credential_used_by_linked_observer_roles(): void
    {
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
        $this->insertTeacher('TC002', '901010101016', 'linked@example.test', 'old-linked-password');
        $this->insertAssignment('TC002', 'active');
        DB::table('observer')->insert(['observerID' => 'OB001', 'teacherID' => 'TC002', 'status' => 'active']);
        DB::table('external_observer')->insert([
            'externalObserverID' => 'EO001', 'teacherID' => 'TC002', 'status' => 'aktif',
        ]);

        $password = $this->recoverSuccessfully('linked@example.test');
        $roles = app(CanonicalIdentityService::class)->authenticate('linked@example.test', $password);

        $this->assertSame(['teacher', 'observer', 'external_observer'], array_keys($roles));
        $this->assertSame([], app(CanonicalIdentityService::class)->authenticate(
            'linked@example.test',
            'old-linked-password',
        ));
    }

    public function test_plaintext_is_displayed_once_but_never_persisted_or_put_in_session(): void
    {
        DB::table('staff_edu')->insert([
            'staffID' => 'ST002', 'staffName' => 'Staff', 'ICNumber' => '901010101017',
            'email' => 'once@example.test', 'password' => Hash::make('old-password'), 'status' => 'active',
        ]);

        $response = $this->post('/forgot-password', ['identifier' => '901010101017'])
            ->assertOk()
            ->assertHeader('Cache-Control', 'max-age=0, no-store, private')
            ->assertSee('ELIT Portal')
            ->assertSee('EduOasis Learning &amp; Insight Tracking', false)
            ->assertSee('Save this password now. It will not be shown again.')
            ->assertSee('data-copy-target="generated-password"', false)
            ->assertSee('Back to Login')
            ->assertSee('images/al-amin-edu-oasis-logo.jpg', false)
            ->assertDontSee('Outsider');
        $password = $this->passwordFrom($response->getContent());
        $stored = DB::table('staff_edu')->where('staffID', 'ST002')->value('password');

        $this->assertNotSame($password, $stored);
        $this->assertTrue(Hash::check($password, $stored));
        $this->assertStringNotContainsString($password, json_encode(session()->all(), JSON_THROW_ON_ERROR));
        $this->assertStringNotContainsString($password, $response->headers->get('Location', ''));
    }

    public function test_unknown_identifier_changes_nothing(): void
    {
        $hash = Hash::make('unchanged-password');
        DB::table('staff_edu')->insert([
            'staffID' => 'ST003', 'staffName' => 'Staff', 'ICNumber' => '901010101018',
            'email' => 'known@example.test', 'password' => $hash, 'status' => 'active',
        ]);

        $this->post('/forgot-password', ['identifier' => 'unknown@example.test'])
            ->assertUnprocessable()
            ->assertSee('We could not recover an active account with that information.');

        $this->assertSame($hash, DB::table('staff_edu')->where('staffID', 'ST003')->value('password'));
    }

    public function test_ambiguous_email_changes_no_password(): void
    {
        $staffHash = Hash::make('staff-password');
        $trainerHash = Hash::make('trainer-password');
        DB::table('staff_edu')->insert([
            'staffID' => 'ST004', 'staffName' => 'Staff', 'ICNumber' => '901010101019',
            'email' => 'shared@example.test', 'password' => $staffHash, 'status' => 'active',
        ]);
        DB::table('trainer')->insert([
            'trainerID' => 'TR004', 'trainerName' => 'Trainer', 'trainerIC' => '901010101020',
            'trainerEmail' => 'shared@example.test', 'trainerPassword' => $trainerHash, 'status' => 'active',
        ]);

        $this->post('/forgot-password', ['identifier' => 'shared@example.test'])
            ->assertUnprocessable()
            ->assertSee('We found more than one account for this information.');

        $this->assertSame($staffHash, DB::table('staff_edu')->where('staffID', 'ST004')->value('password'));
        $this->assertSame($trainerHash, DB::table('trainer')->where('trainerID', 'TR004')->value('trainerPassword'));
    }

    public function test_protected_administrator_email_starts_secure_recovery_without_changing_passwords(): void
    {
        config(['sso.administrator.email' => ' protected.admin@example.test ']);
        $staffHash = Hash::make('old-staff-password');
        $trainerHash = Hash::make('old-trainer-password');
        DB::table('staff_edu')->insert([
            'staffID' => 'ST900', 'staffName' => 'Protected Account', 'ICNumber' => 'admin-staff-ic',
            'email' => 'PROTECTED.ADMIN@example.test', 'password' => $staffHash, 'status' => 'active',
        ]);
        DB::table('trainer')->insert([
            'trainerID' => 'TR900', 'trainerName' => 'Protected Account', 'trainerIC' => 'admin-trainer-ic',
            'trainerEmail' => 'protected.admin@example.test', 'trainerPassword' => $trainerHash,
            'status' => 'active',
        ]);

        $this->post('/forgot-password', ['identifier' => ' PROTECTED.ADMIN@EXAMPLE.TEST '])
            ->assertOk()
            ->assertSee('secure password reset link has been sent')
            ->assertDontSee('data-generated-password', false);

        $this->assertSame($staffHash, DB::table('staff_edu')->where('staffID', 'ST900')->value('password'));
        $this->assertSame($trainerHash, DB::table('trainer')->where('trainerID', 'TR900')->value('trainerPassword'));
    }

    public function test_protected_administrator_role_specific_ic_cannot_use_public_recovery(): void
    {
        config(['sso.administrator.email' => 'protected.admin@example.test']);
        $hash = Hash::make('old-password');
        DB::table('principal')->insert([
            'principalID' => 'PR900', 'principalName' => 'Protected Account', 'ICNumber' => 'admin-principal-ic',
            'email' => 'protected.admin@example.test', 'password' => $hash,
            'role' => 'principal', 'status' => 'active',
        ]);

        $this->post('/forgot-password', ['identifier' => 'admin-principal-ic'])
            ->assertUnprocessable()
            ->assertSee('enter the registered email address')
            ->assertDontSee('data-generated-password', false);

        $this->assertSame($hash, DB::table('principal')->where('principalID', 'PR900')->value('password'));
    }

    public function test_protected_ineligible_target_blocks_a_colliding_public_recovery_identifier(): void
    {
        config(['sso.administrator.email' => 'protected.admin@example.test']);
        $adminHash = Hash::make('admin-old-password');
        $normalHash = Hash::make('normal-old-password');
        DB::table('staff_edu')->insert([
            'staffID' => 'ST902', 'staffName' => 'Protected Account', 'ICNumber' => 'shared-sensitive-ic',
            'email' => 'protected.admin@example.test', 'password' => $adminHash, 'status' => 'inactive',
        ]);
        DB::table('trainer')->insert([
            'trainerID' => 'TR902', 'trainerName' => 'Normal Account', 'trainerIC' => 'shared-sensitive-ic',
            'trainerEmail' => 'normal@example.test', 'trainerPassword' => $normalHash, 'status' => 'active',
        ]);

        $this->post('/forgot-password', ['identifier' => 'shared-sensitive-ic'])
            ->assertUnprocessable()
            ->assertSee('enter the registered email address');

        $this->assertSame($adminHash, DB::table('staff_edu')->where('staffID', 'ST902')->value('password'));
        $this->assertSame($normalHash, DB::table('trainer')->where('trainerID', 'TR902')->value('trainerPassword'));
    }

    public function test_inactive_canonical_accounts_cannot_recover(): void
    {
        DB::table('staff_edu')->insert([
            'staffID' => 'ST005', 'staffName' => 'Staff', 'ICNumber' => 'inactive-staff',
            'email' => 'inactive-staff@example.test', 'password' => Hash::make('staff-old'), 'status' => 'inactive',
        ]);
        DB::table('trainer')->insert([
            'trainerID' => 'TR005', 'trainerName' => 'Trainer', 'trainerIC' => 'inactive-trainer',
            'trainerEmail' => 'inactive-trainer@example.test', 'trainerPassword' => Hash::make('trainer-old'),
            'status' => 'inactive',
        ]);
        $this->insertTeacher('TC005', 'inactive-teacher', 'inactive-teacher@example.test', 'teacher-old');
        $this->insertAssignment('TC005', 'inactive');
        DB::table('guru_new')->insert([
            'gn_id' => 'GN005', 'gn_name' => 'New Teacher', 'ic_number' => 'inactive-new-teacher',
            'email' => 'inactive-new@example.test', 'password' => Hash::make('new-old'),
            'role' => 'new_teacher', 'current_status' => 'inactive',
        ]);
        DB::table('principal')->insert([
            'principalID' => 'PR005', 'principalName' => 'Principal', 'ICNumber' => 'inactive-principal',
            'email' => 'inactive-principal@example.test', 'password' => Hash::make('principal-old'),
            'role' => 'principal', 'status' => 'inactive',
        ]);
        $before = [
            DB::table('staff_edu')->value('password'),
            DB::table('trainer')->value('trainerPassword'),
            DB::table('teacher')->value('password'),
            DB::table('guru_new')->value('password'),
            DB::table('principal')->value('password'),
        ];

        foreach (['inactive-staff', 'inactive-trainer', 'inactive-teacher', 'inactive-new-teacher', 'inactive-principal'] as $identifier) {
            $this->post('/forgot-password', ['identifier' => $identifier])->assertUnprocessable();
        }

        $this->assertSame($before, [
            DB::table('staff_edu')->value('password'),
            DB::table('trainer')->value('trainerPassword'),
            DB::table('teacher')->value('password'),
            DB::table('guru_new')->value('password'),
            DB::table('principal')->value('password'),
        ]);
    }

    public function test_teacher_recovery_fails_closed_when_assignment_schema_is_missing(): void
    {
        Schema::drop('assign');
        $this->insertTeacher('TC006', 'missing-assign', 'missing-assign@example.test', 'teacher-old');
        $hash = DB::table('teacher')->where('teacherID', 'TC006')->value('password');

        $this->post('/forgot-password', ['identifier' => 'missing-assign'])->assertUnprocessable();

        $this->assertSame($hash, DB::table('teacher')->where('teacherID', 'TC006')->value('password'));
    }

    public function test_users_row_without_one_legitimate_public_profile_is_not_recoverable(): void
    {
        $hash = Hash::make('orphan-password');
        DB::table('users')->insert([
            'name' => 'Orphan', 'email' => 'orphan@example.test', 'password' => $hash,
        ]);

        $this->post('/forgot-password', ['identifier' => 'orphan@example.test'])->assertUnprocessable();

        $this->assertSame($hash, DB::table('users')->value('password'));
    }

    public function test_recovery_post_is_limited_by_normalized_identifier_and_ip(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post('/forgot-password', ['identifier' => '  RATE@Example.Test '])
                ->assertUnprocessable();
        }

        $this->post('/forgot-password', ['identifier' => 'rate@example.test'])
            ->assertTooManyRequests();
    }

    public function test_registration_contract_remains_unchanged_and_no_migration_table_is_needed(): void
    {
        $this->get('/register')
            ->assertOk()
            ->assertSee('name="name"', false)
            ->assertSee('name="email"', false)
            ->assertSee('name="password"', false)
            ->assertSee('name="password_confirmation"', false)
            ->assertDontSee('name="ICNumber"', false)
            ->assertDontSee('name="ic_number"', false);

        $this->assertFalse(Schema::hasTable('migrations'));
    }

    private function recoverSuccessfully(string $identifier): string
    {
        $response = $this->post('/forgot-password', ['identifier' => $identifier])
            ->assertOk()
            ->assertSee('Your new password')
            ->assertSee('Save this password now. It will not be shown again.');

        return $this->passwordFrom($response->getContent());
    }

    private function passwordFrom(string $content): string
    {
        $matched = preg_match('/data-generated-password>([A-Za-z0-9]+)<\/output>/', $content, $matches);
        $this->assertSame(1, $matched, 'The generated password was not found in the response.');

        return $matches[1];
    }

    private function assertRecoveredPassword(string $password, string $stored, string $oldPassword): void
    {
        $this->assertSame(12, strlen($password));
        $this->assertMatchesRegularExpression('/[A-Za-z]/', $password);
        $this->assertMatchesRegularExpression('/[2-9]/', $password);
        $this->assertDoesNotMatchRegularExpression('/[01OIl]/', $password);
        $this->assertNotSame($password, $stored);
        $this->assertTrue(Hash::check($password, $stored));
        $this->assertFalse(Hash::check($oldPassword, $stored));
    }

    private function insertTeacher(string $id, string $ic, string $email, string $password): void
    {
        DB::table('teacher')->insert([
            'teacherID' => $id, 'teacherName' => 'Teacher', 'ICNumber' => $ic,
            'email' => $email, 'password' => Hash::make($password), 'role' => 'teacher',
        ]);
    }

    private function insertAssignment(string $teacherId, string $status): void
    {
        DB::table('assign')->insert([
            'schoolID' => 'SC'.$teacherId, 'teacherID' => $teacherId,
            'assignDate' => '2026-01-01', 'status' => $status,
        ]);
    }

    private function insertPublic(string $email, string $password, ?string $ic): int
    {
        $userId = DB::table('users')->insertGetId([
            'name' => 'Public User', 'email' => $email, 'password' => Hash::make($password),
        ]);
        DB::table('outsider')->insert([
            'user_id' => $userId, 'name' => 'Public User', 'ICNumber' => $ic,
        ]);
        DB::table('model_has_roles')->insert([
            'role_id' => 1, 'model_type' => 'App\\Models\\User', 'model_id' => $userId,
        ]);

        return $userId;
    }
}
