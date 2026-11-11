<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminProposalControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());

        Schema::dropIfExists('course_proposal');
        Schema::create('course_proposal', function (Blueprint $table): void {
            $table->increments('proposal_id');
            $table->unsignedInteger('trainer_id');
            $table->string('proposed_title');
            $table->string('status');
            $table->text('admin_comment')->nullable();
            $table->string('reviewed_by')->nullable();
            $table->dateTime('reviewed_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });

        DB::table('course_proposal')->insert([
            'proposal_id' => 41,
            'trainer_id' => 9001,
            'proposed_title' => 'Inclusive Classroom Practice',
            'status' => 'Pending',
        ]);
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('course_proposal');

        parent::tearDown();
    }

    public function test_administrator_approval_rejects_a_missing_proposal_before_review(): void
    {
        DB::table('course_proposal')->where('proposal_id', 41)->delete();

        $response = $this->withSession($this->adminSession())
            ->post('/admin/proposals/41/approve', [
                'admin_comment' => 'Ready to schedule.',
            ]);

        $response
            ->assertRedirect(route('admin.proposals'))
            ->assertSessionHas('error', 'Proposal not found.');
    }

    public function test_administrator_rejection_preserves_validation_and_missing_proposal_gate(): void
    {
        $invalid = $this->withSession($this->adminSession())
            ->from('/admin/proposals')
            ->post('/admin/proposals/41/reject', ['admin_comment' => '']);

        $invalid
            ->assertRedirect('/admin/proposals')
            ->assertSessionHasErrors([
                'admin_comment' => 'Please give a reason when rejecting a proposal.',
            ]);

        DB::table('course_proposal')->where('proposal_id', 41)->delete();

        $response = $this->withSession($this->adminSession())
            ->post('/admin/proposals/41/reject', [
                'admin_comment' => 'The proposed scope needs revision.',
            ]);

        $response
            ->assertRedirect(route('admin.proposals'))
            ->assertSessionHas('error', 'Proposal not found.');
    }

    public function test_proposal_decisions_update_review_fields_without_database_routines(): void
    {
        $this->withSession($this->adminSession())
            ->post('/admin/proposals/41/approve', [
                'admin_comment' => 'Ready to schedule.',
            ])
            ->assertRedirect(route('admin.proposals'))
            ->assertSessionHas('success', 'Proposal approved: Inclusive Classroom Practice');

        $approved = DB::table('course_proposal')->where('proposal_id', 41)->first();
        $this->assertSame('Approved', $approved->status);
        $this->assertSame('Ready to schedule.', $approved->admin_comment);
        $this->assertSame('7001', $approved->reviewed_by);
        $this->assertNotNull($approved->reviewed_at);

        DB::table('course_proposal')->where('proposal_id', 41)->update([
            'status' => 'Pending',
            'admin_comment' => null,
            'reviewed_by' => null,
            'reviewed_at' => null,
        ]);

        $this->withSession($this->adminSession())
            ->post('/admin/proposals/41/reject', [
                'admin_comment' => 'The proposed scope needs revision.',
            ])
            ->assertRedirect(route('admin.proposals'))
            ->assertSessionHas('success', 'Proposal rejected: Inclusive Classroom Practice');

        $rejected = DB::table('course_proposal')->where('proposal_id', 41)->first();
        $this->assertSame('Rejected', $rejected->status);
        $this->assertSame('The proposed scope needs revision.', $rejected->admin_comment);
        $this->assertSame('7001', $rejected->reviewed_by);
        $this->assertNotNull($rejected->reviewed_at);
    }

    private function adminSession(): array
    {
        return [
            'user_id' => 7001,
            'user_role' => 'admin',
            'user_name' => 'Administrator Test',
        ];
    }
}
