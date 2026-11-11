<?php

namespace Database\Seeders;

use App\Models\CourseSession;
use App\Models\LearningMaterial;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class LearningMaterialSeeder extends Seeder
{
    public function run(): void
    {
        // [courseID, trainer_id, [[filename, type, days_before_session], ...]]
        $data = [
            [1, 1, [
                ['Inclusive_Education_Overview_Slides.pptx',        'pptx', 5],
                ['Inclusive_Education_Reference_Guide.pdf',          'pdf',  4],
                ['Differentiated_Instruction_Activity_Sheet.pdf',   'pdf',  2],
            ]],
            [2, 2, [
                ['Digital_Pedagogy_Module1_Slides.pptx',            'pptx', 7],
                ['21st_Century_Tools_Reference.pdf',                'pdf',  6],
                ['Flipped_Classroom_Design_Template.docx',          'docx', 3],
            ]],
            [3, 3, [
                ['Classroom_Management_Slides.pptx',                'pptx', 3],
                ['Positive_Behaviour_Support_Guide.pdf',            'pdf',  2],
                ['Behaviour_Tracking_Worksheet.docx',               'docx', 1],
            ]],
            [4, 2, [
                ['STEM_Integration_Slides_Day1.pptx',               'pptx', 6],
                ['STEM_Activity_Booklet.pdf',                       'pdf',  5],
                ['Experiment_Design_Template.docx',                 'docx', 3],
            ]],
            [5, 3, [
                ['Assessment_Techniques_Slides.pptx',               'pptx', 4],
                ['Formative_Assessment_Toolkit.pdf',                'pdf',  3],
            ]],
            [6, 1, [
                ['Special_Education_Awareness_Slides.pptx',         'pptx', 5],
                ['Disability_Awareness_Reference.pdf',              'pdf',  4],
                ['Inclusive_Classroom_Checklist.pdf',               'pdf',  2],
            ]],
            [7, 1, [
                ['Outdoor_Learning_Facilitation_Guide.pdf',         'pdf',  6],
                ['Experiential_Learning_Activity_Pack.pdf',         'pdf',  5],
                ['Safety_Protocol_Outdoor_Activities.docx',         'docx', 3],
            ]],
            [8, 1, [
                ['Al_Amin_School_Development_Slides.pptx',          'pptx', 3],
                ['School_Improvement_Plan_Template.docx',           'docx', 2],
            ]],
            [9, 2, [
                ['PBL_Implementation_Slides.pptx',                  'pptx', 7],
                ['Project_Based_Learning_Guide.pdf',                'pdf',  6],
                ['PBL_Unit_Planning_Template.docx',                 'docx', 4],
            ]],
            [10, 2, [
                ['Instructional_Coaching_Framework.pdf',            'pdf',  5],
                ['Coaching_Conversation_Slides.pptx',               'pptx', 4],
                ['Observation_Feedback_Form.docx',                  'docx', 2],
            ]],
            [11, 1, [
                ['Mental_Health_Wellbeing_Slides.pptx',             'pptx', 4],
                ['Mental_Health_First_Aid_Guide.pdf',               'pdf',  3],
                ['Student_Wellbeing_Assessment_Tool.pdf',           'pdf',  1],
            ]],
            [12, 2, [
                ['ELT_Methodology_Slides_Day1.pptx',                'pptx', 5],
                ['Communicative_Language_Teaching_Guide.pdf',       'pdf',  4],
                ['CEFR_Alignment_Worksheet.docx',                   'docx', 2],
            ]],
            [13, 3, [
                ['School_Leadership_Slides_Day1.pptx',              'pptx', 7],
                ['Transformational_Leadership_Readings.pdf',        'pdf',  6],
                ['Strategic_School_Planning_Template.docx',         'docx', 4],
            ]],
            [14, 1, [
                ['Creative_Arts_Integration_Slides.pptx',           'pptx', 3],
                ['Arts_Across_Curriculum_Resource_Pack.pdf',        'pdf',  2],
            ]],
            [15, 2, [
                ['Data_Driven_Teaching_Slides_Day1.pptx',           'pptx', 10],
                ['Classroom_Data_Analysis_Guide.pdf',               'pdf',  8],
                ['Student_Progress_Monitoring_Template.xlsx',       'xlsx', 6],
            ]],
            [16, 1, [
                ['Islamic_Education_Enhancement_Slides.pptx',       'pptx', 5],
                ['Pendekatan_Tematik_Pendidikan_Islam.pdf',         'pdf',  4],
                ['Aktiviti_Pengayaan_Ibadah.docx',                  'docx', 2],
            ]],
            [17, 3, [
                ['Community_Outreach_Guide.pdf',                    'pdf',  4],
                ['Parent_Engagement_Strategies_Slides.pptx',        'pptx', 3],
                ['PIBG_Communication_Template.docx',                'docx', 1],
            ]],
            [18, 1, [
                ['Classroom_Innovation_Slides.pptx',                'pptx', 3],
                ['21st_Century_Pedagogy_Handbook.pdf',              'pdf',  2],
            ]],
        ];

        foreach ($data as [$courseId, $trainerId, $materials]) {
            $session = CourseSession::where('courseID', $courseId)->orderBy('session_date')->first();
            $baseDate = $session
                ? Carbon::parse($session->session_date)->subDays(0)
                : Carbon::now();

            foreach ($materials as [$filename, $type, $daysBefore]) {
                LearningMaterial::firstOrCreate(
                    ['courseID' => $courseId, 'original_file_name' => $filename],
                    [
                        'trainer_id'          => $trainerId,
                        'session_id'          => $session?->session_id,
                        'original_file_name'  => $filename,
                        'file_path'           => 'materials/course_' . $courseId . '/' . $filename,
                        'material_type'       => $type,
                        'metadata_cleared'    => false,
                        'uploaded_at'         => $baseDate->copy()->subDays($daysBefore),
                    ]
                );
            }
        }
    }
}
