<?php

namespace Database\Seeders;

use App\Models\Principal;
use App\Models\School;
use App\Models\Teacher;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class MoreSchoolsSeeder extends Seeder
{
    public function run(): void
    {
        $schools = [
            [
                'schoolName'    => 'Sekolah Kebangsaan Cheras',
                'schoolAddress' => 'Jalan Cheras Perdana 1, Taman Cheras Perdana, 56100 Kuala Lumpur',
                'registerDate'  => Carbon::parse('1992-01-10'),
                'phoneNumber'   => '03-91301234',
                'totalTeacher'  => 41,
                'vacancy'       => 4,
            ],
            [
                'schoolName'    => 'Sekolah Kebangsaan Ampang',
                'schoolAddress' => 'Jalan Hulu Ampang, 68000 Ampang, Selangor',
                'registerDate'  => Carbon::parse('1988-06-20'),
                'phoneNumber'   => '03-42571234',
                'totalTeacher'  => 36,
                'vacancy'       => 2,
            ],
            [
                'schoolName'    => 'Sekolah Kebangsaan Wangsa Maju',
                'schoolAddress' => 'Jalan 2/27A, Seksyen 2, Wangsa Maju, 53300 Kuala Lumpur',
                'registerDate'  => Carbon::parse('1997-08-15'),
                'phoneNumber'   => '03-41422345',
                'totalTeacher'  => 44,
                'vacancy'       => 3,
            ],
            [
                'schoolName'    => 'Sekolah Kebangsaan Pandan Indah',
                'schoolAddress' => 'Jalan Pandan Indah 4/2, Pandan Indah, 55100 Kuala Lumpur',
                'registerDate'  => Carbon::parse('2001-03-05'),
                'phoneNumber'   => '03-42962345',
                'totalTeacher'  => 33,
                'vacancy'       => 1,
            ],
        ];

        foreach ($schools as $data) {
            School::firstOrCreate(['schoolName' => $data['schoolName']], $data);
        }

        $s4 = School::where('schoolName', 'Sekolah Kebangsaan Cheras')->first();
        $s5 = School::where('schoolName', 'Sekolah Kebangsaan Ampang')->first();
        $s6 = School::where('schoolName', 'Sekolah Kebangsaan Wangsa Maju')->first();
        $s7 = School::where('schoolName', 'Sekolah Kebangsaan Pandan Indah')->first();

        // ── Principals ──────────────────────────────────────────────
        $principals = [
            [
                'principalName' => 'Hairul Nizam bin Abdul Razak',
                'ICNumber'      => '680320-14-5678',
                'phoneNumber'   => '012-3081234',
                'email'         => 'principal3@edutrain.test',
                'martialStatus' => 'Married',
                'gender'        => 'Male',
                'address'       => 'No 7, Jalan Cheras 3, Taman Cheras, 56100 Kuala Lumpur',
                'race'          => 'Malay',
                'appointedDate' => Carbon::parse('2000-07-01'),
                'serviceDate'   => Carbon::parse('2000-07-01'),
                'pensionDate'   => Carbon::parse('2028-03-20'),
                'latestAge'     => 57,
                'schoolID'      => $s4->schoolID,
                'password'      => Hash::make('password'),
            ],
            [
                'principalName' => 'Lim Bee Geok',
                'ICNumber'      => '710812-10-3456',
                'phoneNumber'   => '019-4561234',
                'email'         => 'principal4@edutrain.test',
                'martialStatus' => 'Married',
                'gender'        => 'Female',
                'address'       => 'No 22, Jalan Ampang Utama 2, 68000 Ampang, Selangor',
                'race'          => 'Chinese',
                'appointedDate' => Carbon::parse('2003-01-20'),
                'serviceDate'   => Carbon::parse('2003-01-20'),
                'pensionDate'   => Carbon::parse('2031-08-12'),
                'latestAge'     => 54,
                'schoolID'      => $s5->schoolID,
                'password'      => Hash::make('password'),
            ],
            [
                'principalName' => 'Suresh a/l Nair',
                'ICNumber'      => '740605-14-7890',
                'phoneNumber'   => '011-7890123',
                'email'         => 'principal5@edutrain.test',
                'martialStatus' => 'Married',
                'gender'        => 'Male',
                'address'       => 'No 15, Jalan Wangsa Maju 5, 53300 Kuala Lumpur',
                'race'          => 'Indian',
                'appointedDate' => Carbon::parse('2005-09-01'),
                'serviceDate'   => Carbon::parse('2005-09-01'),
                'pensionDate'   => Carbon::parse('2034-06-05'),
                'latestAge'     => 51,
                'schoolID'      => $s6->schoolID,
                'password'      => Hash::make('password'),
            ],
            [
                'principalName' => 'Norashikin binti Ramli',
                'ICNumber'      => '760918-14-2345',
                'phoneNumber'   => '013-6781234',
                'email'         => 'principal6@edutrain.test',
                'martialStatus' => 'Married',
                'gender'        => 'Female',
                'address'       => 'No 9, Jalan Pandan Maju 3, 55100 Kuala Lumpur',
                'race'          => 'Malay',
                'appointedDate' => Carbon::parse('2008-04-15'),
                'serviceDate'   => Carbon::parse('2008-04-15'),
                'pensionDate'   => Carbon::parse('2036-09-18'),
                'latestAge'     => 49,
                'schoolID'      => $s7->schoolID,
                'password'      => Hash::make('password'),
            ],
        ];

        foreach ($principals as $data) {
            Principal::firstOrCreate(['email' => $data['email']], $data);
        }

        // ── Teachers ─────────────────────────────────────────────────
        $teachers = [
            // SK Cheras (school 4)
            [
                'teacherName'   => 'Muhd Hafizuddin bin Ismail',
                'ICNumber'      => '900412-14-1234',
                'phoneNumber'   => '016-3451234',
                'email'         => 'teacher9@edutrain.test',
                'martialStatus' => 'Single',
                'gender'        => 'Male',
                'address'       => 'No 3, Jalan Cheras 7, 56100 Kuala Lumpur',
                'race'          => 'Malay',
                'appointedDate' => Carbon::parse('2015-07-01'),
                'serviceDate'   => Carbon::parse('2015-07-01'),
                'pensionDate'   => null,
                'latestAge'     => 35,
                'schoolID'      => $s4->schoolID,
                'password'      => Hash::make('password'),
            ],
            [
                'teacherName'   => 'Tan Mei Ling',
                'ICNumber'      => '921130-14-5678',
                'phoneNumber'   => '017-8901234',
                'email'         => 'teacher10@edutrain.test',
                'martialStatus' => 'Single',
                'gender'        => 'Female',
                'address'       => 'Unit 5-3, Aston Kiara, 56100 Kuala Lumpur',
                'race'          => 'Chinese',
                'appointedDate' => Carbon::parse('2017-01-10'),
                'serviceDate'   => Carbon::parse('2017-01-10'),
                'pensionDate'   => null,
                'latestAge'     => 33,
                'schoolID'      => $s4->schoolID,
                'password'      => Hash::make('password'),
            ],
            [
                'teacherName'   => 'Kavitha a/p Selvam',
                'ICNumber'      => '881004-10-3456',
                'phoneNumber'   => '012-5671234',
                'email'         => 'teacher11@edutrain.test',
                'martialStatus' => 'Married',
                'gender'        => 'Female',
                'address'       => 'No 11, Jalan Manis 2, Taman Cheras Maju, 56100 Kuala Lumpur',
                'race'          => 'Indian',
                'appointedDate' => Carbon::parse('2013-03-15'),
                'serviceDate'   => Carbon::parse('2013-03-15'),
                'pensionDate'   => null,
                'latestAge'     => 37,
                'schoolID'      => $s4->schoolID,
                'password'      => Hash::make('password'),
            ],
            // SK Ampang (school 5)
            [
                'teacherName'   => 'Mohd Fadzli bin Kamaruddin',
                'ICNumber'      => '870715-10-7890',
                'phoneNumber'   => '013-2341234',
                'email'         => 'teacher12@edutrain.test',
                'martialStatus' => 'Married',
                'gender'        => 'Male',
                'address'       => 'No 6, Jalan Hulu Ampang 4, 68000 Ampang, Selangor',
                'race'          => 'Malay',
                'appointedDate' => Carbon::parse('2011-08-01'),
                'serviceDate'   => Carbon::parse('2011-08-01'),
                'pensionDate'   => null,
                'latestAge'     => 38,
                'schoolID'      => $s5->schoolID,
                'password'      => Hash::make('password'),
            ],
            [
                'teacherName'   => 'Nurul Ain binti Malik',
                'ICNumber'      => '930228-10-2345',
                'phoneNumber'   => '018-4561234',
                'email'         => 'teacher13@edutrain.test',
                'martialStatus' => 'Single',
                'gender'        => 'Female',
                'address'       => 'No 18, Jalan Ampang Jaya 5, 68000 Ampang, Selangor',
                'race'          => 'Malay',
                'appointedDate' => Carbon::parse('2018-01-02'),
                'serviceDate'   => Carbon::parse('2018-01-02'),
                'pensionDate'   => null,
                'latestAge'     => 32,
                'schoolID'      => $s5->schoolID,
                'password'      => Hash::make('password'),
            ],
            [
                'teacherName'   => 'Yong Chun Keat',
                'ICNumber'      => '840910-10-6789',
                'phoneNumber'   => '016-7891234',
                'email'         => 'teacher14@edutrain.test',
                'martialStatus' => 'Married',
                'gender'        => 'Male',
                'address'       => 'No 2, Jalan Ampang Hilir 3, 68000 Ampang, Selangor',
                'race'          => 'Chinese',
                'appointedDate' => Carbon::parse('2009-04-01'),
                'serviceDate'   => Carbon::parse('2009-04-01'),
                'pensionDate'   => null,
                'latestAge'     => 41,
                'schoolID'      => $s5->schoolID,
                'password'      => Hash::make('password'),
            ],
            // SK Wangsa Maju (school 6)
            [
                'teacherName'   => 'Salmah binti Othman',
                'ICNumber'      => '910322-14-3456',
                'phoneNumber'   => '011-3456789',
                'email'         => 'teacher15@edutrain.test',
                'martialStatus' => 'Married',
                'gender'        => 'Female',
                'address'       => 'No 45, Jalan Wangsa Delima 5, 53300 Kuala Lumpur',
                'race'          => 'Malay',
                'appointedDate' => Carbon::parse('2016-07-01'),
                'serviceDate'   => Carbon::parse('2016-07-01'),
                'pensionDate'   => null,
                'latestAge'     => 34,
                'schoolID'      => $s6->schoolID,
                'password'      => Hash::make('password'),
            ],
            [
                'teacherName'   => 'Ravi a/l Chandran',
                'ICNumber'      => '860504-10-9012',
                'phoneNumber'   => '012-6781234',
                'email'         => 'teacher16@edutrain.test',
                'martialStatus' => 'Married',
                'gender'        => 'Male',
                'address'       => 'No 8, Jalan Wangsa Maju 3, 53300 Kuala Lumpur',
                'race'          => 'Indian',
                'appointedDate' => Carbon::parse('2010-01-15'),
                'serviceDate'   => Carbon::parse('2010-01-15'),
                'pensionDate'   => null,
                'latestAge'     => 39,
                'schoolID'      => $s6->schoolID,
                'password'      => Hash::make('password'),
            ],
            [
                'teacherName'   => 'Nor Hidayah binti Zulkifli',
                'ICNumber'      => '950614-14-1234',
                'phoneNumber'   => '019-2341234',
                'email'         => 'teacher17@edutrain.test',
                'martialStatus' => 'Single',
                'gender'        => 'Female',
                'address'       => 'Unit 12-2, Vista Wangsa, 53300 Kuala Lumpur',
                'race'          => 'Malay',
                'appointedDate' => Carbon::parse('2020-03-01'),
                'serviceDate'   => Carbon::parse('2020-03-01'),
                'pensionDate'   => null,
                'latestAge'     => 30,
                'schoolID'      => $s6->schoolID,
                'password'      => Hash::make('password'),
            ],
            // SK Pandan Indah (school 7)
            [
                'teacherName'   => 'Azizah binti Hamdan',
                'ICNumber'      => '830118-14-5678',
                'phoneNumber'   => '013-5671234',
                'email'         => 'teacher18@edutrain.test',
                'martialStatus' => 'Married',
                'gender'        => 'Female',
                'address'       => 'No 30, Jalan Pandan Indah 3, 55100 Kuala Lumpur',
                'race'          => 'Malay',
                'appointedDate' => Carbon::parse('2007-09-01'),
                'serviceDate'   => Carbon::parse('2007-09-01'),
                'pensionDate'   => null,
                'latestAge'     => 42,
                'schoolID'      => $s7->schoolID,
                'password'      => Hash::make('password'),
            ],
            [
                'teacherName'   => 'Chong Wei Liang',
                'ICNumber'      => '960205-14-9012',
                'phoneNumber'   => '017-2345678',
                'email'         => 'teacher19@edutrain.test',
                'martialStatus' => 'Single',
                'gender'        => 'Male',
                'address'       => 'No 4, Jalan Pandan Jaya 5, 55100 Kuala Lumpur',
                'race'          => 'Chinese',
                'appointedDate' => Carbon::parse('2021-07-01'),
                'serviceDate'   => Carbon::parse('2021-07-01'),
                'pensionDate'   => null,
                'latestAge'     => 29,
                'schoolID'      => $s7->schoolID,
                'password'      => Hash::make('password'),
            ],
            [
                'teacherName'   => 'Thilagavathi a/p Murugan',
                'ICNumber'      => '890807-14-3456',
                'phoneNumber'   => '016-9012345',
                'email'         => 'teacher20@edutrain.test',
                'martialStatus' => 'Married',
                'gender'        => 'Female',
                'address'       => 'No 17, Jalan Pandan Maju 1, 55100 Kuala Lumpur',
                'race'          => 'Indian',
                'appointedDate' => Carbon::parse('2014-01-06'),
                'serviceDate'   => Carbon::parse('2014-01-06'),
                'pensionDate'   => null,
                'latestAge'     => 36,
                'schoolID'      => $s7->schoolID,
                'password'      => Hash::make('password'),
            ],
        ];

        foreach ($teachers as $data) {
            Teacher::firstOrCreate(['email' => $data['email']], $data);
        }
    }
}
