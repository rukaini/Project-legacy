<?php

namespace Tests\Feature;

use App\Services\AdministratorPasswordService;
use App\Services\AuthenticatedPasswordChangeService;
use App\Services\CanonicalIdentityService;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Mockery\MockInterface;
use Tests\TestCase;

class AuthenticatedPasswordChangeTest extends TestCase
{
    private string $email = 'account@example.test';

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('staff_edu', function (Blueprint $table): void {
            $table->string('staffID')->primary();
            $table->string('staffName');
            $table->string('email');
            $table->string('password');
            $table->string('status')->nullable();
        });
        Schema::create('trainer', function (Blueprint $table): void {
            $table->string('trainerID')->primary();
            $table->string('trainerName');
            $table->string('trainerEmail');
            $table->string('trainerPassword');
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
        DB::table('roles')->insert(['id' => 1, 'name' => 'outsider', 'guard_name' => 'web']);
    }

    public function test_page_requires_authentication_and_authenticated_form_renders(): void
    {
        $this->get('/change-password')->assertRedirect('/login');

        $response = $this->withSession(['gateway.identity' => $this->identity([
            'staff_edu' => $this->role('staff_edu', 'staff_edu', 'ST001'),
        ])])->get('/change-password')
            ->assertOk()
            ->assertSee('Current Password')
            ->assertSee('New Password')
            ->assertSee('Confirm New Password')
            ->assertSee('minlength="12"', false)
            ->assertSee('name="_token"', false);

        $cacheControl = (string) $response->headers->get('Cache-Control');
        $this->assertStringContainsString('no-store', $cacheControl);
        $this->assertStringContainsString('private', $cacheControl);
        $this->assertStringContainsString('max-age=0', $cacheControl);
    }

    public function test_wrong_current_password_changes_nothing(): void
    {
        $oldHash = $this->insertStaff('CurrentPassword123!');

        $this->withSession(['gateway.identity' => $this->staffIdentity()])
            ->post('/change-password', $this->passwordPayload('WrongPassword123!', 'NewPassword123!'))
            ->assertRedirect()
            ->assertSessionHasErrors('current_password');

        $this->assertSame($oldHash, DB::table('staff_edu')->value('password'));
    }

    public function test_confirmation_minimum_length_and_same_as_current_are_rejected_without_flashing_passwords(): void
    {
        $this->withSession(['gateway.identity' => $this->staffIdentity()])
            ->post('/change-password', [
                'current_password' => 'CurrentPassword123!',
                'password' => 'short',
                'password_confirmation' => 'different',
            ])->assertSessionHasErrors('password');

        $this->assertStringNotContainsString(
            'CurrentPassword123!',
            json_encode(session()->all(), JSON_THROW_ON_ERROR),
        );
        $this->assertStringNotContainsString('short', json_encode(session()->all(), JSON_THROW_ON_ERROR));

        $this->withSession(['gateway.identity' => $this->staffIdentity()])
            ->post('/change-password', [
                'current_password' => 'CurrentPassword123!',
                'password' => 'LongEnoughPassword123!',
                'password_confirmation' => '',
            ])->assertSessionHasErrors('password');

        $this->withSession(['gateway.identity' => $this->staffIdentity()])
            ->post('/change-password', $this->passwordPayload('CurrentPassword123!', 'CurrentPassword123!'))
            ->assertSessionHasErrors('password');
    }

    public function test_single_target_change_replaces_password_and_invalidates_session(): void
    {
        $this->insertStaff('CurrentPassword123!');
        $newPassword = 'ReplacementPassword456!';

        $this->withSession(['gateway.identity' => $this->staffIdentity()])
            ->post('/change-password', $this->passwordPayload('CurrentPassword123!', $newPassword))
            ->assertRedirect('/login')
            ->assertSessionHas('status', 'Your password has been changed. Sign in with your new password.')
            ->assertSessionMissing('gateway.identity');

        $stored = (string) DB::table('staff_edu')->value('password');
        $this->assertFalse(Hash::check('CurrentPassword123!', $stored));
        $this->assertTrue(Hash::check($newPassword, $stored));
        $this->assertSame([], app(CanonicalIdentityService::class)->authenticate($this->email, 'CurrentPassword123!'));
        $this->assertSame(['staff_edu'], array_keys(
            app(CanonicalIdentityService::class)->authenticate($this->email, $newPassword),
        ));
    }

    public function test_only_authenticated_targets_are_synchronized_and_teacher_links_are_deduplicated(): void
    {
        $oldPassword = 'SharedCurrentPassword123!';
        $newPassword = 'SharedReplacementPassword456!';
        $this->insertStaff($oldPassword);
        DB::table('teacher')->insert([
            'teacherID' => 'TC001', 'teacherName' => 'Portal User', 'email' => $this->email,
            'password' => Hash::make($oldPassword), 'role' => 'teacher',
        ]);
        DB::table('principal')->insert([
            'principalID' => 'PR001', 'principalName' => 'Unrelated Role', 'email' => $this->email,
            'password' => Hash::make($oldPassword), 'role' => 'principal', 'status' => 'active',
        ]);
        $principalHash = (string) DB::table('principal')->value('password');
        DB::table('assign')->insert([
            'schoolID' => 'SC001', 'teacherID' => 'TC001', 'assignDate' => '2026-01-01', 'status' => 'active',
        ]);
        DB::table('observer')->insert(['observerID' => 'OB001', 'teacherID' => 'TC001', 'status' => 'active']);
        DB::table('external_observer')->insert([
            'externalObserverID' => 'EO001', 'teacherID' => 'TC001', 'status' => 'active',
        ]);

        $teacherUpdates = 0;
        DB::listen(function (QueryExecuted $query) use (&$teacherUpdates): void {
            if (str_starts_with(strtolower($query->sql), 'update "teacher"')) {
                $teacherUpdates++;
            }
        });

        $roles = [
            'staff_edu' => $this->role('staff_edu', 'staff_edu', 'ST001'),
            'teacher' => $this->role('teacher', 'teacher', 'TC001'),
            'observer' => $this->role('observer', 'observer', 'OB001', ['teacher_id' => 'TC001']),
            'external_observer' => $this->role('external_observer', 'external_observer', 'EO001', ['teacher_id' => 'TC001']),
        ];

        $this->withSession(['gateway.identity' => $this->identity($roles)])
            ->post('/change-password', $this->passwordPayload($oldPassword, $newPassword))
            ->assertRedirect('/login');

        $this->assertTrue(Hash::check($newPassword, (string) DB::table('staff_edu')->value('password')));
        $this->assertTrue(Hash::check($newPassword, (string) DB::table('teacher')->value('password')));
        $this->assertSame(1, $teacherUpdates);
        $this->assertSame($principalHash, DB::table('principal')->value('password'));
    }

    public function test_any_update_failure_rolls_back_every_target(): void
    {
        $oldPassword = 'SharedCurrentPassword123!';
        $staffHash = $this->insertStaff($oldPassword);
        $trainerHash = $this->insertTrainer($oldPassword);
        DB::statement("CREATE TRIGGER reject_trainer_password BEFORE UPDATE OF trainerPassword ON trainer BEGIN SELECT RAISE(ABORT, 'synthetic failure'); END");

        $roles = [
            'staff_edu' => $this->role('staff_edu', 'staff_edu', 'ST001'),
            'trainer' => $this->role('trainer', 'trainer', 'TR001'),
        ];
        $this->withSession(['gateway.identity' => $this->identity($roles)])
            ->post('/change-password', $this->passwordPayload($oldPassword, 'ReplacementPassword456!'))
            ->assertRedirect()
            ->assertSessionHasErrors('password');

        $this->assertSame($staffHash, DB::table('staff_edu')->value('password'));
        $this->assertSame($trainerHash, DB::table('trainer')->value('trainerPassword'));
    }

    public function test_inactive_session_role_cannot_change_its_canonical_password(): void
    {
        $oldHash = $this->insertStaff('CurrentPassword123!');
        DB::table('staff_edu')->update(['status' => 'inactive']);

        $this->withSession(['gateway.identity' => $this->staffIdentity()])
            ->post('/change-password', $this->passwordPayload('CurrentPassword123!', 'ReplacementPassword456!'))
            ->assertSessionHasErrors('password');

        $this->assertSame($oldHash, DB::table('staff_edu')->value('password'));
    }

    public function test_account_marker_alone_grants_no_administrator_privilege(): void
    {
        config(['sso.administrator.email' => 'protected@example.test']);
        $oldPassword = 'CurrentPassword123!';
        $this->insertStaff($oldPassword);
        $trainerHash = $this->insertTrainer($oldPassword);
        $identity = $this->staffIdentity();
        $identity['is_administrator'] = true;

        $this->withSession(['gateway.identity' => $identity])
            ->post('/change-password', $this->passwordPayload($oldPassword, 'ReplacementPassword456!'))
            ->assertRedirect('/login');

        $this->assertTrue(Hash::check('ReplacementPassword456!', (string) DB::table('staff_edu')->value('password')));
        $this->assertSame($trainerHash, DB::table('trainer')->value('trainerPassword'));
    }

    public function test_change_attempts_are_throttled(): void
    {
        $this->insertStaff('CurrentPassword123!');
        $session = ['gateway.identity' => $this->staffIdentity()];

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->withSession($session)
                ->post('/change-password', $this->passwordPayload('WrongPassword123!', 'ReplacementPassword456!'))
                ->assertRedirect();
        }

        $this->withSession($session)
            ->post('/change-password', $this->passwordPayload('WrongPassword123!', 'ReplacementPassword456!'))
            ->assertTooManyRequests();
    }

