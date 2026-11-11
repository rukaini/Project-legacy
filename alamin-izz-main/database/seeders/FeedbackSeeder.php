<?php

namespace Database\Seeders;

use App\Models\FeedbackCategory;
use App\Models\FeedbackForm;
use App\Models\FeedbackQuestion;
use Illuminate\Database\Seeder;

class FeedbackSeeder extends Seeder
{
    public function run(): void
    {
        $forms = [
            // Course 1 — single (Inclusive Education), Trainer 1 (Ahmad Razif)
            [
                'courseID'       => 1,
                'title'           => 'Program Evaluation — Introduction to Inclusive Education',
                'createdByStaff'=> 'HR001',
                'createdDate'    => '2026-06-01 08:00:00',
                'questions'       => [
                    ['category' => 'Program Content', 'questionText' => 'The program content was relevant to my teaching needs.', 'questionType' => 'likert', 'options_json' => null, 'isRequired' => true, 'questionOrder' => 1],
                    ['category' => 'Program Content', 'questionText' => 'The materials provided were clear and easy to understand.', 'questionType' => 'likert', 'options_json' => null, 'isRequired' => true, 'questionOrder' => 2],
                    ['category' => 'Trainer',         'questionText' => 'The trainer was knowledgeable about the subject matter.', 'questionType' => 'rating', 'options_json' => null, 'isRequired' => true, 'questionOrder' => 3],
                    ['category' => 'Trainer',         'questionText' => 'The trainer communicated clearly and kept participants engaged.', 'questionType' => 'rating', 'options_json' => null, 'isRequired' => true, 'questionOrder' => 4],
                    ['category' => 'Overall',         'questionText' => 'I would recommend this program to my colleagues.', 'questionType' => 'yes_no', 'options_json' => null, 'isRequired' => true, 'questionOrder' => 5],
                    ['category' => 'Overall',         'questionText' => 'Please share any additional comments or suggestions for improvement.', 'questionType' => 'paragraph', 'options_json' => null, 'isRequired' => false, 'questionOrder' => 6],
                ],
            ],
            // Course 2 — series (Digital Pedagogy), Trainer 2 (Dr. Lim Wei Chen)
            [
                'courseID'       => 2,
                'title'           => 'Course Feedback — Digital Pedagogy for 21st Century Educators',
                'createdByStaff'=> 'HR001',
                'createdDate'    => '2026-06-02 09:00:00',
                'questions'       => [
                    ['category' => 'Module Delivery', 'questionText' => 'The course modules were well-structured and easy to follow.', 'questionType' => 'likert', 'options_json' => null, 'isRequired' => true, 'questionOrder' => 1],
                    ['category' => 'Module Delivery', 'questionText' => 'The online materials (videos, documents) enhanced my learning experience.', 'questionType' => 'likert', 'options_json' => null, 'isRequired' => true, 'questionOrder' => 2],
                    ['category' => 'Digital Skills', 'questionText' => 'I feel more confident using digital tools in my classroom after completing this course.', 'questionType' => 'yes_no', 'options_json' => null, 'isRequired' => true, 'questionOrder' => 3],
                    ['category' => 'Trainer',         'questionText' => 'Rate the overall quality of the trainer\'s instruction and delivery.', 'questionType' => 'rating', 'options_json' => null, 'isRequired' => true, 'questionOrder' => 4],
                    ['category' => 'Trainer',         'questionText' => 'Which aspect of this course was most valuable to you?', 'questionType' => 'multiple_choice', 'options_json' => '["Digital tools integration","Lesson planning strategies","Assessment strategies","Student engagement techniques"]', 'isRequired' => false, 'questionOrder' => 5],
                    ['category' => 'Overall',         'questionText' => 'Overall, how satisfied are you with this program?', 'questionType' => 'likert', 'options_json' => null, 'isRequired' => true, 'questionOrder' => 6],
                ],
            ],
            // Course 3 — multi_day (Classroom Management), Trainer 3 (Nurul Ain)
            [
                'courseID'       => 3,
                'title'           => 'Feedback Form — Classroom Management Strategies',
                'createdByStaff'=> 'HR001',
                'createdDate'    => '2026-06-03 08:30:00',
                'questions'       => [
                    ['category' => 'Program Content', 'questionText' => 'The strategies taught are practical and directly applicable in my classroom.', 'questionType' => 'likert', 'options_json' => null, 'isRequired' => true, 'questionOrder' => 1],
                    ['category' => 'Program Content', 'questionText' => 'Which classroom management strategy will you apply first?', 'questionType' => 'multiple_choice', 'options_json' => '["Positive reinforcement","Structured routines","Restorative practices","Behaviour contracts"]', 'isRequired' => false, 'questionOrder' => 2],
                    ['category' => 'Trainer',         'questionText' => 'The trainer provided useful real-world examples and case studies.', 'questionType' => 'likert', 'options_json' => null, 'isRequired' => true, 'questionOrder' => 3],
                    ['category' => 'Trainer',         'questionText' => 'Rate the trainer\'s ability to handle participant questions and discussions.', 'questionType' => 'rating', 'options_json' => null, 'isRequired' => true, 'questionOrder' => 4],
                    ['category' => 'Overall',         'questionText' => 'What would you like to see improved in future sessions?', 'questionType' => 'paragraph', 'options_json' => null, 'isRequired' => false, 'questionOrder' => 5],
                ],
            ],
            // Course 4 — multi_day (STEM Integration), Trainer 2 (Dr. Lim)
            [
                'courseID'       => 4,
                'title'           => 'Program Evaluation — STEM Integration Programme for Secondary Teachers',
                'createdByStaff'=> 'HR001',
                'createdDate'    => '2026-06-04 09:00:00',
                'questions'       => [
                    ['category' => 'Program Content', 'questionText' => 'The STEM activities were engaging and well-aligned with the secondary curriculum.', 'questionType' => 'likert', 'options_json' => null, 'isRequired' => true, 'questionOrder' => 1],
                    ['category' => 'Program Content', 'questionText' => 'I gained practical skills I can apply immediately in my STEM lessons.', 'questionType' => 'likert', 'options_json' => null, 'isRequired' => true, 'questionOrder' => 2],
                    ['category' => 'Trainer',         'questionText' => 'Rate the trainer\'s ability to explain complex STEM concepts clearly.', 'questionType' => 'rating', 'options_json' => null, 'isRequired' => true, 'questionOrder' => 3],
                    ['category' => 'Overall',         'questionText' => 'Would you attend a follow-up STEM integration programme?', 'questionType' => 'yes_no', 'options_json' => null, 'isRequired' => true, 'questionOrder' => 4],
                    ['category' => 'Overall',         'questionText' => 'Any suggestions for improving the STEM content or delivery?', 'questionType' => 'paragraph', 'options_json' => null, 'isRequired' => false, 'questionOrder' => 5],
                ],
            ],
            // Course 5 — single (Effective Assessment), Trainer 3 (Nurul Ain)
            [
                'courseID'       => 5,
                'title'           => 'Feedback Form — Effective Assessment and Feedback Techniques',
                'createdByStaff'=> 'HR001',
                'createdDate'    => '2026-06-04 10:00:00',
                'questions'       => [
                    ['category' => 'Program Content', 'questionText' => 'The assessment techniques presented are practical for my classroom context.', 'questionType' => 'likert', 'options_json' => null, 'isRequired' => true, 'questionOrder' => 1],
                    ['category' => 'Program Content', 'questionText' => 'Which assessment method will you prioritise implementing first?', 'questionType' => 'multiple_choice', 'options_json' => '["Formative quizzes","Peer assessment","Portfolio-based assessment","Rubric-guided tasks"]', 'isRequired' => false, 'questionOrder' => 2],
                    ['category' => 'Trainer',         'questionText' => 'The trainer provided clear examples and demonstrations.', 'questionType' => 'likert', 'options_json' => null, 'isRequired' => true, 'questionOrder' => 3],
                    ['category' => 'Overall',         'questionText' => 'Overall satisfaction with this programme.', 'questionType' => 'rating', 'options_json' => null, 'isRequired' => true, 'questionOrder' => 4],
                    ['category' => 'Overall',         'questionText' => 'Additional comments or suggestions.', 'questionType' => 'paragraph', 'options_json' => null, 'isRequired' => false, 'questionOrder' => 5],
                ],
            ],
            // Course 6 — single (Special Education), Trainer 1 (Ahmad Razif)
            [
                'courseID'       => 6,
                'title'           => 'Program Evaluation — Special Education Awareness Workshop',
                'createdByStaff'=> 'HR001',
                'createdDate'    => '2026-06-05 08:00:00',
                'questions'       => [
                    ['category' => 'Awareness',  'questionText' => 'My understanding of special education needs has improved after this workshop.', 'questionType' => 'likert', 'options_json' => null, 'isRequired' => true, 'questionOrder' => 1],
                    ['category' => 'Awareness',  'questionText' => 'I feel more confident supporting students with learning disabilities in my classroom.', 'questionType' => 'yes_no', 'options_json' => null, 'isRequired' => true, 'questionOrder' => 2],
                    ['category' => 'Trainer',    'questionText' => 'The trainer demonstrated strong expertise in special education.', 'questionType' => 'rating', 'options_json' => null, 'isRequired' => true, 'questionOrder' => 3],
                    ['category' => 'Overall',    'questionText' => 'This workshop should be mandatory for all teachers.', 'questionType' => 'yes_no', 'options_json' => null, 'isRequired' => true, 'questionOrder' => 4],
                    ['category' => 'Overall',    'questionText' => 'What topic would you like covered in a follow-up session?', 'questionType' => 'short_text', 'options_json' => null, 'isRequired' => false, 'questionOrder' => 5],
                ],
            ],
            // Course 7 — multi_day (Outdoor Learning), Trainer 3 (Nurul Ain)
            [
                'courseID'       => 7,
                'title'           => 'Feedback Form — Outdoor and Experiential Learning Facilitation',
                'createdByStaff'=> 'HR001',
                'createdDate'    => '2026-06-05 09:00:00',
                'questions'       => [
                    ['category' => 'Program Content', 'questionText' => 'The outdoor activities were safe, well-organised, and educationally purposeful.', 'questionType' => 'likert', 'options_json' => null, 'isRequired' => true, 'questionOrder' => 1],
                    ['category' => 'Program Content', 'questionText' => 'I can adapt these experiential activities for my school context.', 'questionType' => 'likert', 'options_json' => null, 'isRequired' => true, 'questionOrder' => 2],
                    ['category' => 'Trainer',         'questionText' => 'Rate the facilitation quality of the trainer during outdoor sessions.', 'questionType' => 'rating', 'options_json' => null, 'isRequired' => true, 'questionOrder' => 3],
                    ['category' => 'Overall',         'questionText' => 'What aspect of the programme was most impactful?', 'questionType' => 'multiple_choice', 'options_json' => '["Team-building activities","Nature-based learning","Reflection sessions","Problem-solving challenges"]', 'isRequired' => false, 'questionOrder' => 4],
                    ['category' => 'Overall',         'questionText' => 'Please share any safety concerns or logistical suggestions.', 'questionType' => 'paragraph', 'options_json' => null, 'isRequired' => false, 'questionOrder' => 5],
                ],
            ],
            // Course 8 — single (Al Amin School Development), Trainer 1 (Ahmad Razif)
            [
                'courseID'       => 8,
                'title'           => 'Program Evaluation — Al Amin School Development Workshop',
                'createdByStaff'=> 'HR001',
                'createdDate'    => '2026-06-06 08:00:00',
                'questions'       => [
                    ['category' => 'Program Content', 'questionText' => 'The workshop content was relevant to our school\'s development goals.', 'questionType' => 'likert', 'options_json' => null, 'isRequired' => true, 'questionOrder' => 1],
                    ['category' => 'Program Content', 'questionText' => 'The strategies shared are actionable within our current school environment.', 'questionType' => 'likert', 'options_json' => null, 'isRequired' => true, 'questionOrder' => 2],
                    ['category' => 'Trainer',         'questionText' => 'Rate the trainer\'s knowledge and presentation of school development strategies.', 'questionType' => 'rating', 'options_json' => null, 'isRequired' => true, 'questionOrder' => 3],
                    ['category' => 'Overall',         'questionText' => 'I would recommend this workshop to colleagues at other schools.', 'questionType' => 'yes_no', 'options_json' => null, 'isRequired' => true, 'questionOrder' => 4],
                    ['category' => 'Overall',         'questionText' => 'What school development topic should be prioritised in future workshops?', 'questionType' => 'short_text', 'options_json' => null, 'isRequired' => false, 'questionOrder' => 5],
                ],
            ],
            // Course 9 — multi_day (Project-Based Learning), Trainer 2 (Dr. Lim)
            [
                'courseID'       => 9,
                'title'           => 'Feedback Form — Project-Based Learning Implementation Workshop',
                'createdByStaff'=> 'HR001',
                'createdDate'    => '2026-06-06 09:00:00',
                'questions'       => [
                    ['category' => 'Program Content', 'questionText' => 'I understand how to design and implement a PBL unit after this workshop.', 'questionType' => 'likert', 'options_json' => null, 'isRequired' => true, 'questionOrder' => 1],
                    ['category' => 'Program Content', 'questionText' => 'Which PBL component was most challenging to understand?', 'questionType' => 'multiple_choice', 'options_json' => '["Driving question design","Assessment rubrics","Student collaboration management","Curriculum alignment"]', 'isRequired' => false, 'questionOrder' => 2],
                    ['category' => 'Trainer',         'questionText' => 'The trainer modelled PBL approaches effectively.', 'questionType' => 'likert', 'options_json' => null, 'isRequired' => true, 'questionOrder' => 3],
                    ['category' => 'Trainer',         'questionText' => 'Rate the trainer\'s overall effectiveness.', 'questionType' => 'rating', 'options_json' => null, 'isRequired' => true, 'questionOrder' => 4],
                    ['category' => 'Overall',         'questionText' => 'I plan to implement a PBL project in my class within the next semester.', 'questionType' => 'yes_no', 'options_json' => null, 'isRequired' => true, 'questionOrder' => 5],
                ],
            ],
            // Course 10 — series (Instructional Coaching), Trainer 1 (Ahmad Razif)
            [
                'courseID'       => 10,
                'title'           => 'Course Feedback — Instructional Coaching for School Leaders',
                'createdByStaff'=> 'HR001',
                'createdDate'    => '2026-06-07 08:00:00',
                'questions'       => [
                    ['category' => 'Coaching Skills',  'questionText' => 'The coaching frameworks introduced are applicable to my leadership role.', 'questionType' => 'likert', 'options_json' => null, 'isRequired' => true, 'questionOrder' => 1],
                    ['category' => 'Coaching Skills',  'questionText' => 'I feel equipped to coach teachers in my school after this programme.', 'questionType' => 'yes_no', 'options_json' => null, 'isRequired' => true, 'questionOrder' => 2],
                    ['category' => 'Trainer',          'questionText' => 'The trainer effectively modelled coaching conversations and feedback techniques.', 'questionType' => 'likert', 'options_json' => null, 'isRequired' => true, 'questionOrder' => 3],
                    ['category' => 'Trainer',          'questionText' => 'Rate the overall quality of the trainer\'s instruction.', 'questionType' => 'rating', 'options_json' => null, 'isRequired' => true, 'questionOrder' => 4],
                    ['category' => 'Overall',          'questionText' => 'What aspect of instructional coaching do you want to develop further?', 'questionType' => 'short_text', 'options_json' => null, 'isRequired' => false, 'questionOrder' => 5],
                ],
            ],
        ];

        foreach ($forms as $formData) {

            $questions = $formData['questions'];
            unset($formData['questions']);

            $form = FeedbackForm::create($formData);

            $categoryOrders = [];
            foreach ($questions as $q) {
                $categoryOrders[$q['category']] ??= count($categoryOrders) + 1;
                $q['categoryID'] = FeedbackCategory::firstOrCreateForForm(
                    $form->formID, $q['category'], $categoryOrders[$q['category']]
                )->categoryID;
                unset($q['category'], $q['options_json']);
                // feedback_question.questionType only supports rating/paragraph/image
                $q['questionType'] = $q['questionType'] === 'rating' ? 'rating' : 'paragraph';
                FeedbackQuestion::create($q);
            }
        }
    }
}
