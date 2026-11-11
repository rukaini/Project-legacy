<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Principal;
use App\Models\School;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

/**
 * Adds 3 new schools, 3 principals, and 9 teachers (teacher21–teacher29).
 * Schools 8–10: SK Kepong, SK Bangsar, SK Setapak.
 */
class ExtraDataSeeder extends Seeder
{
    public function run(): void
    {
        // ── Schools ─────────────────────────────────────────────────────
        $schoolData = [
            [
                'schoolName'    => 'Sekolah Kebangsaan Kepong',
                'schoolAddress' => 'Jalan Kepong 2, Taman Kepong, 52100 Kuala Lumpur',
                'registerDate'  => Carbon::parse('1993-04-12'),
                'phoneNumber'   => '03-62561234',
                'totalTeacher'  => 40,
                'vacancy'       => 3,
            ],
            [
                'schoolName'    => 'Sekolah Kebangsaan Bangsar',
                'schoolAddress' => 'Jalan Bangsar Utama 1, Bangsar, 59000 Kuala Lumpur',
                'registerDate'  => Carbon::parse('1990-08-20'),
                'phoneNumber'   => '03-22821234',
                'totalTeacher'  => 35,
                'vacancy'       => 2,
            ],
            [
                'schoolName'    => 'Sekolah Kebangsaan Setapak',
                'schoolAddress' => 'Jalan Gombak 3, Setapak, 53000 Kuala Lumpur',
                'registerDate'  => Carbon::parse('1996-11-05'),
                'phoneNumber'   => '03-40211234',
                'totalTeacher'  => 42,
                'vacancy'       => 4,
            ],
        ];

        foreach ($schoolData as $data) {
            School::firstOrCreate(['schoolName' => $data['schoolName']], $data);
        }

        $sKepong  = School::where('schoolName', 'Sekolah Kebangsaan Kepong')->first();
        $sBangsar = School::where('schoolName', 'Sekolah Kebangsaan Bangsar')->first();
        $sSetapak = School::where('schoolName', 'Sekolah Kebangsaan Setapak')->first();

        // ── Principals ──────────────────────────────────────────────────
        $principalData = [
            [
                'principalName' => 'Roslan bin Mohd Noor',
                'ICNumber'      => '660514-14-1234',
                'phoneNumber'   => '012-7891234',
                'email'         => 'principal7@edutrain.test',
                'martialStatus' => 'Married',
                'gender'        => 'Male',
                'address'       => 'No 14, Jalan Kepong Baru 4, 52100 Kuala Lumpur',
                'race'          => 'Malay',
                'appointedDate' => Carbon::parse('1998-09-01'),
                'serviceDate'   => Carbon::parse('1998-09-01'),
                'pensionDate'   => Carbon::parse('2026-05-14'),
                'latestAge'     => 59,
                'schoolID'      => $sKepong->schoolID,
                'password'      => Hash::make('password'),
            ],
            [
                'principalName' => 'Wong Siew Fong',
                'ICNumber'      => '720306-10-5678',
                'phoneNumber'   => '019-3451234',
                'email'         => 'principal8@edutrain.test',
                'martialStatus' => 'Married',
                'gender'        => 'Female',
                'address'       => 'No 7, Jalan Bangsar 5, 59000 Kuala Lumpur',
                'race'          => 'Chinese',
                'appointedDate' => Carbon::parse('2002-03-15'),
                'serviceDate'   => Carbon::parse('2002-03-15'),
                'pensionDate'   => Carbon::parse('2032-03-06'),
                'latestAge'     => 53,
                'schoolID'      => $sBangsar->schoolID,
                'password'      => Hash::make('password'),
            ],
            [
                'principalName' => 'Krishnamurthy a/l Pillai',
                'ICNumber'      => '690821-10-9012',
                'phoneNumber'   => '011-5671234',
                'email'         => 'principal9@edutrain.test',
                'martialStatus' => 'Married',
                'gender'        => 'Male',
                'address'       => 'No 22, Jalan Setapak Jaya 3, 53000 Kuala Lumpur',
                'race'          => 'Indian',
                'appointedDate' => Carbon::parse('1999-07-01'),
                'serviceDate'   => Carbon::parse('1999-07-01'),
                'pensionDate'   => Carbon::parse('2029-08-21'),
                'latestAge'     => 56,
                'schoolID'      => $sSetapak->schoolID,
                'password'      => Hash::make('password'),
            ],
        ];

        foreach ($principalData as $data) {
            Principal::firstOrCreate(['email' => $data['email']], $data);
        }

        // ── Teachers ────────────────────────────────────────────────────
        $teacherData = [
            // SK Kepong (teacher21–23)
            [
                'teacherName'   => 'Faizal bin Zainudin',
                'ICNumber'      => '880415-14-2345',
                'phoneNumber'   => '012-4561234',
                'email'         => 'teacher21@edutrain.test',
                'martialStatus' => 'Married',
                'gender'        => 'Male',
                'address'       => 'No 9, Jalan Kepong Permai 2, 52100 Kuala Lumpur',
                'race'          => 'Malay',
                'appointedDate' => Carbon::parse('2012-07-01'),
                'serviceDate'   => Carbon::parse('2012-07-01'),
                'pensionDate'   => null,
                'latestAge'     => 37,
                'schoolID'      => $sKepong->schoolID,
                'password'      => Hash::make('password'),
            ],
            [
                'teacherName'   => 'Lee Hui Yin',
                'ICNumber'      => '920708-14-6789',
                'phoneNumber'   => '016-8901234',
                'email'         => 'teacher22@edutrain.test',
                'martialStatus' => 'Single',
                'gender'        => 'Female',
                'address'       => 'Unit 3-5, Residensi Kepong, 52100 Kuala Lumpur',
                'race'          => 'Chinese',
                'appointedDate' => Carbon::parse('2016-01-04'),
                'serviceDate'   => Carbon::parse('2016-01-04'),
                'pensionDate'   => null,
                'latestAge'     => 33,
                'schoolID'      => $sKepong->schoolID,
                'password'      => Hash::make('password'),
            ],
            [
                'teacherName'   => 'Selvakumar a/l Arumugam',
                'ICNumber'      => '861120-14-3456',
                'phoneNumber'   => '017-2341234',
                'email'         => 'teacher23@edutrain.test',
                'martialStatus' => 'Married',
                'gender'        => 'Male',
                'address'       => 'No 5, Jalan Metro Kepong 4, 52100 Kuala Lumpur',
                'race'          => 'Indian',
                'appointedDate' => Carbon::parse('2010-04-01'),
                'serviceDate'   => Carbon::parse('2010-04-01'),
                'pensionDate'   => null,
                'latestAge'     => 39,
                'schoolID'      => $sKepong->schoolID,
                'password'      => Hash::make('password'),
            ],
            // SK Bangsar (teacher24–26)
            [
                'teacherName'   => 'Nur Izzah binti Abdul Kadir',
                'ICNumber'      => '910903-14-7890',
                'phoneNumber'   => '013-6781234',
                'email'         => 'teacher24@edutrain.test',
                'martialStatus' => 'Married',
                'gender'        => 'Female',
                'address'       => 'No 16, Jalan Bangsar Indah 1, 59000 Kuala Lumpur',
                'race'          => 'Malay',
                'appointedDate' => Carbon::parse('2014-07-01'),
                'serviceDate'   => Carbon::parse('2014-07-01'),
                'pensionDate'   => null,
                'latestAge'     => 34,
                'schoolID'      => $sBangsar->schoolID,
                'password'      => Hash::make('password'),
            ],
            [
                'teacherName'   => 'Tan Chee Keong',
                'ICNumber'      => '870217-10-1234',
                'phoneNumber'   => '018-9012345',
                'email'         => 'teacher25@edutrain.test',
                'martialStatus' => 'Married',
                'gender'        => 'Male',
                'address'       => 'No 3, Jalan Bangsar Baru 6, 59000 Kuala Lumpur',
                'race'          => 'Chinese',
                'appointedDate' => Carbon::parse('2009-09-01'),
                'serviceDate'   => Carbon::parse('2009-09-01'),
                'pensionDate'   => null,
                'latestAge'     => 38,
                'schoolID'      => $sBangsar->schoolID,
                'password'      => Hash::make('password'),
            ],
            [
                'teacherName'   => 'Priya a/p Nadarajan',
                'ICNumber'      => '940512-10-5678',
                'phoneNumber'   => '011-1234567',
                'email'         => 'teacher26@edutrain.test',
                'martialStatus' => 'Single',
                'gender'        => 'Female',
                'address'       => 'Unit 8-2, Bangsar South Residences, 59000 Kuala Lumpur',
                'race'          => 'Indian',
                'appointedDate' => Carbon::parse('2019-01-07'),
                'serviceDate'   => Carbon::parse('2019-01-07'),
                'pensionDate'   => null,
                'latestAge'     => 31,
                'schoolID'      => $sBangsar->schoolID,
                'password'      => Hash::make('password'),
            ],
            // SK Setapak (teacher27–29)
            [
                'teacherName'   => 'Mohd Zulhilmi bin Rashid',
                'ICNumber'      => '830625-14-9012',
                'phoneNumber'   => '012-0123456',
                'email'         => 'teacher27@edutrain.test',
                'martialStatus' => 'Married',
                'gender'        => 'Male',
                'address'       => 'No 38, Jalan Gombak Permai 2, 53000 Kuala Lumpur',
                'race'          => 'Malay',
                'appointedDate' => Carbon::parse('2006-03-01'),
                'serviceDate'   => Carbon::parse('2006-03-01'),
                'pensionDate'   => null,
                'latestAge'     => 42,
                'schoolID'      => $sSetapak->schoolID,
                'password'      => Hash::make('password'),
            ],
            [
                'teacherName'   => 'Chin Li Wen',
                'ICNumber'      => '971109-14-3456',
                'phoneNumber'   => '016-3456789',
                'email'         => 'teacher28@edutrain.test',
                'martialStatus' => 'Single',
                'gender'        => 'Female',
                'address'       => 'Unit 5-1, Setapak Green, 53000 Kuala Lumpur',
                'race'          => 'Chinese',
                'appointedDate' => Carbon::parse('2022-01-03'),
                'serviceDate'   => Carbon::parse('2022-01-03'),
                'pensionDate'   => null,
                'latestAge'     => 28,
                'schoolID'      => $sSetapak->schoolID,
                'password'      => Hash::make('password'),
            ],
            [
                'teacherName'   => 'Vimala a/p Govindasamy',
                'ICNumber'      => '900315-10-7890',
                'phoneNumber'   => '013-4567890',
                'email'         => 'teacher29@edutrain.test',
                'martialStatus' => 'Married',
                'gender'        => 'Female',
                'address'       => 'No 11, Jalan Setapak Murni 3, 53000 Kuala Lumpur',
                'race'          => 'Indian',
                'appointedDate' => Carbon::parse('2013-07-01'),
                'serviceDate'   => Carbon::parse('2013-07-01'),
                'pensionDate'   => null,
                'latestAge'     => 35,
                'schoolID'      => $sSetapak->schoolID,
                'password'      => Hash::make('password'),
            ],
        ];

        foreach ($teacherData as $data) {
            Teacher::firstOrCreate(['email' => $data['email']], $data);
        }

        // ── User accounts + roles ────────────────────────────────────────
        // Principals
        foreach ($principalData as $p) {
            $user = User::firstOrCreate(
                ['email' => $p['email']],
                ['name' => $p['principalName'], 'password' => Hash::make('password')]
            );
            if (! $user->hasRole(Role::GuruBesar->value)) {
                $user->assignRole(Role::GuruBesar->value);
            }
        }

        // Teachers
        foreach ($teacherData as $t) {
            $user = User::firstOrCreate(
                ['email' => $t['email']],
                ['name' => $t['teacherName'], 'password' => Hash::make('password')]
            );
            if (! $user->hasRole(Role::Teacher->value)) {
                $user->assignRole(Role::Teacher->value);
            }
        }
    }
}