    public function test_administrator_wrong_current_password_changes_nothing(): void
    {
        config(['sso.administrator.email' => $this->email]);
        $this->insertAllAdministratorTargets('AdministratorCurrent123!');
        $before = $this->allHashes();

        $this->withSession(['gateway.identity' => $this->staffIdentity(true)])
            ->post('/change-password', $this->passwordPayload('WrongPassword123!', 'AdministratorReplacement456!'))
            ->assertSessionHasErrors('current_password');

        $this->assertSame($before, $this->allHashes());
    }

    public function test_administrator_change_synchronizes_seven_targets_and_new_password_authenticates_all_roles(): void
    {
        config(['sso.administrator.email' => $this->email]);
        $oldPassword = 'AdministratorCurrent123!';
        $newPassword = 'AdministratorReplacement456!';
        $this->insertAllAdministratorTargets($oldPassword);

        $this->withSession(['gateway.identity' => $this->staffIdentity(true)])
            ->post('/change-password', $this->passwordPayload($oldPassword, $newPassword))
            ->assertRedirect('/login')
            ->assertSessionMissing('gateway.identity');

        $hashes = $this->allHashes();
        $this->assertCount(7, $hashes);
        $this->assertCount(7, array_unique($hashes));
        foreach ($hashes as $stored) {
            $this->assertFalse(Hash::check($oldPassword, $stored));
            $this->assertTrue(Hash::check($newPassword, $stored));
        }

        $identities = app(CanonicalIdentityService::class);
        $this->assertSame([], $identities->authenticate($this->email, $oldPassword));
        $this->assertSame([
            'staff_edu', 'teacher', 'observer', 'external_observer', 'new_teacher',
            'principal', 'hr', 'trainer', 'outsider',
        ], array_keys($identities->authenticate($this->email, $newPassword)));
    }

