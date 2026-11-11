<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class OutsiderRegistrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->string('external_id')->nullable();
            $table->timestamps();
        });
        Schema::create('outsider', function (Blueprint $table): void {
            $table->id('outsider_id');
            $table->string('name')->nullable();
            $table->string('ICNumber')->nullable()->unique();
            $table->string('phoneNumber')->nullable();
            $table->string('gender')->nullable();
            $table->string('organization')->nullable();
            $table->string('contact_no')->nullable();
            $table->unsignedBigInteger('user_id')->index();
            $table->timestamps();
        });
        Schema::create('roles', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
        });
        Schema::create('model_has_roles', function (Blueprint $table): void {
            $table->unsignedBigInteger('role_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->primary(['role_id', 'model_type', 'model_id']);
        });
    }

    public function test_registration_page_loads_and_login_links_to_it(): void
    {
        $this->get('/register')
            ->assertOk()
            ->assertSee('Create account')
            ->assertSee('Register for an account.')
            ->assertDontSee('Outsider registration')
            ->assertDontSee('Outsider')
            ->assertSee('Full Name')
            ->assertSee('Use at least 8 characters.')
            ->assertSee('minlength="8"', false)
            ->assertSee('name="name"', false)
            ->assertSee('name="email"', false)
            ->assertSee('name="password"', false)
            ->assertSee('name="password_confirmation"', false);

        $this->get('/register')
            ->assertDontSee('portal-footer', false)
            ->assertDontSee('&copy;', false)
            ->assertDontSee('Al Amin</footer>', false);

        $this->get('/login')
            ->assertOk()
            ->assertSee('Create account')
            ->assertSee('href="'.route('register').'"', false);
    }

    public function test_valid_registration_creates_the_canonical_outsider_relationships(): void
    {
        $this->insertOutsiderRole(37);

        $this->post('/register', [
            'name' => 'Public Learner',
            'email' => '  LEARNER@Example.Test ',
            'password' => 'secure-password',
            'password_confirmation' => 'secure-password',
        ])->assertRedirect('/login')
            ->assertSessionHas('status', 'Account created successfully. You can now sign in.');

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('outsider', 1);
        $this->assertDatabaseCount('model_has_roles', 1);

        $user = DB::table('users')->sole();
        $this->assertSame('learner@example.test', $user->email);
        $this->assertNotSame('secure-password', $user->password);
        $this->assertTrue(Hash::check('secure-password', $user->password));
        $this->assertDatabaseHas('outsider', [
            'name' => 'Public Learner',
            'user_id' => $user->id,
        ]);
        $this->assertDatabaseHas('model_has_roles', [
            'role_id' => 37,
            'model_type' => 'App\\Models\\User',
            'model_id' => $user->id,
        ]);
        $this->assertFalse(Schema::hasTable('migrations'));
    }

    public function test_duplicate_email_is_rejected_without_changing_the_existing_user(): void
    {
        $existingPassword = Hash::make('original-password');
        DB::table('users')->insert([
            'name' => 'Existing User',
            'email' => 'existing@example.test',
            'password' => $existingPassword,
        ]);
        $this->insertOutsiderRole();

        $this->from('/register')->post('/register', [
            'name' => 'Different Person',
            'email' => ' EXISTING@EXAMPLE.TEST ',
            'password' => 'replacement-password',
            'password_confirmation' => 'replacement-password',
        ])->assertRedirect('/register')
            ->assertSessionHasErrors('email');

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('outsider', 0);
        $this->assertDatabaseCount('model_has_roles', 0);
        $this->assertSame($existingPassword, DB::table('users')->value('password'));
    }

    public function test_password_confirmation_is_required(): void
    {
        $this->insertOutsiderRole();

        $this->from('/register')->post('/register', [
            'name' => 'Public Learner',
            'email' => 'learner@example.test',
            'password' => 'secure-password',
        ])->assertRedirect('/register')
            ->assertSessionHasErrors('password');

        $this->assertDatabaseCount('users', 0);
    }

    public function test_missing_outsider_role_rolls_back_user_and_profile_inserts(): void
    {
        $this->from('/register')->post('/register', $this->validPayload())
            ->assertRedirect('/register')
            ->assertSessionHasErrors('registration');

        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('outsider', 0);
        $this->assertDatabaseCount('model_has_roles', 0);
    }

    public function test_failed_outsider_insert_does_not_leave_a_partial_user(): void
    {
        $this->insertOutsiderRole();
        Schema::drop('outsider');

        $this->from('/register')->post('/register', $this->validPayload())
            ->assertRedirect('/register')
            ->assertSessionHasErrors('registration');

        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('model_has_roles', 0);
    }

    public function test_failed_role_assignment_rolls_back_user_and_profile_inserts(): void
    {
        $this->insertOutsiderRole();
        Schema::drop('model_has_roles');

        $this->from('/register')->post('/register', $this->validPayload())
            ->assertRedirect('/register')
            ->assertSessionHasErrors('registration');

        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('outsider', 0);
    }

    private function insertOutsiderRole(int $id = 3): void
    {
        DB::table('roles')->insert([
            'id' => $id,
            'name' => 'outsider',
            'guard_name' => 'web',
        ]);
    }

    private function validPayload(): array
    {
        return [
            'name' => 'Public Learner',
            'email' => 'learner@example.test',
            'password' => 'secure-password',
            'password_confirmation' => 'secure-password',
        ];
    }
}
