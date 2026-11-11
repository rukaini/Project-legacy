<?php

namespace Database\Seeders;

use App\Models\School;
use App\Models\Teacher;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class TeacherSeeder extends Seeder
{
    public function run(): void
    {
        $school1 = School::where('schoolName', 'Sekolah Kebangsaan Al Amin')->first();
        $school2 = School::where('schoolName', 'Sekolah Kebangsaan Seri Setia')->first();
        $school3 = School::where('schoolName', 'Sekolah Kebangsaan Bukit Damansara')->first();

        $teachers = [
            [
                'teacherName'   => 'Ahmad Firdaus bin Zakaria',
                'ICNumber'      => '850312-10-1234',
                'phoneNumber'   => '012-3456789',
                'email'         => 'teacher1@edutrain.test',
                'martialStatus' => 'Married',
                'gender'        => 'Male',
                'address'       => 'No 12, Jalan Kenanga 3, Taman Maju, 43000 Kajang, Selangor',
                'race'          => 'Malay',
                'appointedDate' => Carbon::parse('2010-03-01'),
                'serviceDate'   => Carbon::parse('2010-03-01'),
                'pensionDate'   => null,
                'latestAge'     => 40,
                'schoolID'      => $school1->schoolID,
                'password'      => Hash::make('password'),
            ],
            [
                'teacherName'   => 'Siti Norehan binti Mahmud',
                'ICNumber'      => '880621-06-5678',
                'phoneNumber'   => '013-7654321',
                'email'         => 'teacher2@edutrain.test',
                'martialStatus' => 'Married',
                'gender'        => 'Female',
                'address'       => 'No 5, Jalan Melati 2, Taman Setia, 47500 Petaling Jaya, Selangor',
                'race'          => 'Malay',
                'appointedDate' => Carbon::parse('2012-07-15'),
                'serviceDate'   => Carbon::parse('2012-07-15'),
                'pensionDate'   => null,
                'latestAge'     => 37,
                'schoolID'      => $school2->schoolID,
                'password'      => Hash::make('password'),
            ],
            [
                'teacherName'   => 'Rajendran a/l Subramaniam',
                'ICNumber'      => '790505-08-9012',
                'phoneNumber'   => '011-2345678',
                'email'         => 'teacher3@edutrain.test',
                'martialStatus' => 'Single',
                'gender'        => 'Male',
                'address'       => 'No 88, Jalan Damansara 4, 50490 Kuala Lumpur',
                'race'          => 'Indian',
                'appointedDate' => Carbon::parse('2005-01-10'),
                'serviceDate'   => Carbon::parse('2005-01-10'),
                'pensionDate'   => Carbon::parse('2039-05-05'),
                'latestAge'     => 46,
                'schoolID'      => $school3->schoolID,
                'password'      => Hash::make('password'),
            ],
        ];

        foreach ($teachers as $data) {
            Teacher::firstOrCreate(['email' => $data['email']], $data);
        }
    }
}
