<?php

namespace Database\Seeders;

use App\Models\School;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class SchoolSeeder extends Seeder
{
    public function run(): void
    {
        $schools = [
            [
                'schoolName'    => 'Sekolah Kebangsaan Al Amin',
                'schoolAddress' => 'Jalan Utama 1, Bandar Baru Bangi, 43000 Kajang, Selangor',
                'registerDate'  => Carbon::parse('2000-01-15'),
                'phoneNumber'   => '03-89251234',
                'totalTeacher'  => 45,
                'vacancy'       => 3,
                'capacity'      => 48,
            ],
            [
                'schoolName'    => 'Sekolah Kebangsaan Seri Setia',
                'schoolAddress' => 'Jalan Setia 5, Taman Seri Setia, 47500 Petaling Jaya, Selangor',
                'registerDate'  => Carbon::parse('1998-06-01'),
                'phoneNumber'   => '03-78013456',
                'totalTeacher'  => 38,
                'vacancy'       => 2,
                'capacity'      => 40,
            ],
            [
                'schoolName'    => 'Sekolah Kebangsaan Bukit Damansara',
                'schoolAddress' => 'Jalan Damansara, 50490 Kuala Lumpur',
                'registerDate'  => Carbon::parse('1995-03-10'),
                'phoneNumber'   => '03-20941234',
                'totalTeacher'  => 52,
                'vacancy'       => 5,
                'capacity'      => 57,
            ],
        ];

        foreach ($schools as $data) {
            School::firstOrCreate(['schoolName' => $data['schoolName']], $data);
        }
    }
}
