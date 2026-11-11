<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Populates training_module, training_content_block, assessment, and
 * assessment_question for course 10 (series — visible in the UI) and
 * basic assessments for courses 19–21.
 */
class ModuleAssessmentSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        // ── Enroll teacher1 in course 10 (series) so they can demo it ──
        if (! DB::table('enroll_teacher')->where('teacherID', 1)->where('courseID', 10)->exists()) {
            DB::table('enroll_teacher')->insert([
                'teacherID'   => 1,
                'courseID'   => 10,
                'status'      => 'approved',
                'applied_at'  => '2026-06-01 09:00:00',
                'created_at'  => $now,
                'updated_at'  => $now,
            ]);
        }

        // ── COURSE 10: Instructional Coaching for School Leaders (series) ──
        $c10 = 10;

        // -- Modules --
        $mod1 = $this->upsertModule($c10, 'Coaching Fundamentals',       1, $now);
        $mod2 = $this->upsertModule($c10, 'Coaching Conversations',       2, $now);
        $mod3 = $this->upsertModule($c10, 'Observation & Feedback',       3, $now);
        $mod4 = $this->upsertModule($c10, 'Assessment & Reflection',      4, $now);

        // -- Assessments (create before blocks so we have their IDs) --
        $quiz10 = $this->upsertAssessment([
            'courseID'          => $c10,
            'type'               => 'quiz',
            'title'              => 'Instructional Coaching Concepts Quiz',
            'status'             => 'Active',
            'module_id'          => $mod2,
            'instruction_text'   => 'Answer all 5 questions. Each question carries 2 marks. You have 20 minutes to complete this quiz.',
            'available_from'     => '2026-10-06 09:00:00',
            'available_until'    => '2026-12-31 23:59:00',
            'time_limit_minutes' => 20,
            'max_attempts'       => 2,
            'total_marks'        => 10.00,
            'weight_percent'     => 20.00,
        ], $now);

        $task10 = $this->upsertAssessment([
            'courseID'        => $c10,
            'type'             => 'submission',
            'title'            => 'Coaching Action Plan Submission',
            'status'           => 'Active',
            'module_id'        => $mod4,
            'instruction_text' => 'Develop a comprehensive Coaching Action Plan for one of your colleagues. Your plan must include: (1) Goals of the coaching cycle, (2) Pre-observation conference notes, (3) Observation findings, (4) Post-observation feedback strategy, and (5) Follow-up milestones. Submit as PDF or Word document.',
            'available_from'   => '2026-10-13 09:00:00',
            'available_until'  => '2026-11-30 23:59:00',
            'accepted_format'  => 'pdf,docx',
            'max_attempts'     => 1,
            'total_marks'      => 100.00,
            'weight_percent'   => 30.00,
        ], $now);

        $quiz10b = $this->upsertAssessment([
            'courseID'          => $c10,
            'type'               => 'quiz',
            'title'              => 'Observation Techniques Self-Check',
            'status'             => 'Active',
            'module_id'          => $mod3,
            'instruction_text'   => 'A short reflective quiz on observation and feedback methodologies covered in Module 3.',
            'available_from'     => '2026-10-20 09:00:00',
            'available_until'    => '2026-12-31 23:59:00',
            'time_limit_minutes' => 15,
            'max_attempts'       => 3,
            'total_marks'        => 10.00,
            'weight_percent'     => 10.00,
        ], $now);

        // -- Content Blocks for Module 1: Coaching Fundamentals --
        $this->upsertBlock($mod1, 'title',      'Welcome to Instructional Coaching',
            'This module introduces the core philosophy and principles of instructional coaching. You will explore what coaching means in an educational context, how it differs from evaluation, and why it is one of the most powerful tools for teacher development.',
            null, null, null, 1, $now);
        $this->upsertBlock($mod1, 'text',       'What Is Instructional Coaching?',
            "Instructional coaching is a collaborative, job-embedded professional development approach. Unlike traditional top-down professional development, coaching is:\n\n• Personalised to each teacher's goals and needs\n• Ongoing rather than one-off\n• Non-evaluative — coaches are not assessors\n• Evidence-based, using classroom observation data\n\nEffective coaches build trust, listen actively, ask powerful questions, and celebrate small wins.",
            null, null, null, 2, $now);
        $this->upsertBlock($mod1, 'youtube',    'Instructional Coaching Overview (Video)',
            'Watch this introduction to the principles of instructional coaching. Pay attention to the pre-observation, observation, and post-observation cycle discussed at the 4:30 mark.',
            'https://www.youtube.com/watch?v=dQw4w9WgXcQ', null, null, 3, $now);
        // Link to existing material: material_id 26 (Instructional_Coaching_Framework.pdf)
        $this->upsertBlock($mod1, 'material',   'Instructional Coaching Framework (PDF)',
            'Download and read the Instructional Coaching Framework before proceeding to Module 2.',
            null, 26, null, 4, $now);

        // -- Content Blocks for Module 2: Coaching Conversations --
        $this->upsertBlock($mod2, 'title',      'The Art of Coaching Conversations',
            'Effective coaching conversations require preparation, active listening, and structured dialogue. This module walks you through the three-phase conversation model: Pre-Observation, In-Observation, and Post-Observation.',
            null, null, null, 1, $now);
        $this->upsertBlock($mod2, 'text',       'Three-Phase Conversation Model',
            "Phase 1 — Pre-Observation Conference\nBefore entering the classroom, the coach meets with the teacher to understand the lesson plan, learning objectives, and specific aspect the teacher wants feedback on. Key questions: What do you want students to learn? How will you know they have learned it?\n\nPhase 2 — Classroom Observation\nThe coach observes the class using a structured data-collection tool. The focus is narrow and agreed upon in Phase 1. No notes on unrelated matters.\n\nPhase 3 — Post-Observation Conference\nWithin 48 hours, the coach and teacher meet to review the data. The coach uses non-judgmental, evidence-based language. The teacher reflects first, then the coach shares findings.",
            null, null, null, 2, $now);
        // Link to material_id 27 (Coaching_Conversation_Slides.pptx)
        $this->upsertBlock($mod2, 'material',   'Coaching Conversation Slides (PPTX)',
            'Presentation slides summarising the three-phase model. Use these as a reference when practising with your coaching partner.',
            null, 27, null, 3, $now);
        $this->upsertBlock($mod2, 'quiz',       'Instructional Coaching Concepts Quiz',
            'Test your understanding of the coaching conversation model. You have 20 minutes and 2 attempts.',
            null, null, $quiz10, 4, $now);

        // -- Content Blocks for Module 3: Observation & Feedback --
        $this->upsertBlock($mod3, 'title',      'Classroom Observation Techniques',
            'This module covers structured observation methods, data collection strategies, and how to deliver evidence-based feedback that motivates teachers to grow.',
            null, null, null, 1, $now);
        $this->upsertBlock($mod3, 'text',       'Evidence-Based Feedback Principles',
            "Effective feedback is:\n\n1. Specific — focuses on observable behaviours, not character ('Students in rows 3–4 were off-task during the first 5 minutes' not 'class was noisy')\n2. Timely — delivered within 24–48 hours of observation\n3. Balanced — acknowledges strengths before areas for growth\n4. Action-oriented — ends with a concrete next step\n5. Dialogic — invites teacher reflection first\n\nAvoid: vague praise ('good lesson!'), evaluative language ('you should have...'), or overloading the teacher with too many points at once.",
            null, null, null, 2, $now);
        // Link to material_id 28 (Observation_Feedback_Form.docx)
        $this->upsertBlock($mod3, 'material',   'Observation & Feedback Form (DOCX)',
            'Download this form to use during your classroom observation. Fill in the sections before, during, and after the observation cycle.',
            null, 28, null, 3, $now);
        $this->upsertBlock($mod3, 'quiz',       'Observation Techniques Self-Check',
            'A short reflective quiz to check your understanding of observation techniques and feedback principles.',
            null, null, $quiz10b, 4, $now);

        // -- Content Blocks for Module 4: Assessment & Reflection --
        $this->upsertBlock($mod4, 'title',      'Measuring Coaching Impact',
            'In the final module, you will learn how to measure the effectiveness of your coaching cycle and document teacher growth. You will also complete your Coaching Action Plan assignment.',
            null, null, null, 1, $now);
        $this->upsertBlock($mod4, 'text',       'Documenting Teacher Growth',
            "A coaching cycle is only as strong as its documentation. Effective coaches maintain:\n\n• Observation notes with timestamps\n• Evidence of student learning (work samples, assessment data)\n• Teacher reflection journals\n• Goal-tracking sheets updated after each coaching conversation\n\nWhen documenting, focus on trends over time rather than isolated incidents. Three data points make a pattern; one data point is anecdote.",
            null, null, null, 2, $now);
        $this->upsertBlock($mod4, 'submission', 'Coaching Action Plan Submission',
            'Submit your completed Coaching Action Plan. Your plan should cover a full coaching cycle with one colleague. See the instructions panel for required sections.',
            null, null, $task10, 3, $now);

        // -- Questions for Quiz 1 (Instructional Coaching Concepts) --
        $this->upsertQuestion($quiz10, 1, 'mcq',
            'What is the PRIMARY purpose of a pre-observation conference?',
            '["Evaluate the teacher\'s lesson plan","Agree on the focus area for observation","Inform the principal of the lesson","Complete the observation form"]',
            'Agree on the focus area for observation', 2.00);

        $this->upsertQuestion($quiz10, 2, 'mcq',
            'Instructional coaching is BEST described as:',
            '["A form of teacher evaluation","A top-down professional development approach","A collaborative, job-embedded development process","A once-a-year performance review"]',
            'A collaborative, job-embedded development process', 2.00);

        $this->upsertQuestion($quiz10, 3, 'true_false',
            'Coaches should give comprehensive feedback covering every aspect of the lesson observed.',
            '["True","False"]',
            'False', 2.00);

        $this->upsertQuestion($quiz10, 4, 'mcq',
            'Post-observation feedback should ideally be delivered:',
            '["Within 1 week","On the same day if possible, within 48 hours","At the end of the school term","Only when the teacher requests it"]',
            'On the same day if possible, within 48 hours', 2.00);

        $this->upsertQuestion($quiz10, 5, 'mcq',
            'Which phrase is an example of SPECIFIC, evidence-based feedback?',
            '["Great lesson, the students really enjoyed it","Your classroom management needs improvement","During minutes 10–15, 6 students in row 2 were off-task while you were at the whiteboard","You should use more questioning techniques"]',
            'During minutes 10–15, 6 students in row 2 were off-task while you were at the whiteboard', 2.00);

        // -- Questions for Quiz 2 (Observation Techniques Self-Check) --
        $this->upsertQuestion($quiz10b, 1, 'mcq',
            'A coach\'s observation notes should focus on:',
            '["Everything that happens in the classroom","Only the negative incidents","The agreed-upon focus area from the pre-conference","The teacher\'s personal teaching style"]',
            'The agreed-upon focus area from the pre-conference', 2.00);

        $this->upsertQuestion($quiz10b, 2, 'true_false',
            'Evaluative language such as "you should have done this" is recommended in feedback conversations.',
            '["True","False"]',
            'False', 2.00);

        $this->upsertQuestion($quiz10b, 3, 'mcq',
            'How many data points are needed before identifying a teaching pattern?',
            '["One","Two","Three or more","Five or more"]',
            'Three or more', 2.00);

        $this->upsertQuestion($quiz10b, 4, 'mcq',
            'Which element should come FIRST in the post-observation conference?',
            '["Coach shares all observations","Teacher self-reflects on the lesson","Principal reviews the data","Coach rates the teacher\'s performance"]',
            'Teacher self-reflects on the lesson', 2.00);

        $this->upsertQuestion($quiz10b, 5, 'true_false',
            'Coaching cycles should be documented to track teacher growth over time.',
            '["True","False"]',
            'True', 2.00);

        // ── COURSES 19–21: assessments (multi_day — table data only) ────
        $this->seedCourse19Assessment($now);
        $this->seedCourse20Assessment($now);
        $this->seedCourse21Assessment($now);
    }

    // ── Course 19 assessment ─────────────────────────────────────────────
    private function seedCourse19Assessment(Carbon $now): void
    {
        $quiz = $this->upsertAssessment([
            'courseID'          => 19,
            'type'               => 'quiz',
            'title'              => 'Teknologi Pendidikan Digital — Penilaian Pengetahuan',
            'status'             => 'Active',
            'module_id'          => null,
            'instruction_text'   => 'Jawab semua soalan. Setiap soalan membawa 2 markah.',
            'available_from'     => '2026-06-24 09:00:00',
            'available_until'    => '2026-12-31 23:59:00',
            'time_limit_minutes' => 15,
            'max_attempts'       => 2,
            'total_marks'        => 10.00,
            'weight_percent'     => 20.00,
        ], $now);

        $this->upsertQuestion($quiz, 1, 'mcq',
            'Apakah ciri utama pembelajaran abad ke-21?',
            '["Berpusatkan guru","Hafalan fakta","Kemahiran berfikir kritis dan kreatif","Peperiksaan bertulis sahaja"]',
            'Kemahiran berfikir kritis dan kreatif', 2.00);
        $this->upsertQuestion($quiz, 2, 'mcq',
            'Antara berikut, yang manakah merupakan alat pedagogi digital?',
            '["Buku teks","Papan hitam","Google Classroom","Pembaris"]',
            'Google Classroom', 2.00);
        $this->upsertQuestion($quiz, 3, 'true_false',
            'Penggunaan teknologi dalam pengajaran secara automatik meningkatkan pembelajaran murid.',
            '["True","False"]',
            'False', 2.00);
        $this->upsertQuestion($quiz, 4, 'mcq',
            'Penilaian berasaskan teknologi membolehkan guru untuk:',
            '["Mengurangkan masa mengajar","Memantau kemajuan murid secara masa nyata","Menghapuskan penilaian formatif","Menggantikan guru sepenuhnya"]',
            'Memantau kemajuan murid secara masa nyata', 2.00);
        $this->upsertQuestion($quiz, 5, 'mcq',
            'Reka bentuk pembelajaran yang bermakna menekankan kepentingan:',
            '["Kelajuan penyampaian isi pelajaran","Hubung kait antara kandungan dengan kehidupan murid","Bilangan slaid pembentangan","Penggunaan buku teks terkini"]',
            'Hubung kait antara kandungan dengan kehidupan murid', 2.00);
    }

    // ── Course 20 assessment ─────────────────────────────────────────────
    private function seedCourse20Assessment(Carbon $now): void
    {
        $task = $this->upsertAssessment([
            'courseID'        => 20,
            'type'             => 'submission',
            'title'            => 'Innovative Pedagogy Reflection Portfolio',
            'status'           => 'Active',
            'module_id'        => null,
            'instruction_text' => 'Submit a reflective portfolio documenting your application of at least two innovative pedagogical strategies learned in this programme. Include: lesson plans, student work samples, and a 500-word reflection on the impact observed.',
            'available_from'   => '2026-06-24 09:00:00',
            'available_until'  => '2026-09-30 23:59:00',
            'accepted_format'  => 'pdf,docx',
            'max_attempts'     => 1,
            'total_marks'      => 100.00,
            'weight_percent'   => 40.00,
        ], $now);

        $quiz = $this->upsertAssessment([
            'courseID'          => 20,
            'type'               => 'quiz',
            'title'              => 'Active Learning Strategies Quiz',
            'status'             => 'Active',
            'module_id'          => null,
            'instruction_text'   => 'Choose the best answer for each question.',
            'available_from'     => '2026-06-24 09:00:00',
            'available_until'    => '2026-12-31 23:59:00',
            'time_limit_minutes' => 20,
            'max_attempts'       => 2,
            'total_marks'        => 10.00,
            'weight_percent'     => 20.00,
        ], $now);

        $this->upsertQuestion($quiz, 1, 'mcq',
            'Active learning is best characterised by:',
            '["Students listening passively to lectures","Students engaging in tasks that require thinking and doing","Students copying notes from the board","Silent independent reading"]',
            'Students engaging in tasks that require thinking and doing', 2.00);
        $this->upsertQuestion($quiz, 2, 'mcq',
            'Which strategy best promotes authentic assessment?',
            '["Multiple choice tests","True/False questions","Project-based tasks connected to real-world problems","Spelling dictation"]',
            'Project-based tasks connected to real-world problems', 2.00);
        $this->upsertQuestion($quiz, 3, 'true_false',
            'Flipped classroom means students do homework in class and watch lectures at home.',
            '["True","False"]',
            'True', 2.00);
        $this->upsertQuestion($quiz, 4, 'mcq',
            'Collaborative learning is effective because it:',
            '["Reduces the teacher\'s workload","Exposes students to diverse perspectives and promotes social skills","Eliminates individual accountability","Makes marking easier"]',
            'Exposes students to diverse perspectives and promotes social skills', 2.00);
        $this->upsertQuestion($quiz, 5, 'mcq',
            'Formative assessment is used to:',
            '["Grade students at the end of a unit","Rank students against each other","Provide ongoing feedback to guide instruction","Determine school rankings"]',
            'Provide ongoing feedback to guide instruction', 2.00);
    }

    // ── Course 21 assessment ─────────────────────────────────────────────
    private function seedCourse21Assessment(Carbon $now): void
    {
        $quiz = $this->upsertAssessment([
            'courseID'          => 21,
            'type'               => 'quiz',
            'title'              => 'Trainer Competency Assessment',
            'status'             => 'Active',
            'module_id'          => null,
            'instruction_text'   => 'This quiz assesses your readiness as a certified trainer. Each question carries 2 marks. Minimum passing score: 70%.',
            'available_from'     => '2026-06-24 09:00:00',
            'available_until'    => '2026-12-31 23:59:00',
            'time_limit_minutes' => 25,
            'max_attempts'       => 2,
            'total_marks'        => 10.00,
            'weight_percent'     => 30.00,
        ], $now);

        $task = $this->upsertAssessment([
            'courseID'        => 21,
            'type'             => 'submission',
            'title'            => 'Training Design Portfolio',
            'status'           => 'Active',
            'module_id'        => null,
            'instruction_text' => 'Design a complete 4-hour training session on a topic of your choice. Submit: training agenda, slide deck, facilitator guide, and participant handout. Include a 300-word rationale explaining your instructional design choices.',
            'available_from'   => '2026-06-24 09:00:00',
            'available_until'  => '2026-10-31 23:59:00',
            'accepted_format'  => 'pdf,docx,pptx',
            'max_attempts'     => 1,
            'total_marks'      => 100.00,
            'weight_percent'   => 40.00,
        ], $now);

        $this->upsertQuestion($quiz, 1, 'mcq',
            'The primary role of a trainer is to:',
            '["Deliver content as fast as possible","Ensure learners achieve the intended learning outcomes","Fill the entire time slot with activities","Impress participants with their expertise"]',
            'Ensure learners achieve the intended learning outcomes', 2.00);
        $this->upsertQuestion($quiz, 2, 'mcq',
            'Needs analysis in training design helps to:',
            '["Reduce training costs","Identify the gap between current and desired performance","Select the training venue","Determine the catering menu"]',
            'Identify the gap between current and desired performance', 2.00);
        $this->upsertQuestion($quiz, 3, 'true_false',
            'Adult learners prefer to understand why they need to learn something before engaging with content.',
            '["True","False"]',
            'True', 2.00);
        $this->upsertQuestion($quiz, 4, 'mcq',
            'Which evaluation level measures whether learning occurred during training?',
            '["Level 1 — Reaction","Level 2 — Learning","Level 3 — Behaviour","Level 4 — Results"]',
            'Level 2 — Learning', 2.00);
        $this->upsertQuestion($quiz, 5, 'mcq',
            'An icebreaker activity at the start of training is designed to:',
            '["Waste time before the real content starts","Test participants\' knowledge","Build rapport and psychological safety in the group","Demonstrate the trainer\'s creativity"]',
            'Build rapport and psychological safety in the group', 2.00);
    }

    // ── Helpers ──────────────────────────────────────────────────────────

    private function upsertModule(int $courseId, string $title, int $order, Carbon $now): int
    {
        $existing = DB::table('training_module')
            ->where('courseID', $courseId)->where('module_title', $title)->first();
        if ($existing) return $existing->module_id;
        return DB::table('training_module')->insertGetId([
            'courseID'    => $courseId,
            'module_title' => $title,
            'module_order' => $order,
            'cover_image'  => null,
            'created_at'   => $now,
            'updated_at'   => $now,
        ]);
    }

    private function upsertAssessment(array $data, Carbon $now): int
    {
        $existing = DB::table('assessment')
            ->where('courseID', $data['courseID'])->where('title', $data['title'])->first();
        if ($existing) return $existing->assessment_id;
        return DB::table('assessment')->insertGetId(array_merge([
            'accepted_format'    => null,
            'time_limit_minutes' => null,
            'max_attempts'       => null,
            'available_from'     => null,
            'available_until'    => null,
            'module_id'          => null,
        ], $data, ['created_at' => $now, 'updated_at' => $now]));
    }

    private function upsertBlock(
        int $moduleId, string $type, string $title, string $content,
        ?string $url, ?int $materialId, ?int $assessmentId,
        int $order, Carbon $now
    ): void {
        $exists = DB::table('training_content_block')
            ->where('module_id', $moduleId)->where('block_order', $order)->exists();
        if ($exists) return;
        DB::table('training_content_block')->insert([
            'module_id'     => $moduleId,
            'block_type'    => $type,
            'block_title'   => $title,
            'block_content' => $content,
            'external_url'  => $url,
            'material_id'   => $materialId,
            'assessment_id' => $assessmentId,
            'block_order'   => $order,
            'is_visible'    => true,
            'created_at'    => $now,
            'updated_at'    => $now,
        ]);
    }

    private function upsertQuestion(
        int $assessmentId, int $order, string $type,
        string $text, string $optionsJson, string $correct, float $marks
    ): void {
        $exists = DB::table('assessment_question')
            ->where('assessment_id', $assessmentId)->where('question_order', $order)->exists();
        if ($exists) return;
        DB::table('assessment_question')->insert([
            'assessment_id'      => $assessmentId,
            'question_text'      => $text,
            'question_image_path'=> null,
            'question_order'     => $order,
            'question_type'      => $type,
            'options_json'       => $optionsJson,
            'correct_answer'     => $correct,
            'marks'              => $marks,
            'grading_rubric'     => null,
        ]);
    }
}
