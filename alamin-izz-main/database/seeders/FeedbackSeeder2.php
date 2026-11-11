<?php

namespace Database\Seeders;

use App\Models\FeedbackCategory;
use App\Models\FeedbackForm;
use App\Models\FeedbackQuestion;
use Illuminate\Database\Seeder;

class FeedbackSeeder2 extends Seeder
{
    public function run(): void
    {
        $forms = [
            // Course 11 — single (Mental Health & Wellbeing), Trainer 1
            [
                'courseID'        => 11,
                'title'            => 'Program Evaluation — Mental Health & Wellbeing in Schools',
                'createdByStaff' => 'HR001',
                'createdDate'     => '2026-05-01 08:00:00',
                'questions'        => [
                    ['category' => 'Program Content', 'questionText' => 'The content covered was relevant to the mental health challenges I face in my school.', 'questionType' => 'likert', 'options_json' => null, 'isRequired' => true, 'questionOrder' => 1],
                    ['category' => 'Program Content', 'questionText' => 'I gained practical strategies to support student mental health and wellbeing.', 'questionType' => 'likert', 'options_json' => null, 'isRequired' => true, 'questionOrder' => 2],
                    ['category' => 'Trainer',         'questionText' => 'Rate the trainer\'s ability to address sensitive mental health topics professionally.', 'questionType' => 'rating', 'options_json' => null, 'isRequired' => true, 'questionOrder' => 3],
                    ['category' => 'Overall',         'questionText' => 'I feel more equipped to identify and support students experiencing mental health difficulties.', 'questionType' => 'yes_no', 'options_json' => null, 'isRequired' => true, 'questionOrder' => 4],
                    ['category' => 'Overall',         'questionText' => 'Please share any comments or suggestions to improve this programme.', 'questionType' => 'paragraph', 'options_json' => null, 'isRequired' => false, 'questionOrder' => 5],
                ],
            ],
            // Course 12 — multi_day (English Language Teaching), Trainer 2
            [
                'courseID'        => 12,
                'title'            => 'Course Feedback — English Language Teaching Methodology',
                'createdByStaff' => 'HR001',
                'createdDate'     => '2026-05-05 09:00:00',
                'questions'        => [
                    ['category' => 'Program Content', 'questionText' => 'The ELT methodology sessions were well-structured and easy to follow.', 'questionType' => 'likert', 'options_json' => null, 'isRequired' => true, 'questionOrder' => 1],
                    ['category' => 'Program Content', 'questionText' => 'Which aspect of the programme was most beneficial to you?', 'questionType' => 'multiple_choice', 'options_json' => '["Communicative Language Teaching","CEFR alignment","Task-based learning","Assessment strategies"]', 'isRequired' => false, 'questionOrder' => 2],
                    ['category' => 'Trainer',         'questionText' => 'The trainer demonstrated strong command of English and effective teaching techniques.', 'questionType' => 'likert', 'options_json' => null, 'isRequired' => true, 'questionOrder' => 3],
                    ['category' => 'Trainer',         'questionText' => 'Rate the overall quality of the trainer\'s instruction and delivery.', 'questionType' => 'rating', 'options_json' => null, 'isRequired' => true, 'questionOrder' => 4],
                    ['category' => 'Overall',         'questionText' => 'What improvements would you suggest for future ELT methodology programmes?', 'questionType' => 'paragraph', 'options_json' => null, 'isRequired' => false, 'questionOrder' => 5],
                ],
            ],
            // Course 13 — multi_day (Leadership & School Management), Trainer 3
            [
                'courseID'        => 13,
                'title'            => 'Program Evaluation — Leadership & School Management',
                'createdByStaff' => 'HR001',
                'createdDate'     => '2026-04-12 08:30:00',
                'questions'        => [
                    ['category' => 'Program Content', 'questionText' => 'The leadership frameworks discussed are applicable to my current school context.', 'questionType' => 'likert', 'options_json' => null, 'isRequired' => true, 'questionOrder' => 1],
                    ['category' => 'Program Content', 'questionText' => 'I gained new insights on staff management and performance appraisal.', 'questionType' => 'likert', 'options_json' => null, 'isRequired' => true, 'questionOrder' => 2],
                    ['category' => 'Trainer',         'questionText' => 'Rate the trainer\'s knowledge and practical experience in school leadership.', 'questionType' => 'rating', 'options_json' => null, 'isRequired' => true, 'questionOrder' => 3],
                    ['category' => 'Overall',         'questionText' => 'Would you recommend this programme to colleagues in leadership roles?', 'questionType' => 'yes_no', 'options_json' => null, 'isRequired' => true, 'questionOrder' => 4],
                    ['category' => 'Overall',         'questionText' => 'What topics would you like covered in a follow-up leadership programme?', 'questionType' => 'paragraph', 'options_json' => null, 'isRequired' => false, 'questionOrder' => 5],
                ],
            ],
            // Course 14 — single (Creative Arts Integration), Trainer 1
            [
                'courseID'        => 14,
                'title'            => 'Feedback Form — Creative Arts Integration in Learning',
                'createdByStaff' => 'HR001',
                'createdDate'     => '2026-06-10 09:00:00',
                'questions'        => [
                    ['category' => 'Program Content', 'questionText' => 'The creative arts activities were practical and directly usable in my classroom.', 'questionType' => 'likert', 'options_json' => null, 'isRequired' => true, 'questionOrder' => 1],
                    ['category' => 'Program Content', 'questionText' => 'Which arts integration area will you apply most in your teaching?', 'questionType' => 'multiple_choice', 'options_json' => '["Visual arts","Drama & role play","Music integration","Storytelling"]', 'isRequired' => false, 'questionOrder' => 2],
                    ['category' => 'Trainer',         'questionText' => 'Rate the trainer\'s creativity and ability to demonstrate arts integration techniques.', 'questionType' => 'rating', 'options_json' => null, 'isRequired' => true, 'questionOrder' => 3],
                    ['category' => 'Overall',         'questionText' => 'I feel more confident integrating creative arts across different subjects.', 'questionType' => 'yes_no', 'options_json' => null, 'isRequired' => true, 'questionOrder' => 4],
                    ['category' => 'Overall',         'questionText' => 'Please share any feedback or suggestions for this programme.', 'questionType' => 'paragraph', 'options_json' => null, 'isRequired' => false, 'questionOrder' => 5],
                ],
            ],
            // Course 15 — multi_day (Data-Driven Teaching), Trainer 2
            [
                'courseID'        => 15,
                'title'            => 'Program Evaluation — Data-Driven Teaching Practices',
                'createdByStaff' => 'HR001',
                'createdDate'     => '2026-07-01 08:00:00',
                'questions'        => [
                    ['category' => 'Program Content', 'questionText' => 'The data analysis tools and techniques taught are practical for classroom use.', 'questionType' => 'likert', 'options_json' => null, 'isRequired' => true, 'questionOrder' => 1],
                    ['category' => 'Program Content', 'questionText' => 'I can now collect and interpret classroom data to inform my teaching decisions.', 'questionType' => 'likert', 'options_json' => null, 'isRequired' => true, 'questionOrder' => 2],
                    ['category' => 'Trainer',         'questionText' => 'Rate the trainer\'s ability to explain data concepts in a clear, teacher-friendly way.', 'questionType' => 'rating', 'options_json' => null, 'isRequired' => true, 'questionOrder' => 3],
                    ['category' => 'Overall',         'questionText' => 'Would you attend a follow-up session on advanced data-driven strategies?', 'questionType' => 'yes_no', 'options_json' => null, 'isRequired' => true, 'questionOrder' => 4],
                    ['category' => 'Overall',         'questionText' => 'What additional support would help you apply data-driven practices in your school?', 'questionType' => 'paragraph', 'options_json' => null, 'isRequired' => false, 'questionOrder' => 5],
                ],
            ],
            // Course 16 — multi_day (Islamic Education Enhancement), Trainer 1
            [
                'courseID'        => 16,
                'title'            => 'Program Evaluation — Islamic Education Enhancement Workshop',
                'createdByStaff' => 'HR001',
                'createdDate'     => '2026-05-15 08:00:00',
                'questions'        => [
                    ['category' => 'Program Content', 'questionText' => 'The workshop content enriched my understanding of contemporary Islamic education approaches.', 'questionType' => 'likert', 'options_json' => null, 'isRequired' => true, 'questionOrder' => 1],
                    ['category' => 'Program Content', 'questionText' => 'The strategies shared are applicable within the current Pendidikan Islam curriculum.', 'questionType' => 'likert', 'options_json' => null, 'isRequired' => true, 'questionOrder' => 2],
                    ['category' => 'Trainer',         'questionText' => 'Rate the trainer\'s depth of knowledge in Islamic pedagogy and curriculum delivery.', 'questionType' => 'rating', 'options_json' => null, 'isRequired' => true, 'questionOrder' => 3],
                    ['category' => 'Overall',         'questionText' => 'I would recommend this workshop to other Pendidikan Islam teachers.', 'questionType' => 'yes_no', 'options_json' => null, 'isRequired' => true, 'questionOrder' => 4],
                    ['category' => 'Overall',         'questionText' => 'What topics should be included in future Islamic education enhancement programmes?', 'questionType' => 'paragraph', 'options_json' => null, 'isRequired' => false, 'questionOrder' => 5],
                ],
            ],
            // Course 17 — single (Community Outreach), Trainer 3
            [
                'courseID'        => 17,
                'title'            => 'Feedback Form — Community Outreach & Parent Engagement',
                'createdByStaff' => 'HR001',
                'createdDate'     => '2026-05-20 08:30:00',
                'questions'        => [
                    ['category' => 'Program Content', 'questionText' => 'The strategies for engaging parents and the community were practical and relevant.', 'questionType' => 'likert', 'options_json' => null, 'isRequired' => true, 'questionOrder' => 1],
                    ['category' => 'Program Content', 'questionText' => 'Which parent engagement strategy do you plan to implement first?', 'questionType' => 'multiple_choice', 'options_json' => '["Regular parent-teacher dialogues","Digital communication channels","Community volunteer programmes","Home visit initiatives"]', 'isRequired' => false, 'questionOrder' => 2],
                    ['category' => 'Trainer',         'questionText' => 'The trainer provided useful examples of successful school-community partnerships.', 'questionType' => 'likert', 'options_json' => null, 'isRequired' => true, 'questionOrder' => 3],
                    ['category' => 'Trainer',         'questionText' => 'Rate the overall quality of the programme facilitation.', 'questionType' => 'rating', 'options_json' => null, 'isRequired' => true, 'questionOrder' => 4],
                    ['category' => 'Overall',         'questionText' => 'Any suggestions for strengthening this community outreach programme?', 'questionType' => 'paragraph', 'options_json' => null, 'isRequired' => false, 'questionOrder' => 5],
                ],
            ],
            // Course 18 — single (Classroom Innovation), Trainer 1
            [
                'courseID'        => 18,
                'title'            => 'Feedback Form — Classroom Innovation & 21st Century Teaching',
                'createdByStaff' => 'HR001',
                'createdDate'     => '2026-06-17 08:00:00',
                'questions'        => [
                    ['category' => 'Program Content', 'questionText' => 'The innovative teaching strategies shared are applicable in my daily classroom practice.', 'questionType' => 'likert', 'options_json' => null, 'isRequired' => true, 'questionOrder' => 1],
                    ['category' => 'Program Content', 'questionText' => 'I gained new ideas on how to engage 21st century learners more effectively.', 'questionType' => 'likert', 'options_json' => null, 'isRequired' => true, 'questionOrder' => 2],
                    ['category' => 'Trainer',         'questionText' => 'Rate the trainer\'s delivery style and use of innovative teaching demonstrations.', 'questionType' => 'rating', 'options_json' => null, 'isRequired' => true, 'questionOrder' => 3],
                    ['category' => 'Overall',         'questionText' => 'I would recommend this workshop to other teachers in my school.', 'questionType' => 'yes_no', 'options_json' => null, 'isRequired' => true, 'questionOrder' => 4],
                    ['category' => 'Overall',         'questionText' => 'Please share your overall experience and any suggestions for improvement.', 'questionType' => 'paragraph', 'options_json' => null, 'isRequired' => false, 'questionOrder' => 5],
                ],
            ],
        ];

        foreach ($forms as $formData) {
            $questions = $formData['questions'];
            unset($formData['questions']);

            // Skip if course doesn't exist (prevents FK failure when a course was removed)
            if (!\Illuminate\Support\Facades\DB::table('course')->where('courseID', $formData['courseID'])->exists()) continue;

            $existing = FeedbackForm::where('courseID', $formData['courseID'])->first();
            if ($existing) continue;

            $form = FeedbackForm::create($formData);

            $categoryOrders = [];
            foreach ($questions as $q) {
                $categoryOrders[$q['category']] ??= count($categoryOrders) + 1;
                $q['categoryID'] = FeedbackCategory::firstOrCreateForForm(
                    $form->formID, $q['category'], $categoryOrders[$q['category']]
                )->categoryID;
                unset($q['category']);
                FeedbackQuestion::create(array_merge(['formID' => $form->formID], $q));
            }
        }
    }
}