    public function test_administrator_change_delegates_actual_update_to_administrator_password_service(): void
    {
        config(['sso.administrator.email' => $this->email]);
        $oldPassword = 'AdministratorCurrent123!';
        $this->insertStaff($oldPassword);
        $this->insertTrainer($oldPassword);

        $this->mock(AdministratorPasswordService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('reset')
                ->once()
                ->with('AdministratorReplacement456!')
                ->andReturn(['email' => $this->email, 'roles' => ['staff_edu', 'trainer'], 'target_count' => 2]);
        });

        $result = app(AuthenticatedPasswordChangeService::class)->change(
            $this->staffIdentity(true),
            $oldPassword,
            'AdministratorReplacement456!',
        );

        $this->assertSame(['administrator' => true, 'target_count' => 2], $result);
    }

    /** @return array<string, mixed> */
    private function identity(array $roles, bool $administrator = false): array
    {
        return [
            'email' => $this->email,
            'name' => 'Portal User',
            'roles' => $roles,
            'selected_role' => array_key_first($roles),
            'is_administrator' => $administrator,
        ];
    }

    /** @return array<string, mixed> */
    private function staffIdentity(bool $administrator = false): array
    {
        return $this->identity([
            'staff_edu' => $this->role('staff_edu', 'staff_edu', 'ST001'),
        ], $administrator);
    }

    /** @return array<string, string> */
    private function role(string $role, string $source, string $id, array $extra = []): array
    {
        return $extra + [
            'role' => $role,
            'source' => $source,
            'id' => $id,
            'name' => 'Portal User',
            'email' => $this->email,
        ];
    }

    /** @return array{current_password: string, password: string, password_confirmation: string} */
    private function passwordPayload(string $current, string $new): array
    {
        return ['current_password' => $current, 'password' => $new, 'password_confirmation' => $new];
    }

    private function insertStaff(string $password): string
    {
        $hash = Hash::make($password);
        DB::table('staff_edu')->insert([
            'staffID' => 'ST001', 'staffName' => 'Portal User', 'email' => $this->email,
            'password' => $hash, 'status' => 'active',
        ]);

        return $hash;
    }

    private function insertTrainer(string $password): string
    {
        $hash = Hash::make($password);
        DB::table('trainer')->insert([
            'trainerID' => 'TR001', 'trainerName' => 'Portal User', 'trainerEmail' => $this->email,
            'trainerPassword' => $hash, 'status' => 'active',
        ]);

        return $hash;
    }

    private function insertAllAdministratorTargets(string $password): void
    {
        $this->insertStaff($password);
        $this->insertTrainer($password);
        DB::table('teacher')->insert([
            'teacherID' => 'TC001', 'teacherName' => 'Portal User', 'email' => $this->email,
            'password' => Hash::make($password), 'role' => 'teacher',
        ]);
        DB::table('assign')->insert([
            'schoolID' => 'SC001', 'teacherID' => 'TC001', 'assignDate' => '2026-01-01', 'status' => 'active',
        ]);
        DB::table('observer')->insert(['observerID' => 'OB001', 'teacherID' => 'TC001', 'status' => 'active']);
        DB::table('external_observer')->insert([
            'externalObserverID' => 'EO001', 'teacherID' => 'TC001', 'status' => 'active',
        ]);
        DB::table('guru_new')->insert([
            'gn_id' => 'GN001', 'gn_name' => 'Portal User', 'email' => $this->email,
            'password' => Hash::make($password), 'role' => 'new_teacher', 'current_status' => 'active',
        ]);
        DB::table('principal')->insert([
            'principalID' => 'PR001', 'principalName' => 'Portal User', 'email' => $this->email,
            'password' => Hash::make($password), 'role' => 'principal', 'status' => 'active',
        ]);
        DB::table('hr_administrator')->insert([
            'hrid' => 'HR001', 'username' => 'Portal User', 'email' => $this->email,
            'password' => Hash::make($password), 'role' => 'hr',
        ]);
        $userId = DB::table('users')->insertGetId([
            'name' => 'Portal User', 'email' => $this->email, 'password' => Hash::make($password),
        ]);
        DB::table('outsider')->insert(['user_id' => $userId, 'name' => 'Portal User']);
        DB::table('model_has_roles')->insert([
            'role_id' => 1, 'model_type' => 'App\\Models\\User', 'model_id' => $userId,
        ]);
    }

    /** @return array<int, string> */
    private function allHashes(): array
    {
        return [
            (string) DB::table('staff_edu')->value('password'),
            (string) DB::table('teacher')->value('password'),
            (string) DB::table('guru_new')->value('password'),
            (string) DB::table('principal')->value('password'),
            (string) DB::table('hr_administrator')->value('password'),
            (string) DB::table('trainer')->value('trainerPassword'),
            (string) DB::table('users')->value('password'),
        ];
    }
}
