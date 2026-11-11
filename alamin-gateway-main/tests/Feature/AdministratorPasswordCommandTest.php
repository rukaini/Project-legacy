<?php

namespace Tests\Feature;

use App\Services\AdministratorPasswordService;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdministratorPasswordCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('staff_edu', function (Blueprint $table): void {
            $table->string('staffID')->primary();
            $table->string('staffName');
            $table->string('ICNumber')->nullable();
            $table->string('email');
            $table->string('password');
            $table->string('status')->nullable();
        });
        Schema::create('trainer', function (Blueprint $table): void {
            $table->string('trainerID')->primary();
            $table->string('trainerName');
            $table->string('trainerIC')->nullable();
            $table->string('trainerEmail');
            $table->string('trainerPassword');
            $table->string('status')->nullable();
        });
        Schema::create('principal', function (Blueprint $table): void {
            $table->string('principalID')->primary();
            $table->string('principalName');
            $table->string('ICNumber')->nullable();
            $table->string('email');
            $table->string('password');
            $table->string('role');
            $table->string('status')->nullable();
        });
    }

    public function test_command_refuses_blank_administrator_email(): void
    {
        config(['sso.administrator.email' => '']);

        $this->artisan('elit:administrator-password', ['--generate' => true])
            ->expectsOutputToContain('ELIT_ADMIN_EMAIL must contain a valid email address.')
            ->assertFailed();
    }

    public function test_command_refuses_when_no_targets_exist(): void
    {
        config(['sso.administrator.email' => 'missing@example.test']);

        $this->artisan('elit:administrator-password', ['--generate' => true])
            ->expectsOutputToContain('At least 2 eligible canonical credential targets are required; found 0.')
            ->assertFailed();
    }

    public function test_multiple_targets_are_independently_hashed_to_the_same_password(): void
    {
        config(['sso.administrator.email' => ' PROTECTED@EXAMPLE.TEST ']);
        $oldStaff = Hash::make('old-staff-password');
        $oldTrainer = Hash::make('old-trainer-password');
        $unrelated = Hash::make('unrelated-password');
        $this->insertAdministratorTargets($oldStaff, $oldTrainer);
        DB::table('staff_edu')->insert([
            'staffID' => 'ST901', 'staffName' => 'Unrelated', 'email' => 'unrelated@example.test',
            'password' => $unrelated, 'status' => 'active',
        ]);

        $plaintext = 'NewSharedPassword8642';
        $result = app(AdministratorPasswordService::class)->reset($plaintext);
        $staffHash = DB::table('staff_edu')->where('staffID', 'ST900')->value('password');
        $trainerHash = DB::table('trainer')->where('trainerID', 'TR900')->value('trainerPassword');

        $this->assertSame(2, $result['target_count']);
        $this->assertSame(['staff_edu', 'trainer'], $result['roles']);
        $this->assertTrue(Hash::check($plaintext, $staffHash));
        $this->assertTrue(Hash::check($plaintext, $trainerHash));
        $this->assertFalse(Hash::check('old-staff-password', $staffHash));
        $this->assertFalse(Hash::check('old-trainer-password', $trainerHash));
        $this->assertNotSame($staffHash, $trainerHash, 'Each credential must receive an independent salted hash.');
        $this->assertNotSame($plaintext, $staffHash);
        $this->assertNotSame($plaintext, $trainerHash);
        $this->assertSame($unrelated, DB::table('staff_edu')->where('staffID', 'ST901')->value('password'));
    }

    public function test_one_target_failure_rolls_back_every_update(): void
    {
        config(['sso.administrator.email' => 'protected@example.test']);
        $oldStaff = Hash::make('old-staff-password');
        $oldTrainer = Hash::make('old-trainer-password');
        $this->insertAdministratorTargets($oldStaff, $oldTrainer);
        DB::unprepared(<<<'SQL'
            CREATE TRIGGER reject_trainer_password
            BEFORE UPDATE OF trainerPassword ON trainer
            BEGIN
                SELECT RAISE(ABORT, 'forced target failure');
            END
            SQL);

        try {
            app(AdministratorPasswordService::class)->reset('RollbackPassword8642');
            $this->fail('The forced target failure should have aborted the reset.');
        } catch (QueryException) {
            $this->assertSame($oldStaff, DB::table('staff_edu')->where('staffID', 'ST900')->value('password'));
            $this->assertSame($oldTrainer, DB::table('trainer')->where('trainerID', 'TR900')->value('trainerPassword'));
        }
    }

    public function test_duplicate_role_targets_are_refused_without_changes(): void
    {
        config(['sso.administrator.email' => 'protected@example.test']);
        $firstHash = Hash::make('first-password');
        $secondHash = Hash::make('second-password');
        $trainerHash = Hash::make('trainer-password');
        $this->insertAdministratorTargets($firstHash, $trainerHash);
        DB::table('staff_edu')->insert([
            'staffID' => 'ST902', 'staffName' => 'Duplicate Protected Account',
            'email' => 'PROTECTED@EXAMPLE.TEST', 'password' => $secondHash, 'status' => 'active',
        ]);

        $this->artisan('elit:administrator-password', ['--generate' => true])
            ->expectsOutputToContain('Duplicate or ambiguous Administrator credential targets were found for: staff_edu.')
            ->assertFailed();

        $this->assertSame($firstHash, DB::table('staff_edu')->where('staffID', 'ST900')->value('password'));
        $this->assertSame($secondHash, DB::table('staff_edu')->where('staffID', 'ST902')->value('password'));
        $this->assertSame($trainerHash, DB::table('trainer')->where('trainerID', 'TR900')->value('trainerPassword'));
    }

    public function test_ineligible_administrator_identity_refuses_reset_without_changes(): void
    {
        config(['sso.administrator.email' => 'protected@example.test']);
        $oldStaff = Hash::make('old-staff-password');
        $oldTrainer = Hash::make('old-trainer-password');
        $oldPrincipal = Hash::make('old-principal-password');
        $unrelated = Hash::make('unrelated-password');
        $this->insertAdministratorTargets($oldStaff, $oldTrainer);
        DB::table('principal')->insert([
            'principalID' => 'PR900', 'principalName' => 'Protected Account',
            'email' => 'protected@example.test', 'password' => $oldPrincipal,
            'role' => 'principal', 'status' => 'inactive',
        ]);
        DB::table('staff_edu')->insert([
            'staffID' => 'ST901', 'staffName' => 'Unrelated', 'email' => 'unrelated@example.test',
            'password' => $unrelated, 'status' => 'active',
        ]);

        try {
            app(AdministratorPasswordService::class)->reset('RejectedPassword8642');
            $this->fail('An ineligible Administrator identity should have aborted the reset.');
        } catch (\RuntimeException $exception) {
            $this->assertSame(
                'Existing Administrator credential identities are ineligible: principal. '
                .'Correct them before password synchronization.',
                $exception->getMessage(),
            );
        }

        $this->assertSame($oldStaff, DB::table('staff_edu')->where('staffID', 'ST900')->value('password'));
        $this->assertSame($oldTrainer, DB::table('trainer')->where('trainerID', 'TR900')->value('trainerPassword'));
        $this->assertSame($oldPrincipal, DB::table('principal')->where('principalID', 'PR900')->value('password'));
        $this->assertSame($unrelated, DB::table('staff_edu')->where('staffID', 'ST901')->value('password'));
    }

    public function test_generate_mode_displays_password_once_after_atomic_synchronization(): void
    {
        config(['sso.administrator.email' => 'protected@example.test']);
        $this->insertAdministratorTargets(Hash::make('old-staff'), Hash::make('old-trainer'));

        $this->assertSame(0, Artisan::call('elit:administrator-password', ['--generate' => true]));
        $output = Artisan::output();
        $this->assertStringContainsString('Administrator credentials were synchronized atomically.', $output);
        $this->assertStringContainsString('Credential targets updated: 2', $output);
        $this->assertStringContainsString('Canonical roles: staff_edu, trainer', $output);
        preg_match('/Generated password \(shown once\): ([A-Za-z2-9]+)/', $output, $matches);
        $this->assertArrayHasKey(1, $matches);
        $this->assertSame(1, substr_count($output, $matches[1]));
        $this->assertTrue(Hash::check(
            $matches[1],
            DB::table('staff_edu')->where('staffID', 'ST900')->value('password'),
        ));
        $this->assertTrue(Hash::check(
            $matches[1],
            DB::table('trainer')->where('trainerID', 'TR900')->value('trainerPassword'),
        ));
    }

    private function insertAdministratorTargets(string $staffHash, string $trainerHash): void
    {
        DB::table('staff_edu')->insert([
            'staffID' => 'ST900', 'staffName' => 'Protected Account', 'email' => 'protected@example.test',
            'password' => $staffHash, 'status' => 'active',
        ]);
        DB::table('trainer')->insert([
            'trainerID' => 'TR900', 'trainerName' => 'Protected Account',
            'trainerEmail' => 'PROTECTED@EXAMPLE.TEST', 'trainerPassword' => $trainerHash,
            'status' => 'Aktif',
        ]);
    }
}
