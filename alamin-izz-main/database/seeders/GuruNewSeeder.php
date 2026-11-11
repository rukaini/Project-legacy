<?php

namespace Database\Seeders;

use App\Models\GuruNew;
use App\Models\School;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class GuruNewSeeder extends Seeder
{
    public function run(): void
    {
        $schoolId = School::query()->orderBy('schoolID')->value('schoolID');

        $guruNewRecords = [
            [
                'ic_number'      => '900101101234',
                'gn_name'        => 'Nurul Aisyah binti Roslan',
                'email'          => 'applicant1@edutrain.test',
                'password'       => Hash::make('password'),
                'phone_number'   => '014-1112233',
                'marital_status' => 'Single',
                'gender'         => 'Female',
                'address'        => null,
                'race'           => 'Malay',
                'appointed_date' => now()->subMonths(6)->format('Y-m-d'),
                'current_status' => 'Active',
                'hrID'           => 'HR001',
                'schoolID'       => $schoolId,
            ],
            [
                'ic_number'      => '920202102345',
                'gn_name'        => 'Muhammad Haziq bin Ismail',
                'email'          => 'applicant2@edutrain.test',
                'password'       => Hash::make('password'),
                'phone_number'   => '014-4445566',
                'marital_status' => 'Single',
                'gender'         => 'Male',
                'address'        => null,
                'race'           => 'Malay',
                'appointed_date' => now()->subMonths(3)->format('Y-m-d'),
                'current_status' => 'Active',
                'hrID'           => 'HR001',
                'schoolID'       => $schoolId,
            ],
        ];

        foreach ($guruNewRecords as $data) {
            GuruNew::firstOrCreate(['email' => $data['email']], $data);
        }
    }
}
