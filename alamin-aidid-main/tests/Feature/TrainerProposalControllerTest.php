<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TrainerProposalControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());

        Schema::dropIfExists('trainer_activity_log');
        Schema::dropIfExists('course_proposal');

        Schema::create('course_proposal', function (Blueprint $table): void {
            $table->increments('proposal_id');
            $table->unsignedInteger('trainer_id');
            $table->string('proposed_title');
            $table->text('target_audience');
            $table->text('objective');
            $table->text('description')->nullable();
            $table->string('training_mode');
            $table->string('delivery_mode');
            $table->unsignedInteger('estimated_capacity')->nullable();
            $table->date('proposed_start_date')->nullable();
            $table->date('proposed_end_date')->nullable();
            $table->string('document_path')->nullable();
            $table->string('document_name')->nullable();
            $table->string('status');
            $table->text('admin_comment')->nullable();
            $table->unsignedInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('trainer_activity_log', function (Blueprint $table): void {
            $table->bigIncrements('log_id');
            $table->bigInteger('trainer_id')->index('idx_trainer');
            $table->string('action', 60)->index('idx_action');
            $table->string('detail', 255)->nullable();
            $table->bigInteger('course_id')->nullable();
            $table->string('target_type', 40)->nullable();
            $table->bigInteger('target_id')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent()->index('idx_created');
        });

        Storage::fake('public');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        Schema::dropIfExists('trainer_activity_log');
        Schema::dropIfExists('course_proposal');

        parent::tearDown();
    }

    public function test_trainer_can_submit_a_proposal_with_the_existing_contract(): void
    {
        $response = $this->withSession($this->trainerSession())
            ->post('/trainer/proposals', $this->validProposal());

        $response
            ->assertRedirect(route('trainer.proposals'))
            ->assertSessionHas('success');

        $proposal = DB::table('course_proposal')->first();

        $this->assertNotNull($proposal);
        $this->assertSame(9001, $proposal->trainer_id);
        $this->assertSame('Pending', $proposal->status);
        $this->assertSame(
            ['Teacher', 'Public'],
            json_decode($proposal->target_audience, true)
        );
        $this->assertSame(
            1,
            DB::table('trainer_activity_log')->where('action', 'proposal.submit')->count()
        );
        $this->assertDatabaseHas('trainer_activity_log', [
            'trainer_id' => 9001,
            'action' => 'proposal.submit',
            'detail' => 'Inclusive Classroom Practice',
            'target_id' => $proposal->proposal_id,
        ]);
    }

    public function test_proposal_attachment_upload_keeps_the_existing_public_disk_contract(): void
    {
        $document = UploadedFile::fake()->create('training-outline.pdf', 120, 'application/pdf');

        $response = $this->withSession($this->trainerSession())
            ->post('/trainer/proposals', array_merge($this->validProposal(), [
                'proposal_document' => $document,
            ]));

        $response->assertRedirect(route('trainer.proposals'));
        $proposal = DB::table('course_proposal')->first();

        $this->assertNotNull($proposal->document_path);
        $this->assertSame('training-outline.pdf', $proposal->document_name);
        $this->assertStringStartsWith('proposal-documents/9001/', $proposal->document_path);
        Storage::disk('public')->assertExists($proposal->document_path);
    }

    public function test_proposal_validation_messages_remain_unchanged(): void
    {
        $response = $this->withSession($this->trainerSession())
            ->from('/trainer/proposals')
            ->post('/trainer/proposals', array_merge($this->validProposal(), [
                'target_audience' => [],
                'proposed_start_date' => '2026-08-10',
                'proposed_end_date' => '2026-08-09',
            ]));

        $response
            ->assertRedirect('/trainer/proposals')
            ->assertSessionHasErrors([
                'target_audience' => 'Please select at least one target audience.',
                'proposed_end_date' => 'The end date must be the same as or after the start date.',
            ])
            ->assertSessionHasInput('proposed_title', 'Inclusive Classroom Practice');
        $this->assertSame(0, DB::table('course_proposal')->count());
    }

    public function test_summary_counts_and_list_are_scoped_to_the_current_trainer(): void
    {
        $this->insertProposal(['proposed_title' => 'Pending One', 'status' => 'Pending']);
        $this->insertProposal(['proposed_title' => 'Pending Two', 'status' => 'Pending']);
        $this->insertProposal(['proposed_title' => 'Approved One', 'status' => 'Approved']);
        $this->insertProposal(['proposed_title' => 'Rejected One', 'status' => 'Rejected']);
        $this->insertProposal([
            'trainer_id' => 7007,
            'proposed_title' => 'Another Trainer Proposal',
            'status' => 'Approved',
        ]);

        $response = $this->withSession($this->trainerSession())->get('/trainer/proposals');

        $response
            ->assertOk()
            ->assertViewHas('stats', [
                'total' => 4,
                'pending' => 2,
                'approved' => 1,
                'rejected' => 1,
            ])
            ->assertViewHas('proposalPaginator', fn (LengthAwarePaginator $paginator) => $paginator->total() === 4)
            ->assertSee('Pending One')
            ->assertDontSee('Another Trainer Proposal');
    }

    public function test_status_and_title_filters_are_server_side_and_trainer_scoped(): void
    {
        $this->insertProposal([
            'proposed_title' => 'Classroom Psychology Foundations',
            'status' => 'Approved',
        ]);
        $this->insertProposal([
            'proposed_title' => 'Classroom Leadership',
            'status' => 'Approved',
        ]);
        $this->insertProposal([
            'proposed_title' => 'Classroom Psychology Pending',
            'status' => 'Pending',
        ]);
        $this->insertProposal([
            'trainer_id' => 7007,
            'proposed_title' => 'Classroom Psychology Other Trainer',
            'status' => 'Approved',
        ]);

        $response = $this->withSession($this->trainerSession())->get('/trainer/proposals?'.http_build_query([
            'status' => 'approved',
            'search' => 'psychology',
        ]));

        $response
            ->assertOk()
            ->assertViewHas('proposalPaginator', function (LengthAwarePaginator $paginator): bool {
                return $paginator->total() === 1
                    && $paginator->first()->proposed_title === 'Classroom Psychology Foundations';
            })
            ->assertSee('Classroom Psychology Foundations')
            ->assertDontSee('Classroom Psychology Pending')
            ->assertDontSee('Classroom Psychology Other Trainer');
    }

    public function test_submitted_period_and_custom_dates_use_created_at_with_inclusive_boundaries(): void
    {
        Carbon::setTestNow('2026-08-12 12:00:00');
        $this->insertProposal(['proposed_title' => 'Recent Proposal', 'created_at' => '2026-08-12 09:00:00']);
        $this->insertProposal(['proposed_title' => 'From Boundary', 'created_at' => '2026-07-01 00:00:00']);
        $this->insertProposal(['proposed_title' => 'To Boundary', 'created_at' => '2026-07-31 23:59:59']);
        $this->insertProposal(['proposed_title' => 'Outside Boundary', 'created_at' => '2026-06-30 23:59:59']);

        $recent = $this->withSession($this->trainerSession())
            ->get('/trainer/proposals?submitted_period=30');
        $recent
            ->assertViewHas('proposalPaginator', fn (LengthAwarePaginator $paginator) => $paginator->total() === 2)
            ->assertSee('Recent Proposal')
            ->assertSee('To Boundary')
            ->assertDontSee('From Boundary');

        $custom = $this->withSession($this->trainerSession())
            ->get('/trainer/proposals?submitted_period=custom&submitted_from=2026-07-01&submitted_to=2026-07-31');
        $custom
            ->assertViewHas('proposalPaginator', fn (LengthAwarePaginator $paginator) => $paginator->total() === 2)
            ->assertSee('From Boundary')
            ->assertSee('To Boundary')
            ->assertDontSee('Outside Boundary');
    }

    public function test_invalid_or_reversed_custom_dates_normalize_to_all_time(): void
    {
        $this->insertProposal(['proposed_title' => 'First Proposal', 'created_at' => '2026-01-01 10:00:00']);
        $this->insertProposal(['proposed_title' => 'Second Proposal', 'created_at' => '2026-08-01 10:00:00']);

        foreach ([
            '/trainer/proposals?submitted_period=custom&submitted_from=invalid&submitted_to=2026-08-01',
            '/trainer/proposals?submitted_period=custom&submitted_from=2026-08-02&submitted_to=2026-08-01',
        ] as $url) {
            $response = $this->withSession($this->trainerSession())->get($url);
            $response
                ->assertViewHas('proposalFilters', fn (array $filters) => $filters['submitted_period'] === 'all'
                    && $filters['submitted_from'] === ''
                    && $filters['submitted_to'] === '')
                ->assertViewHas('proposalPaginator', fn (LengthAwarePaginator $paginator) => $paginator->total() === 2);
        }
    }

    public function test_proposals_use_exact_server_side_row_options_and_page_counts(): void
    {
        foreach (range(1, 56) as $index) {
            $this->insertProposal([
                'proposed_title' => sprintf('Proposal %02d', $index),
                'status' => $index <= 30 ? 'Approved' : 'Pending',
                'created_at' => Carbon::parse('2026-01-01')->addDays($index),
            ]);
        }

        $default = $this->withSession($this->trainerSession())->get('/trainer/proposals');
        $default->assertViewHas('proposalPaginator', fn (LengthAwarePaginator $paginator) => $paginator->perPage() === 10
            && $paginator->count() === 10
            && $paginator->lastPage() === 6);

        $rows25 = $this->withSession($this->trainerSession())->get('/trainer/proposals?rows=25&page=2');
        $rows25->assertViewHas('proposalPaginator', fn (LengthAwarePaginator $paginator) => $paginator->perPage() === 25
            && $paginator->currentPage() === 2
            && $paginator->count() === 25
            && $paginator->lastPage() === 3);

        $rows50 = $this->withSession($this->trainerSession())->get('/trainer/proposals?rows=50&page=2');
        $rows50->assertViewHas('proposalPaginator', fn (LengthAwarePaginator $paginator) => $paginator->perPage() === 50
            && $paginator->currentPage() === 2
            && $paginator->count() === 6
            && $paginator->lastPage() === 2);

        $filtered = $this->withSession($this->trainerSession())->get('/trainer/proposals?status=approved&rows=10&page=3');
        $filtered->assertViewHas('proposalPaginator', fn (LengthAwarePaginator $paginator) => $paginator->total() === 30
            && $paginator->currentPage() === 3
            && $paginator->count() === 10
            && $paginator->lastPage() === 3);
    }

    public function test_only_an_owned_selected_proposal_receives_expanded_detail_and_admin_review(): void
    {
        $selectedId = $this->insertProposal([
            'proposed_title' => 'Selected Approved Proposal',
            'objective' => 'Selected private objective.',
            'description' => 'Selected justification.',
            'status' => 'Approved',
            'admin_comment' => 'Approved for the next training cycle.',
            'reviewed_by' => 11,
            'reviewed_at' => '2026-08-10 14:30:00',
        ]);
        $this->insertProposal([
            'proposed_title' => 'Collapsed Proposal',
            'objective' => 'Collapsed private objective.',
        ]);
        $otherId = $this->insertProposal([
            'trainer_id' => 7007,
            'proposed_title' => 'Other Trainer Secret',
            'objective' => 'Other trainer private objective.',
            'admin_comment' => 'Private review.',
        ]);

        $selected = $this->withSession($this->trainerSession())
            ->get('/trainer/proposals?proposal_id='.$selectedId);
        $selected
            ->assertOk()
            ->assertViewHas('selectedProposal', fn ($proposal) => (int) $proposal->proposal_id === $selectedId)
            ->assertSee('Selected private objective.')
            ->assertSee('Approved for the next training cycle.')
            ->assertDontSee('Collapsed private objective.')
            ->assertDontSee('Other trainer private objective.');

        $unauthorized = $this->withSession($this->trainerSession())
            ->get('/trainer/proposals?proposal_id='.$otherId);
        $unauthorized
            ->assertOk()
            ->assertViewHas('selectedProposal', null)
            ->assertDontSee('Other Trainer Secret')
            ->assertDontSee('Private review.');
    }

    public function test_async_report_response_contains_only_the_replaceable_results_region(): void
    {
        $this->insertProposal(['proposed_title' => 'Async Proposal']);

        $response = $this->withSession($this->trainerSession())
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->get('/trainer/proposals?search=Async');

        $response
            ->assertOk()
            ->assertViewIs('trainer.training.proposal.partials.results')
            ->assertSee('id="proposalResultsRegion"', false)
            ->assertDontSee('id="proposalSubmissionForm"', false)
            ->assertDontSee('id="proposalSearch"', false);
    }

    public function test_report_get_interactions_do_not_modify_proposals(): void
    {
        $proposalId = $this->insertProposal([
            'proposed_title' => 'Read Only Proposal',
            'updated_at' => '2026-08-01 08:00:00',
        ]);
        $before = (array) DB::table('course_proposal')->where('proposal_id', $proposalId)->first();

        $this->withSession($this->trainerSession())->get('/trainer/proposals?'.http_build_query([
            'status' => 'pending',
            'search' => 'Read Only',
            'submitted_period' => 'custom',
            'submitted_from' => '2026-01-01',
            'submitted_to' => '2026-12-31',
            'rows' => 25,
            'proposal_id' => $proposalId,
        ]))->assertOk();

        $after = (array) DB::table('course_proposal')->where('proposal_id', $proposalId)->first();
        $this->assertSame($before, $after);
    }

    public function test_trainer_can_withdraw_an_owned_pending_proposal_and_its_document(): void
    {
        Storage::disk('public')->put('proposal-documents/9001/proposal.pdf', 'proposal');

        $proposalId = DB::table('course_proposal')->insertGetId(array_merge(
            $this->validProposal(),
            [
                'trainer_id' => 9001,
                'target_audience' => json_encode(['Teacher']),
                'document_path' => 'proposal-documents/9001/proposal.pdf',
                'document_name' => 'proposal.pdf',
                'status' => 'Pending',
                'admin_comment' => null,
                'reviewed_by' => null,
                'reviewed_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ));

        $response = $this->withSession($this->trainerSession())
            ->delete('/trainer/proposals/'.$proposalId);

        $response
            ->assertRedirect(route('trainer.proposals'))
            ->assertSessionHas('success');
        $this->assertSame(0, DB::table('course_proposal')->count());
        Storage::disk('public')->assertMissing('proposal-documents/9001/proposal.pdf');
        $this->assertSame(
            1,
            DB::table('trainer_activity_log')->where('action', 'proposal.delete')->count()
        );
        $this->assertDatabaseHas('trainer_activity_log', [
            'trainer_id' => 9001,
            'action' => 'proposal.delete',
            'detail' => 'Inclusive Classroom Practice',
            'target_id' => $proposalId,
        ]);
    }

    public function test_trainer_cannot_withdraw_another_trainers_or_a_reviewed_proposal(): void
    {
        $otherId = $this->insertProposal(['trainer_id' => 7007, 'status' => 'Pending']);
        $reviewedId = $this->insertProposal(['status' => 'Approved']);

        $this->withSession($this->trainerSession())
            ->delete('/trainer/proposals/'.$otherId)
            ->assertRedirect(route('trainer.proposals'))
            ->assertSessionHas('error', 'Proposal not found.');
        $this->withSession($this->trainerSession())
            ->delete('/trainer/proposals/'.$reviewedId)
            ->assertRedirect(route('trainer.proposals'))
            ->assertSessionHas('error', 'Only pending proposals can be withdrawn. This one has already been reviewed.');

        $this->assertSame(2, DB::table('course_proposal')->count());
    }

    private function insertProposal(array $overrides = []): int
    {
        return DB::table('course_proposal')->insertGetId(array_merge([
            'trainer_id' => 9001,
            'proposed_title' => 'Default Proposal',
            'target_audience' => json_encode(['Teacher']),
            'objective' => 'Default objective.',
            'description' => 'Default description.',
            'training_mode' => 'LMS',
            'delivery_mode' => 'Hybrid',
            'estimated_capacity' => 40,
            'proposed_start_date' => '2026-09-01',
            'proposed_end_date' => '2026-09-02',
            'document_path' => null,
            'document_name' => null,
            'status' => 'Pending',
            'admin_comment' => null,
            'reviewed_by' => null,
            'reviewed_at' => null,
            'created_at' => '2026-08-01 10:00:00',
            'updated_at' => '2026-08-01 10:00:00',
        ], $overrides));
    }

    private function validProposal(): array
    {
        return [
            'proposed_title' => 'Inclusive Classroom Practice',
            'training_mode' => 'LMS',
            'delivery_mode' => 'Hybrid',
            'target_audience' => ['Teacher', 'Public'],
            'estimated_capacity' => 40,
            'proposed_start_date' => '2026-08-10',
            'proposed_end_date' => '2026-08-11',
            'objective' => 'Improve inclusive classroom practice.',
            'description' => 'A focused trainer-led workshop.',
        ];
    }

    private function trainerSession(): array
    {
        return [
            'user_id' => 9001,
            'user_role' => 'trainer',
            'user_name' => 'Trainer Test',
        ];
    }
}
