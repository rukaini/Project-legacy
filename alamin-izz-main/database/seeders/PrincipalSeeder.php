<?php

namespace Database\Seeders;

use App\Models\Principal;
use App\Models\School;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class PrincipalSeeder extends Seeder
{
    public function run(): void
    {
        $school1 = School::where('schoolName', 'Sekolah Kebangsaan Al Amin')->first();
        $school2 = School::where('schoolName', 'Sekolah Kebangsaan Seri Setia')->first();

        $principals = [
            [
                'principalName' => 'Guru Besar',
                'ICNumber'      => '700101-14-0001',
                'phoneNumber'   => '010-0000001',
                'email'         => 'principal@edutrain.test',
                'martialStatus' => 'Married',
                'gender'        => 'Male',
                'address'       => 'No 1, Jalan Pengetua, Bandar Baru Bangi, 43000 Kajang, Selangor',
                'race'          => 'Malay',
                'appointedDate' => Carbon::parse('1995-08-01'),
                'pensionDate'   => Carbon::parse('2030-01-01'),
                'latestAge'     => 55,
                'schoolID'      => $school1->schoolID,
                'password'      => Hash::make('password'),
            ],
            [
                'principalName' => 'Zainab binti Hassan',
                'ICNumber'      => '720215-10-2345',
                'phoneNumber'   => '010-9876543',
                'email'         => 'principal2@edutrain.test',
                'martialStatus' => 'Married',
                'gender'        => 'Female',
                'address'       => 'No 3, Jalan Harmoni, Taman Setia, 47500 Petaling Jaya, Selangor',
                'race'          => 'Malay',
                'appointedDate' => Carbon::parse('1998-01-05'),
                'pensionDate'   => Carbon::parse('2032-02-15'),
                'latestAge'     => 53,
                'schoolID'      => $school2->schoolID,
                'password'      => Hash::make('password'),
            ],
        ];

        foreach ($principals as $data) {
            Principal::firstOrCreate(['email' => $data['email']], $data);
        }
    }
}
