<?php

namespace Database\Seeders;

use App\Models\Assessment;
use App\Models\AssessmentQuestion;
use Illuminate\Database\Seeder;

class AssessmentSeeder extends Seeder
{
    public function run(): void
    {
        // Digital Pedagogy in the 21st Century — courseID = 11
        $courseId = 11;

        Assessment::where('courseID', $courseId)->delete();

        // Quiz 1
        $quiz = Assessment::create([
            'courseID'   => $courseId,
            'type'        => 'quiz',
            'title'       => 'Chapter 1 Quiz — Digital Literacy',
            'description' => 'Test your understanding of digital literacy concepts covered in Chapter 1.',
            'deadline'    => now()->addDays(7),
            'max_score'   => 10,
            'status'      => 'active',
        ]);

        AssessmentQuestion::insert([
            [
                'assessment_id'  => $quiz->assessment_id,
                'question_text'  => 'Which of the following best describes digital literacy?',
                'type'           => 'mcq',
                'options'        => json_encode([
                    ['label' => 'A', 'text' => 'The ability to use a computer to type documents'],
                    ['label' => 'B', 'text' => 'The ability to find, evaluate, create, and communicate information using digital technology'],
                    ['label' => 'C', 'text' => 'Knowledge of computer programming languages'],
                    ['label' => 'D', 'text' => 'Using social media for personal communication'],
                ]),
                'correct_option' => 'B',
                'marks'          => 2,
                'order'          => 1,
                'created_at'     => now(),
                'updated_at'     => now(),
            ],
            [
                'assessment_id'  => $quiz->assessment_id,
                'question_text'  => 'What does the term "21st century skills" primarily refer to in education?',
                'type'           => 'mcq',
                'options'        => json_encode([
                    ['label' => 'A', 'text' => 'Skills needed to pass standardized tests'],
                    ['label' => 'B', 'text' => 'Critical thinking, collaboration, communication, and creativity'],
                    ['label' => 'C', 'text' => 'Memorization and recitation skills'],
                    ['label' => 'D', 'text' => 'Skills for operating industrial machinery'],
                ]),
                'correct_option' => 'B',
                'marks'          => 2,
                'order'          => 2,
                'created_at'     => now(),
                'updated_at'     => now(),
            ],
            [
                'assessment_id'  => $quiz->assessment_id,
                'question_text'  => 'Which tool is most appropriate for collaborative real-time document editing in a classroom setting?',
                'type'           => 'mcq',
                'options'        => json_encode([
                    ['label' => 'A', 'text' => 'Microsoft Word (local file)'],
                    ['label' => 'B', 'text' => 'Google Docs'],
                    ['label' => 'C', 'text' => 'Notepad'],
                    ['label' => 'D', 'text' => 'Printed worksheets'],
                ]),
                'correct_option' => 'B',
                'marks'          => 2,
                'order'          => 3,
                'created_at'     => now(),
                'updated_at'     => now(),
            ],
            [
                'assessment_id'  => $quiz->assessment_id,
                'question_text'  => 'Briefly explain how you would use one digital tool to improve student engagement in your classroom.',
                'type'           => 'text',
                'options'        => null,
                'correct_option' => null,
                'marks'          => 4,
                'order'          => 4,
                'created_at'     => now(),
                'updated_at'     => now(),
            ],
        ]);

        // Task 1
        Assessment::create([
            'courseID'           => $courseId,
            'type'                => 'task',
            'title'               => 'Lesson Plan — Technology Integration',
            'description'         => "Prepare a lesson plan that integrates at least ONE digital tool into your subject area.\n\nYour lesson plan must include:\n- Learning objectives\n- Digital tool selected and justification\n- Step-by-step activity description\n- Assessment strategy\n\nSubmit as a PDF or Word document (max 5MB).",
            'deadline'            => now()->addDays(14),
            'max_score'           => 100,
            'accepted_file_types' => '.pdf,.doc,.docx',
            'status'              => 'active',
        ]);
    }
}
