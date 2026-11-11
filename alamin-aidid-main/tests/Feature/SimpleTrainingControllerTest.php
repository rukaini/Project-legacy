<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SimpleTrainingControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite');

        $this->createTables();
        $this->seedSimpleTraining();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_overview_and_participant_pages_render_shared_read_only_data(): void
    {
        $session = ['user_role' => 'trainer', 'user_id' => 7];

        $this->withSession($session)
            ->get(route('trainer.simple.overview', 10))
            ->assertOk()
            ->assertSee('Simple Training Course')
            ->assertSee('Training Session')
            ->assertSee('Teacher One')
            ->assertSee('Teaching Plan')
            ->assertSee('Teaching plan upload required')
            ->assertSee(route('trainer.simple.materials', 10), false)
            ->assertSee(route('trainer.simple.participants', 10), false)
            ->assertSee(route('trainer.simple.attendance', 10), false);

        $this->withSession($session)
            ->get(route('trainer.courses.teaching-plans.index', 10))
            ->assertOk()
            ->assertSee('aria-current="page"', false)
            ->assertSee('Upload Required')
            ->assertSee('Upload Teaching Plan');

        $this->withSession($session)
            ->get(route('trainer.simple.participants', 10))
            ->assertOk()
            ->assertSee('Teacher One')
            ->assertSee('teacher@example.test')
            ->assertSee('012-345 6789')
            ->assertSee('Not available')
            ->assertSee('Phone Number')
            ->assertSee('Organisation / Department')
            ->assertDontSee('Participant Type')
            ->assertDontSee('bi-person-badge', false);
    }

    public function test_attendance_page_renders_external_records_without_writing_them(): void
    {
        $before = DB::table('attendance')->get()->map(fn ($row) => (array) $row)->all();

        $response = $this->withSession(['user_role' => 'trainer', 'user_id' => 7])
            ->get(route('trainer.simple.attendance', ['course_id' => 10, 'session_id' => 20]))
            ->assertOk()
            ->assertSee('Teacher One')
            ->assertSee('Recorded')
            ->assertDontSee('Approved')
            ->assertDontSee('Pending')
            ->assertDontSee('Rejected')
            ->assertDontSee('owner status not interpreted')
            ->assertDontSee('sw-attendance-counts', false)
            ->assertSee('Review attendance records and display the session QR code during active sessions.')
            ->assertSee('Not Recorded')
            ->assertSee('View QR Code')
            ->assertSee('Available when this session starts.')
            ->assertDontSee('https://example.test/simple-attendance.png')
            ->assertSee('Training Session')
            ->assertDontSee('QR-SIMPLE-20');

        foreach (['RSVP', 'Will Come', 'Not Coming', 'Maybe', 'No Response'] as $removedLabel) {
            $response->assertDontSee($removedLabel);
        }

        $after = DB::table('attendance')->get()->map(fn ($row) => (array) $row)->all();

        $this->assertMatchesRegularExpression('/<button[^>]*class="attendance-qr-button"[^>]*disabled[^>]*aria-disabled="true"[^>]*>/s', $response->getContent());
        $this->assertDoesNotMatchRegularExpression('/<button[^>]*class="attendance-qr-button"[^>]*data-attendance-qr-open[^>]*>/s', $response->getContent());
        $this->assertSame($before, $after);
        $this->assertSame(0, DB::table('trainer_activity_log')->count());
    }

    public function test_active_simple_session_exposes_the_same_qr_control_without_logging_activity(): void
    {
        Carbon::setTestNow('2026-09-05 10:00:00');
        DB::table('course_session')->where('sessionID', '20')->update([
            'sessionDate' => '2026-09-05',
            'startTime' => '09:00:00',
            'endTime' => '11:00:00',
        ]);
        $before = DB::table('trainer_activity_log')->count();

        $response = $this->withSession(['user_role' => 'trainer', 'user_id' => 7])
            ->get(route('trainer.simple.attendance', ['course_id' => 10, 'session_id' => 20]))
            ->assertOk()
            ->assertSee('View QR Code')
            ->assertSee('data-attendance-qr-open', false)
            ->assertDontSee('Present Full Screen')
            ->assertDontSee('Open QR in New Tab')
            ->assertSee('Open QR Display')
            ->assertSee(route('trainer.courses.attendance.qr', [10, 20]), false)
            ->assertSee('https://example.test/simple-attendance.png', false);

        $this->assertMatchesRegularExpression('/<button[^>]*class="attendance-qr-button"[^>]*data-attendance-qr-open[^>]*>/s', $response->getContent());
        $this->assertDoesNotMatchRegularExpression('/<button[^>]*class="attendance-qr-button"[^>]*disabled[^>]*>/s', $response->getContent());
        $this->assertSame('active', $response->viewData('attendanceQr')['state']);
        $this->assertTrue($response->viewData('attendanceQr')['can_view']);
        $this->assertSame($before, DB::table('trainer_activity_log')->count());
    }

    public function test_workspace_remains_fully_accessible_after_teaching_plan_feedback(): void
    {
        $planId = DB::table('teaching_plans')->insertGetId([
            'course_id' => 10,
            'trainer_id' => 7,
            'original_name' => 'simple-teaching-plan.pdf',
            'file_path' => 'teaching-plans/course-10/trainer-7/missing.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 100,
            'submitted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('teaching_plan_comments')->insert([
            'teaching_plan_id' => $planId,
            'reviewer_staff_id' => 'ST7001',
            'comment' => 'Keep the reflection activity at the end.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->withSession(['user_role' => 'trainer', 'user_id' => 7])
            ->get(route('trainer.simple.overview', 10))
            ->assertOk()
            ->assertSee('New teaching plan feedback')
            ->assertSee(route('trainer.simple.materials', 10), false)
            ->assertSee(route('trainer.simple.participants', 10), false)
            ->assertSee(route('trainer.simple.attendance', 10), false);

        $this->withSession(['user_role' => 'trainer', 'user_id' => 7])
            ->get(route('trainer.courses.teaching-plans.index', 10))
            ->assertOk()
            ->assertSee('New Feedback')
            ->assertSee('Keep the reflection activity at the end.');
    }

    public function test_material_page_renders_trainer_owned_materials(): void
    {
        DB::table('learning_material')->insert([
            'material_id' => 30,
            'course_id' => 10,
            'trainer_id' => 7,
            'original_file_name' => 'existing-guide.pdf',
            'file_path' => 'course-materials/10/existing-guide.pdf',
            'material_type' => 'PDF',
            'uploaded_at' => now(),
        ]);

        $this->withSession(['user_role' => 'trainer', 'user_id' => 7])
            ->get(route('trainer.simple.materials', 10))
            ->assertOk()
            ->assertSee('existing-guide.pdf')
            ->assertSee('PDF');
    }

    public function test_trainer_can_upload_view_and_delete_a_simple_material(): void
    {
        Storage::fake('public');
        $session = ['user_role' => 'trainer', 'user_id' => 7];

        $this->withSession($session)
            ->post(route('trainer.simple.materials.store', 10), [
                'material_file' => UploadedFile::fake()->create(
                    'trainer-guide.pdf',
                    100,
                    'application/pdf'
                ),
            ])
            ->assertRedirect(route('trainer.simple.materials', 10))
            ->assertSessionHas('success', 'Material uploaded successfully.');

        $material = DB::table('learning_material')->where('course_id', 10)->first();

        $this->assertNotNull($material);
        $this->assertSame(7, (int) $material->trainer_id);
        $this->assertSame('PDF', $material->material_type);
        Storage::disk('public')->assertExists($material->file_path);
        $this->assertDatabaseHas('trainer_activity_log', [
            'trainer_id' => 7,
            'course_id' => 10,
            'action' => 'material.upload',
            'detail' => 'trainer-guide.pdf',
            'target_id' => $material->material_id,
        ]);

        $this->withSession($session)
            ->get(route('trainer.simple.materials.view', $material->material_id))
            ->assertOk()
            ->assertHeader('Content-Disposition', 'inline; filename="trainer-guide.pdf"');

        $this->withSession($session)
            ->post(route('trainer.simple.materials.delete', $material->material_id))
            ->assertRedirect(route('trainer.simple.materials', 10))
            ->assertSessionHas('success', 'Material has been deleted.');

        $this->assertDatabaseMissing('learning_material', [
            'material_id' => $material->material_id,
        ]);
        Storage::disk('public')->assertMissing($material->file_path);
        $this->assertDatabaseHas('trainer_activity_log', [
            'trainer_id' => 7,
            'course_id' => 10,
            'action' => 'material.delete',
            'detail' => 'trainer-guide.pdf',
            'target_id' => $material->material_id,
        ]);
        $this->assertSame(2, DB::table('trainer_activity_log')->count());
    }

    public function test_overview_lists_the_deduplicated_training_team_and_marks_the_current_trainer(): void
    {
        DB::table('trainer')->insert(['trainerID' => '8', 'trainerName' => 'Trainer Partner']);
        DB::table('course_session')->insert([
            'sessionID' => '21',
            'courseID' => '10',
            'sessionName' => 'Second Session',
            'sessionDate' => now()->addDays(2)->toDateString(),
            'startTime' => '09:00:00',
            'endTime' => '11:00:00',
            'location' => 'Training Hall',
        ]);
        DB::table('session_trainer')->insert([
            ['sessionID' => '20', 'trainerID' => '8'],
            ['sessionID' => '21', 'trainerID' => '7'],
            ['sessionID' => '21', 'trainerID' => '8'],
        ]);

        $response = $this->withSession(['user_role' => 'trainer', 'user_id' => 7])
            ->get(route('trainer.simple.overview', 10))
            ->assertOk()
            ->assertSee('Training Team')
            ->assertSee('Trainer Current')
            ->assertSee('Trainer Partner')
            ->assertSee('You');

        $this->assertSame([7, 8], $response->viewData('trainingTeam')->pluck('trainer_id')->map(fn ($id) => (int) $id)->all());
        $this->assertSame(2, $response->viewData('trainingTeam')->count());
    }

    public function test_shared_header_has_the_required_tab_order_on_every_workspace_page(): void
    {
        foreach ([
            route('trainer.simple.overview', 10),
            route('trainer.simple.materials', 10),
            route('trainer.simple.attendance', 10),
            route('trainer.simple.participants', 10),
        ] as $url) {
            $response = $this->withSession(['user_role' => 'trainer', 'user_id' => 7])
                ->get($url)
                ->assertOk()
                ->assertSeeInOrder(['Overview', 'Materials', 'Attendance', 'Participants'])
                ->assertSee('data-simple-training-header', false)
                ->assertSee('Simple Training')
                ->assertDontSee('Professional Development')
                ->assertDontSee('>Active</span>', false);

            $response->assertDontSee('Teachers');
        }
    }

    public function test_overview_prioritizes_current_then_next_and_collapses_past_sessions(): void
    {
        Carbon::setTestNow('2026-08-12 10:00:00');
        DB::table('course_session')->where('sessionID', '20')->update([
            'sessionName' => 'Nearest Future',
            'sessionDate' => '2026-08-13',
            'startTime' => '09:00:00',
            'endTime' => '11:00:00',
        ]);
        $this->insertSession(21, 'Current Workshop', '2026-08-12', '09:00:00', '11:00:00');
        $this->insertSession(22, 'Older Session', '2026-08-01', '09:00:00', '11:00:00');
        $this->insertSession(23, 'Later Future', '2026-08-20', '09:00:00', '11:00:00');

        $response = $this->withSession(['user_role' => 'trainer', 'user_id' => 7])
            ->get(route('trainer.simple.overview', 10))
            ->assertOk()
            ->assertSee('Current Session')
            ->assertSeeInOrder(['Current Workshop', 'Nearest Future', 'Later Future'])
            ->assertSee('Past Sessions (1)')
            ->assertSee('id="pastSessionList"', false)
            ->assertSee('hidden', false)
            ->assertDontSee('Rows per page');
        $this->assertSame(21, (int) $response->viewData('featuredSession')->session_id);
        $this->assertSame(['20', '23'], $response->viewData('scheduleUpcoming')->pluck('session_id')->all());

        DB::table('course_session')->where('sessionID', '21')->update([
            'sessionDate' => '2026-08-11',
        ]);
        $response = $this->withSession(['user_role' => 'trainer', 'user_id' => 7])
            ->get(route('trainer.simple.overview', 10))
            ->assertOk()
            ->assertSee('Next Session')
            ->assertSeeInOrder(['Next Session', 'Nearest Future']);
        $this->assertSame(20, (int) $response->viewData('featuredSession')->session_id);
        $this->assertSame(['23'], $response->viewData('scheduleUpcoming')->pluck('session_id')->all());
        $this->assertSame(21, (int) $response->viewData('pastSessions')->first()->session_id);

        DB::table('course_session')->whereIn('sessionID', ['20', '23'])->update(['sessionDate' => '2026-08-01']);
        $response = $this->withSession(['user_role' => 'trainer', 'user_id' => 7])
            ->get(route('trainer.simple.overview', 10))
            ->assertOk()
            ->assertSee('No upcoming session')
            ->assertSee('Previous sessions remain available below.');
        $this->assertNull($response->viewData('featuredSession'));
        $this->assertSame(4, $response->viewData('pastSessions')->count());

        Carbon::setTestNow();
    }

    public function test_attendance_uses_the_shared_schedule_status_for_selector_and_selected_session(): void
    {
        Carbon::setTestNow('2026-08-12 10:00:00');
        DB::table('course_session')->where('sessionID', '20')->update([
            'sessionName' => 'Completed Session',
            'sessionDate' => '2026-08-11',
            'startTime' => '09:00:00',
            'endTime' => '11:00:00',
        ]);
        $this->insertSession(21, 'Ongoing Session', '2026-08-12', '09:00:00', '11:00:00');
        $this->insertSession(22, 'Upcoming Session', '2026-08-13', '09:00:00', '11:00:00');
        $before = DB::table('course_session')->orderBy('sessionID')->get()->map(fn ($row) => (array) $row)->all();

        $ongoing = $this->withSession(['user_role' => 'trainer', 'user_id' => 7])
            ->get(route('trainer.simple.attendance', ['course_id' => 10, 'session_id' => 21]))
            ->assertOk()
            ->assertSee('Completed Session · Completed')
            ->assertSee('Ongoing Session · Ongoing')
            ->assertSee('Upcoming Session · Upcoming')
            ->assertSee('<span class="sw-status-badge ongoing">Ongoing</span>', false);
        $this->assertSame('Ongoing', $ongoing->viewData('selectedSession')->display_status);

        $completed = $this->withSession(['user_role' => 'trainer', 'user_id' => 7])
            ->get(route('trainer.simple.attendance', ['course_id' => 10, 'session_id' => 20]))
            ->assertOk()
            ->assertSee('<span class="sw-status-badge completed">Completed</span>', false);
        $this->assertSame('Completed', $completed->viewData('selectedSession')->display_status);

        $upcoming = $this->withSession(['user_role' => 'trainer', 'user_id' => 7])
            ->get(route('trainer.simple.attendance', ['course_id' => 10, 'session_id' => 22]))
            ->assertOk()
            ->assertSee('<span class="sw-status-badge upcoming">Upcoming</span>', false);
        $this->assertSame('Upcoming', $upcoming->viewData('selectedSession')->display_status);

        $after = DB::table('course_session')->orderBy('sessionID')->get()->map(fn ($row) => (array) $row)->all();
        $this->assertSame($before, $after);
        Carbon::setTestNow();
    }

    public function test_material_reports_search_sort_type_and_paginate_without_rendering_upload_form_in_fragment(): void
    {
        foreach (range(1, 12) as $index) {
            DB::table('learning_material')->insert([
                'material_id' => 100 + $index,
                'course_id' => 10,
                'trainer_id' => 7,
                'original_file_name' => sprintf('%02d-guide.%s', $index, $index === 12 ? 'docx' : 'pdf'),
                'file_path' => 'course-materials/10/'.$index,
                'material_type' => 'legacy-value',
                'uploaded_at' => now()->addMinutes($index),
            ]);
        }

        $response = $this->withSession(['user_role' => 'trainer', 'user_id' => 7])
            ->get(route('trainer.simple.materials', ['course_id' => 10, 'rows' => 10]))
            ->assertOk()
            ->assertSee('Showing <strong>1–10</strong> of <strong>12</strong> materials', false)
            ->assertSee('12-guide.docx')
            ->assertDontSee('02-guide.pdf');

        $this->withSession(['user_role' => 'trainer', 'user_id' => 7])
            ->get(route('trainer.simple.materials', ['course_id' => 10, 'search' => '12-guide']))
            ->assertOk()->assertSee('12-guide.docx')->assertDontSee('11-guide.pdf');

        $this->withSession(['user_role' => 'trainer', 'user_id' => 7])
            ->get(route('trainer.simple.materials', ['course_id' => 10, 'sort' => 'oldest']))
            ->assertOk()->assertSeeInOrder(['01-guide.pdf', '02-guide.pdf']);

        $this->withSession(['user_role' => 'trainer', 'user_id' => 7])
            ->get(route('trainer.simple.materials', ['course_id' => 10, 'sort' => 'name-asc']))
            ->assertOk()->assertSeeInOrder(['01-guide.pdf', '02-guide.pdf']);

        $this->withSession(['user_role' => 'trainer', 'user_id' => 7])
            ->get(route('trainer.simple.materials', ['course_id' => 10, 'sort' => 'name-desc', 'type' => 'Document']))
            ->assertOk()->assertSee('12-guide.docx')->assertDontSee('11-guide.pdf');

        $this->withSession(['user_role' => 'trainer', 'user_id' => 7])
            ->get(route('trainer.simple.materials', ['course_id' => 10, 'rows' => 25]), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->assertSee('simpleMaterialResults')->assertDontSee('simpleMaterialUploadForm');
    }

    public function test_attendance_filters_are_read_only_counts_are_session_wide_and_foreign_sessions_are_rejected(): void
    {
        $this->seedAdditionalParticipants(12);
        DB::table('attendance')->insert([
            ['attendance_id' => 51, 'session_id' => '20', 'teacher_id' => 'T101', 'attendance_status' => 'pending', 'timestamp_status' => 1, 'hours_ladap' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['attendance_id' => 52, 'session_id' => '20', 'teacher_id' => 'T102', 'attendance_status' => 'rejected', 'timestamp_status' => 1, 'hours_ladap' => 2, 'created_at' => now(), 'updated_at' => now()],
        ]);
        $before = DB::table('attendance')->count();

        $response = $this->withSession(['user_role' => 'trainer', 'user_id' => 7])
            ->get(route('trainer.simple.attendance', ['course_id' => 10, 'session_id' => 20, 'status' => 'all', 'search' => 'Teacher 02']))
            ->assertOk()
            ->assertSee('Showing <strong>1–1</strong> of <strong>1</strong> participants', false)
            ->assertSee('>1</strong>', false)
            ->assertSee('Not Recorded')
            ->assertDontSee('Mark Attendance')
            ->assertDontSee('Edit Attendance');
        $this->assertSame([
            'total' => 13,
            'recorded' => 3,
            'not_recorded' => 10,
        ], $response->viewData('attendanceCounts'));
        $recorded = $this->withSession(['user_role' => 'trainer', 'user_id' => 7])
            ->get(route('trainer.simple.attendance', ['course_id' => 10, 'session_id' => 20, 'status' => 'recorded']))
            ->assertOk()
            ->assertSee('Recorded')
            ->assertDontSee('Approved')
            ->assertDontSee('Pending')
            ->assertDontSee('Rejected')
            ->assertDontSee('owner status not interpreted');
        $this->assertSame(3, $recorded->viewData('attendancePaginator')->total());

        $this->insertOtherTraining();
        DB::table('qr_session')->insert(['qrID' => 'QR-FOREIGN', 'qrCode' => 'https://example.test/foreign-simple-qr.png', 'sessionID' => '90']);
        $this->withSession(['user_role' => 'trainer', 'user_id' => 7])
            ->get(route('trainer.simple.attendance', ['course_id' => 10, 'session_id' => 90]))
            ->assertOk()
            ->assertDontSee('Foreign Session')
            ->assertDontSee('foreign-simple-qr.png');

        $this->assertSame($before, DB::table('attendance')->count());
    }

    public function test_participant_metrics_filters_letters_and_pagination_use_only_simple_training_data(): void
    {
        DB::table('guru_new')->insert(['gn_id' => 70, 'gn_name' => 'Alice New', 'email' => 'alice.new@example.test', 'phone_number' => '01112345678']);
        DB::table('outsider')->insert(['outsider_id' => 80, 'name' => 'Public Person', 'gender' => null, 'phoneNumber' => '0100000003', 'organization' => 'Community Learning Hub']);
        DB::table('enroll_guru_baru')->insert(['enrollment_id' => 70, 'course_id' => '10', 'gn_id' => 70, 'status' => 'approved', 'applied_at' => '2026-08-01 09:00:00']);
        DB::table('enroll_outsider')->insert(['course_id' => '10', 'outsider_id' => 80, 'status' => 'approved', 'applied_at' => '2026-08-02 09:00:00']);
        $this->seedAdditionalParticipants(10);

        $response = $this->withSession(['user_role' => 'trainer', 'user_id' => 7])
            ->get(route('trainer.simple.participants', 10))
            ->assertOk()
            ->assertSee('Showing <strong>1–10</strong> of <strong>13</strong> participants', false)
            ->assertSee('Participant')
            ->assertSee('Email')
            ->assertSee('Phone Number')
            ->assertSee('Organisation / Department')
            ->assertSee('011-1234 5678')
            ->assertSee('010-000 0003')
            ->assertSee('Community Learning Hub')
            ->assertDontSee('Participant Type')
            ->assertDontSee('<th>Type</th>', false)
            ->assertDontSee('<th>Enrolled</th>', false)
            ->assertDontSee('RSVP')
            ->assertDontSee('Access Status')
            ->assertDontSee('Last Accessed');
        $this->assertSame([
            'total' => 13,
            'male' => 5,
            'female' => 6,
            'unspecified' => 2,
            'teacher' => 11,
            'new_teacher' => 1,
            'public' => 1,
            'staff' => 0,
        ], $response->viewData('participantStats'));
        $this->assertArrayNotHasKey('participantTypeOptions', $response->viewData());

        $this->withSession(['user_role' => 'trainer', 'user_id' => 7])
            ->get(route('trainer.simple.participants', ['course_id' => 10, 'search' => 'alice']))
            ->assertOk()->assertSee('Alice New')->assertDontSee('Teacher One');

        $this->withSession(['user_role' => 'trainer', 'user_id' => 7])
            ->get(route('trainer.simple.participants', ['course_id' => 10, 'search' => '0100000003']))
            ->assertOk()->assertSee('Public Person')->assertDontSee('Alice New');

        $this->withSession(['user_role' => 'trainer', 'user_id' => 7])
            ->get(route('trainer.simple.participants', ['course_id' => 10, 'gender' => 'Unspecified']))
            ->assertOk()->assertSee('Public Person')->assertSee('Alice New');
    }

    public function test_workspace_and_material_access_remain_scoped_to_the_assigned_trainer(): void
    {
        $this->insertOtherTraining(8);
        foreach (['overview', 'materials', 'attendance', 'participants'] as $page) {
            $route = $page === 'overview' ? 'trainer.simple.overview' : 'trainer.simple.'.$page;
            $this->withSession(['user_role' => 'trainer', 'user_id' => 7])
                ->get(route($route, 11))
                ->assertRedirect(route('trainer.courses'));
        }

        Storage::fake('public');
        Storage::disk('public')->put('course-materials/11/private.pdf', 'private');
        DB::table('learning_material')->insert(['material_id' => 91, 'course_id' => 11, 'trainer_id' => 8, 'original_file_name' => 'private.pdf', 'file_path' => 'course-materials/11/private.pdf', 'material_type' => 'PDF', 'uploaded_at' => now()]);
        $this->withSession(['user_role' => 'trainer', 'user_id' => 7])
            ->get(route('trainer.simple.materials.view', 91))
            ->assertForbidden();
        $this->withSession(['user_role' => 'trainer', 'user_id' => 7])
            ->post(route('trainer.simple.materials.delete', 91))
            ->assertRedirect(route('trainer.courses'));
        $this->assertDatabaseHas('learning_material', ['material_id' => 91]);
    }

    public function test_all_three_reports_accept_only_the_exact_server_page_sizes(): void
    {
        $this->seedAdditionalParticipants(12);
        foreach (range(1, 30) as $index) {
            DB::table('learning_material')->insert(['material_id' => 200 + $index, 'course_id' => 10, 'trainer_id' => 7, 'original_file_name' => 'material-'.$index.'.pdf', 'file_path' => 'course-materials/10/'.$index, 'material_type' => 'PDF', 'uploaded_at' => now()]);
        }

        foreach ([10, 25, 50] as $rows) {
            $session = ['user_role' => 'trainer', 'user_id' => 7];
            $materials = $this->withSession($session)->get(route('trainer.simple.materials', ['course_id' => 10, 'rows' => $rows]));
            $participants = $this->withSession($session)->get(route('trainer.simple.participants', ['course_id' => 10, 'rows' => $rows]));
            $attendance = $this->withSession($session)->get(route('trainer.simple.attendance', ['course_id' => 10, 'session_id' => 20, 'rows' => $rows]));
            $this->assertSame($rows, $materials->viewData('materialPaginator')->perPage());
            $this->assertSame($rows, $participants->viewData('participantPaginator')->perPage());
            $this->assertSame($rows, $attendance->viewData('attendancePaginator')->perPage());
        }

        $invalid = $this->withSession(['user_role' => 'trainer', 'user_id' => 7])
            ->get(route('trainer.simple.participants', ['course_id' => 10, 'rows' => 100]));
        $this->assertSame(10, $invalid->viewData('participantPaginator')->perPage());
    }

    public function test_report_get_requests_do_not_modify_training_records(): void
    {
        $before = [
            'enrollments' => $this->canonicalEnrollmentRows(),
            'attendance' => DB::table('attendance')->get()->map(fn ($row) => (array) $row)->all(),
            'materials' => DB::table('learning_material')->get()->map(fn ($row) => (array) $row)->all(),
        ];
        $session = ['user_role' => 'trainer', 'user_id' => 7];
        $this->withSession($session)->get(route('trainer.simple.materials', ['course_id' => 10, 'search' => 'guide']))->assertOk();
        $this->withSession($session)->get(route('trainer.simple.attendance', ['course_id' => 10, 'status' => 'recorded']))->assertOk();
        $this->withSession($session)->get(route('trainer.simple.participants', ['course_id' => 10, 'gender' => 'Female']))->assertOk();
        $this->assertSame($before['enrollments'], $this->canonicalEnrollmentRows());
        $this->assertSame($before['attendance'], DB::table('attendance')->get()->map(fn ($row) => (array) $row)->all());
        $this->assertSame($before['materials'], DB::table('learning_material')->get()->map(fn ($row) => (array) $row)->all());
    }

    public function test_material_validation_contract_is_unchanged(): void
    {
        Storage::fake('public');
        $this->withSession(['user_role' => 'trainer', 'user_id' => 7])
            ->from(route('trainer.simple.materials', 10))
            ->post(route('trainer.simple.materials.store', 10), [
                'material_file' => UploadedFile::fake()->create('unsafe.exe', 10, 'application/octet-stream'),
            ])
            ->assertRedirect(route('trainer.simple.materials', 10))
            ->assertSessionHasErrors('material_file');
        $this->assertSame(0, DB::table('learning_material')->count());
    }

    private function insertSession(int $id, string $title, string $date, string $start, string $end): void
    {
        DB::table('course_session')->insert(['sessionID' => (string) $id, 'courseID' => '10', 'sessionName' => $title, 'sessionDate' => $date, 'startTime' => $start, 'endTime' => $end, 'location' => 'Hall']);
        DB::table('session_trainer')->insert(['sessionID' => (string) $id, 'trainerID' => '7']);
    }

    private function seedAdditionalParticipants(int $count): void
    {
        foreach (range(1, $count) as $index) {
            $teacherId = 100 + $index;
            DB::table('teacher')->insert(['teacherID' => 'T'.$teacherId, 'teacherName' => sprintf('Teacher %02d', $index + 1), 'email' => 'teacher'.$teacherId.'@example.test', 'gender' => $index % 2 ? 'Male' : 'Female']);
            DB::table('enroll_teacher')->insert(['teacherID' => 'T'.$teacherId, 'course_id' => '10', 'status' => 'approved', 'applied_at' => now()->subDays($index)]);
        }
    }

    private function insertOtherTraining(int $trainerId = 7): void
    {
        DB::table('course')->insertOrIgnore(['courseID' => '11', 'courseName' => 'Other Training', 'courseCategory' => 'Other', 'description' => null, 'targetAudience' => 'Teachers', 'status' => 'Active', 'courseType' => 'simple']);
        DB::table('course_session')->insertOrIgnore(['sessionID' => '90', 'courseID' => '11', 'sessionName' => 'Foreign Session', 'sessionDate' => now()->addDay()->toDateString(), 'startTime' => '09:00:00', 'endTime' => '10:00:00', 'location' => 'Elsewhere']);
        DB::table('session_trainer')->insertOrIgnore(['sessionID' => '90', 'trainerID' => (string) $trainerId]);
    }

    private function canonicalEnrollmentRows(): array
    {
        return collect(['enroll_teacher', 'enroll_guru_baru', 'enroll_outsider'])
            ->mapWithKeys(fn (string $table): array => [
                $table => DB::table($table)->get()->map(fn ($row) => (array) $row)->all(),
            ])->all();
    }

    private function createTables(): void
    {
        Schema::create('trainer', function (Blueprint $table): void {
            $table->string('trainerID', 20)->primary();
            $table->string('trainerName');
        });

        Schema::create('staff_edu', function (Blueprint $table): void {
            $table->string('staffID', 20)->primary();
            $table->string('staffName');
            $table->string('email')->nullable();
            $table->string('gender')->nullable();
            $table->string('phoneNumber')->nullable();
            $table->string('department')->nullable();
        });

        Schema::create('course', function (Blueprint $table): void {
            $table->string('courseID', 20)->primary();
            $table->string('courseName');
            $table->string('courseCategory')->nullable();
            $table->text('description')->nullable();
            $table->string('targetAudience')->nullable();
            $table->string('status')->nullable();
            $table->string('courseType')->nullable();
        });

        Schema::create('course_session', function (Blueprint $table): void {
            $table->string('sessionID', 20)->primary();
            $table->string('courseID', 20);
            $table->string('sessionName')->nullable();
            $table->date('sessionDate')->nullable();
            $table->time('startTime')->nullable();
            $table->time('endTime')->nullable();
            $table->string('location')->nullable();
        });

        Schema::create('session_trainer', function (Blueprint $table): void {
            $table->string('sessionID', 20);
            $table->string('trainerID', 20);
        });

        Schema::create('enroll_teacher', function (Blueprint $table): void {
            $table->string('teacherID', 20);
            $table->string('course_id', 20);
            $table->string('status')->default('approved');
            $table->timestamp('applied_at')->nullable();
        });
        Schema::create('enroll_guru_baru', function (Blueprint $table): void {
            $table->integer('enrollment_id')->primary();
            $table->string('gn_id', 20);
            $table->string('course_id', 20);
            $table->string('status')->default('approved');
            $table->timestamp('applied_at')->nullable();
        });
        Schema::create('enroll_outsider', function (Blueprint $table): void {
            $table->integer('outsider_id');
            $table->string('course_id', 20);
            $table->string('status')->default('approved');
            $table->timestamp('applied_at')->nullable();
        });

        Schema::create('teacher', function (Blueprint $table): void {
            $table->string('teacherID', 20)->primary();
            $table->string('teacherName');
            $table->string('email')->nullable();
            $table->string('gender')->nullable();
            $table->string('phoneNumber')->nullable();
        });

        Schema::create('guru_new', function (Blueprint $table): void {
            $table->string('gn_id', 20)->primary();
            $table->string('gn_name');
            $table->string('email')->nullable();
            $table->string('gender', 10)->nullable();
            $table->string('phone_number')->nullable();
        });

        Schema::create('outsider', function (Blueprint $table): void {
            $table->integer('outsider_id')->primary();
            $table->string('name');
            $table->string('gender')->nullable();
            $table->string('phoneNumber')->nullable();
            $table->string('contact_no')->nullable();
            $table->string('organization')->nullable();
        });

        Schema::create('learning_material', function (Blueprint $table): void {
            $table->integer('material_id')->primary();
            $table->string('course_id', 20);
            $table->string('trainer_id', 20);
            $table->string('session_id', 20)->nullable();
            $table->string('original_file_name');
            $table->string('file_path');
            $table->string('material_type');
            $table->boolean('metadata_cleared')->default(false);
            $table->text('extracted_metadata')->nullable();
            $table->string('ai_generated_title')->nullable();
            $table->text('ai_synopsis')->nullable();
            $table->timestamp('uploaded_at')->nullable();
        });

        Schema::create('attendance', function (Blueprint $table): void {
            $table->integer('attendance_id')->primary();
            $table->string('attendance_status')->nullable();
            $table->decimal('timestamp_status', 5, 2)->default(0);
            $table->decimal('hours_ladap', 5, 2)->default(0);
            $table->string('teacher_id', 20);
            $table->string('session_id', 20);
            $table->timestamps();
        });
        Schema::create('attendance_guru_baru', function (Blueprint $table): void {
            $table->integer('attendGuruBaru_id')->primary();
            $table->string('attendance_status')->nullable();
            $table->decimal('timestamp_status', 5, 2)->default(0);
            $table->decimal('hours_ladap', 5, 2)->default(0);
            $table->string('gn_id', 20);
            $table->string('session_id', 20);
            $table->timestamps();
        });
        Schema::create('attendance_outsider', function (Blueprint $table): void {
            $table->integer('attendOutsider_id')->primary();
            $table->string('attendance_status')->nullable();
            $table->decimal('timestamp_status', 5, 2)->default(0);
            $table->integer('outsider_id');
            $table->string('session_id', 20);
            $table->timestamps();
        });

        Schema::create('teaching_plans', function (Blueprint $table): void {
            $table->id();
            $table->string('course_id', 20);
            $table->string('trainer_id', 20);
            $table->string('original_name');
            $table->string('file_path');
            $table->string('mime_type');
            $table->unsignedBigInteger('file_size');
            $table->timestamp('submitted_at');
            $table->timestamps();
            $table->unique(['course_id', 'trainer_id']);
        });

        Schema::create('teaching_plan_comments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('teaching_plan_id');
            $table->string('reviewer_staff_id', 20);
            $table->text('comment');
            $table->timestamp('seen_by_trainer_at')->nullable();
            $table->timestamps();
        });

        Schema::create('trainer_activity_log', function (Blueprint $table): void {
            $table->bigIncrements('log_id');
            $table->string('trainer_id', 20)->index('idx_trainer');
            $table->string('action', 60)->index('idx_action');
            $table->string('detail', 255)->nullable();
            $table->string('course_id', 20)->nullable();
            $table->string('target_type', 40)->nullable();
            $table->bigInteger('target_id')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent()->index('idx_created');
        });

        Schema::create('qr_session', function (Blueprint $table): void {
            $table->string('qrID', 20)->primary();
            $table->text('qrCode');
            $table->string('attendanceLink', 255)->nullable();
            $table->dateTime('expiryTime')->nullable();
            $table->string('sessionID', 20)->index('qr_session_session_id_index');
        });
    }

    private function seedSimpleTraining(): void
    {
        DB::table('staff_edu')->insert([
            'staffID' => 'ST7001',
            'staffName' => 'Reviewer Staff',
        ]);

        DB::table('trainer')->insert([
            'trainerID' => '7',
            'trainerName' => 'Trainer Current',
        ]);

        DB::table('course')->insert([
            'courseID' => '10',
            'courseName' => 'Simple Training Course',
            'courseCategory' => 'Professional Development',
            'description' => 'A focused Simple Training course.',
            'targetAudience' => 'Teachers',
            'status' => 'Active',
            'courseType' => 'simple',
        ]);

        DB::table('course_session')->insert([
            'sessionID' => '20',
            'courseID' => '10',
            'sessionName' => 'Training Session',
            'sessionDate' => now()->addDay()->toDateString(),
            'startTime' => '09:00:00',
            'endTime' => '11:00:00',
            'location' => 'Training Hall',
        ]);

        DB::table('session_trainer')->insert([
            'sessionID' => '20',
            'trainerID' => '7',
        ]);

        DB::table('teacher')->insert([
            'teacherID' => '40',
            'teacherName' => 'Teacher One',
            'email' => 'teacher@example.test',
            'gender' => 'Female',
            'phoneNumber' => '0123456789',
        ]);

        DB::table('enroll_teacher')->insert([
            'teacherID' => '40',
            'course_id' => '10',
            'status' => 'approved',
            'applied_at' => now(),
        ]);

        DB::table('attendance')->insert([
            'attendance_id' => 50,
            'session_id' => '20',
            'teacher_id' => '40',
            'attendance_status' => 'approved',
            'timestamp_status' => 1,
            'hours_ladap' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('qr_session')->insert([
            'qrID' => 'QR-SIMPLE-20',
            'qrCode' => 'https://example.test/simple-attendance.png',
            'attendanceLink' => 'http://localhost/trainhub/attendance.php?sessionID=20',
            'expiryTime' => null,
            'sessionID' => '20',
        ]);
    }
}
