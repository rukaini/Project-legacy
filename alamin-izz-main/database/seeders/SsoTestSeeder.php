<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\School;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * One test account per SSO role, for trying Al Amin Login locally.
 *
 * Idempotent (safe to re-run). Local/testing only — refuses to run in production.
 *
 * IMPORTANT: for each account, EMAIL (and, for Teacher/Principal/New Teacher, the
 * fixed ID) must equal the test account's identity in Al Amin Login, otherwise the
 * receiver rejects the sign-in. Edit the values below to match the gateway.
 * All accounts share the TEST_PASSWORD below (local/testing only).
 *
 *   php artisan db:seed --class=SsoTestSeeder
 */
class SsoTestSeeder extends Seeder
{
    private const TEST_PASSWORD = 'password';

    private const ACCOUNTS = [
        'teacher'   => ['id' => 'TC9001', 'name' => 'Test Teacher',     'email' => 'sso.teacher@edutrain.test'],
        'principal' => ['id' => 'PR9001', 'name' => 'Test Principal',   'email' => 'sso.principal@edutrain.test'],
        'guru_baru' => ['id' => 'GN9001', 'name' => 'Test New Teacher', 'email' => 'sso.newteacher@edutrain.test'],
        'hr'        => ['id' => 'HR9001', 'name' => 'Test HR',          'email' => 'sso.hr@edutrain.test'],
        'outsider'  => ['id' => null,     'name' => 'Test Outsider',    'email' => 'sso.outsider@edutrain.test'],
    ];

    public function run(): void
    {
        abort_if(app()->environment('production'), 1, 'SsoTestSeeder must not run in production.');

        $this->call(RoleSeeder::class);

        $school = School::firstOrCreate(
            ['schoolName' => 'SSO Test School'],
            [
                'schoolAddress' => 'Test address',
                'registerDate'  => now()->toDateString(),
                'phoneNumber'   => '03-00000000',
                'totalTeacher'  => 1,
                'vacancy'       => 0,
                'capacity'      => 1,
            ],
        );

        $a = self::ACCOUNTS;
        $unused = fn (): string => Hash::make(self::TEST_PASSWORD);

        // HR: hr_administrator (guru_new.hrid FK) + users row with the Spatie role.
        DB::table('hr_administrator')->updateOrInsert(['hrid' => $a['hr']['id']], [
            'username'     => $a['hr']['name'],
            'email'        => $a['hr']['email'],
            'password'     => $unused(),
            'phone_number' => '0100000000',
            'role'         => 'hr',
        ]);
        $this->userWithRole($a['hr'], Role::HR);

        // Teacher, assigned to the test school.
        DB::table('teacher')->updateOrInsert(['teacherID' => $a['teacher']['id']], [
            'teacherName' => $a['teacher']['name'],
            'email'       => $a['teacher']['email'],
            'password'    => $unused(),
            'role'        => 'teacher',
        ]);
        DB::table('assign')->updateOrInsert(
            ['schoolID' => $school->schoolID, 'teacherID' => $a['teacher']['id']],
            ['assignDate' => now(), 'status' => 'active'],
        );

        // Principal (guru_besar).
        DB::table('principal')->updateOrInsert(['principalID' => $a['principal']['id']], [
            'principalName' => $a['principal']['name'],
            'email'         => $a['principal']['email'],
            'password'      => $unused(),
            'role'          => 'principal',
            'status'        => 'active',
            'schoolID'      => $school->schoolID,
        ]);

        // New teacher (guru_baru).
        DB::table('guru_new')->updateOrInsert(['gn_id' => $a['guru_baru']['id']], [
            'ic_number'      => '000000-00-0000',
            'gn_name'        => $a['guru_baru']['name'],
            'phone_number'   => '0100000000',
            'email'          => $a['guru_baru']['email'],
            'appointed_date' => now()->toDateString(),
            'role'           => 'new_teacher',
            'current_status' => 'active',
            'hrid'           => $a['hr']['id'],
            'schoolID'       => $school->schoolID,
            'password'       => $unused(),
        ]);

        // Outsider: users row + Spatie role + outsider profile.
        $outsider = $this->userWithRole($a['outsider'], Role::Outsider);
        DB::table('outsider')->updateOrInsert(['user_id' => $outsider->id], [
            'name' => $a['outsider']['name'],
        ]);
    }

    /** @param array{name: string, email: string} $account */
    private function userWithRole(array $account, Role $role): User
    {
        $user = User::updateOrCreate(
            ['email' => $account['email']],
            ['name' => $account['name'], 'password' => self::TEST_PASSWORD],
        );
        $user->syncRoles([$role->value]);

        return $user;
    }
}
