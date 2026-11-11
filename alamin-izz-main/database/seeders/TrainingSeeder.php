<?php

namespace Database\Seeders;

use App\Models\Training;
use App\Models\User;
use Illuminate\Database\Seeder;

class TrainingSeeder extends Seeder
{
    public function run(): void
    {
        $gb = User::where('email', 'principal@edutrain.test')->first();

        // Open, public — outsiders can apply
        Training::firstOrCreate(
            ['title' => 'Digital Pedagogy for 21st Century Educators'],
            [
                'user_id'               => $gb->id,
                'description'           => 'Explore digital tools and methodologies for modern classroom teaching.',
                'date'                  => now()->addDays(30)->toDateString(),
                'time'                  => '08:00:00',
                'location'              => 'Dewan Utama, Al Amin Edu Oasis',
                'capacity'              => 30,
                'status'                => 'open',
                'registration_deadline' => now()->addDays(20)->toDateString(),
                'category'              => 'LADAP',
                'competency_area'       => 'Digital Literacy',
                'trainer_name'          => 'Dr. Ahmad Fauzi',
                'duration_hours'        => 8.0,
                'is_public'             => true,
            ]
        );

        // Open, internal — teachers only
        Training::firstOrCreate(
            ['title' => 'Classroom Management Strategies'],
            [
                'user_id'               => $gb->id,
                'description'           => 'Advanced techniques for managing diverse classroom environments.',
                'date'                  => now()->addDays(45)->toDateString(),
                'time'                  => '09:00:00',
                'location'              => 'Bilik Mesyuarat, Blok B',
                'capacity'              => 20,
                'status'               => 'open',
                'registration_deadline' => now()->addDays(35)->toDateString(),
                'category'              => 'LADAP',
                'competency_area'       => 'Classroom Management',
                'trainer_name'          => 'Pn. Siti Rahimah',
                'duration_hours'        => 6.0,
                'is_public'             => false,
            ]
        );

        // Completed, past date — for history/feedback demo
        Training::firstOrCreate(
            ['title' => 'Introduction to Inclusive Education'],
            [
                'user_id'               => $gb->id,
                'description'           => 'Understanding and implementing inclusive education practices.',
                'date'                  => now()->subDays(30)->toDateString(),
                'time'                  => '08:30:00',
                'location'              => 'Dewan Utama, Al Amin Edu Oasis',
                'capacity'              => 25,
                'status'                => 'completed',
                'registration_deadline' => now()->subDays(40)->toDateString(),
                'category'              => 'LADAP',
                'competency_area'       => 'Special Education',
                'trainer_name'          => 'Dr. Nurul Huda',
                'duration_hours'        => 7.0,
                'is_public'             => true,
            ]
        );
    }
}
