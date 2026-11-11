<?php

namespace Database\Seeders;

use App\Models\Trainer;
use Illuminate\Database\Seeder;

class TrainerSeeder extends Seeder
{
    public function run(): void
    {
        $trainers = [
            [
                'trainer_name'      => 'Ahmad Razif bin Hassan',
                'trainer_ic'        => '780415-10-5678',
                'trainer_email'     => 'razif.hassan@trainer.test',
                'trainer_phoneNo'   => '012-8765432',
                'expertise'         => 'Special Education, Inclusive Classroom Practices, Learning Disabilities Support',
                'trainer_pic'       => null,
                'invitation_letter' => null,
                'average_rating'    => 4.50,
                'status'            => 'active',
            ],
            [
                'trainer_name'      => 'Dr. Lim Wei Chen',
                'trainer_ic'        => '820901-14-2345',
                'trainer_email'     => 'limweichen@trainer.test',
                'trainer_phoneNo'   => '016-2345678',
                'expertise'         => 'ICT in Education, Digital Pedagogy, Educational Technology, 21st Century Learning',
                'trainer_pic'       => null,
                'invitation_letter' => null,
                'average_rating'    => 4.80,
                'status'            => 'active',
            ],
            [
                'trainer_name'      => 'Nurul Ain binti Malik',
                'trainer_ic'        => '850723-06-9012',
                'trainer_email'     => 'nuraini.malik@trainer.test',
                'trainer_phoneNo'   => '019-3456789',
                'expertise'         => 'Classroom Management, Behaviour Intervention, Student Discipline Strategies',
                'trainer_pic'       => null,
                'invitation_letter' => null,
                'average_rating'    => 4.30,
                'status'            => 'active',
            ],
        ];

        foreach ($trainers as $data) {
            Trainer::firstOrCreate(['trainerEmail' => $data['trainer_email']], $data);
        }
    }
}
