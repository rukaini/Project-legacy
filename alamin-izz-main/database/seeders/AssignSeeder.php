<?php

namespace Database\Seeders;

use App\Models\Assign;
use App\Models\Teacher;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class AssignSeeder extends Seeder
{
    public function run(): void
    {
        $teachers = Teacher::with('school')->get();

        foreach ($teachers as $teacher) {
            Assign::firstOrCreate(
                [
                    'schoolID'  => $teacher->schoolID,
                    'teacherID' => $teacher->teacherID,
                ],
                [
                    'assignDate' => $teacher->serviceDate ?? Carbon::now(),
                    'status'     => 'active',
                ]
            );
        }
    }
}
