<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Outsider;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Guru Besar — links to principal table via email
        $gb = User::firstOrCreate(
            ['email' => 'principal@edutrain.test'],
            ['name' => 'Guru Besar', 'password' => Hash::make('password')]
        );
        $gb->assignRole(Role::GuruBesar->value);

        // Teachers — standalone records in teacher table (seeded by TeacherSeeder)
        $teacherEmails = [
            ['email' => 'teacher1@edutrain.test', 'name' => 'Ahmad Firdaus bin Zakaria'],
            ['email' => 'teacher2@edutrain.test', 'name' => 'Siti Norehan binti Mahmud'],
            ['email' => 'teacher3@edutrain.test', 'name' => 'Rajendran a/l Subramaniam'],
        ];

        foreach ($teacherEmails as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                ['name' => $data['name'], 'password' => Hash::make('password')]
            );
            $user->assignRole(Role::Teacher->value);
        }

        // HR
        $hr = User::firstOrCreate(
            ['email' => 'hr@edutrain.test'],
            ['name' => 'HR Officer', 'password' => Hash::make('password')]
        );
        $hr->assignRole(Role::HR->value);

        // Guru Baru — User accounts linked to GuruNew table via email
        $guruBaruAccounts = [
            ['email' => 'applicant1@edutrain.test', 'name' => 'Guru Baru One'],
            ['email' => 'applicant2@edutrain.test', 'name' => 'Guru Baru Two'],
        ];
        foreach ($guruBaruAccounts as $data) {
            $gb = User::firstOrCreate(
                ['email' => $data['email']],
                ['name' => $data['name'], 'password' => Hash::make('password')]
            );
            $gb->assignRole(Role::GuruBaru->value);
        }

        // Outsider
        $outsider = User::firstOrCreate(
            ['email' => 'outsider@edutrain.test'],
            ['name' => 'Outsider User', 'password' => Hash::make('password')]
        );
        $outsider->assignRole(Role::Outsider->value);
        Outsider::firstOrCreate(
            ['user_id' => $outsider->id],
            [
                'name'        => 'Outsider User',
                'ICNumber'    => '900101-14-9999',
                'phoneNumber' => '011-0000001',
                'gender'      => 'Male',
            ]
        );
    }
}
