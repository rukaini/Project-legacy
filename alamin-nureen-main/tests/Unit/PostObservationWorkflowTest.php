<?php

namespace Tests\Unit;

use App\Support\PostObservationWorkflow;
use PHPUnit\Framework\TestCase;

class PostObservationWorkflowTest extends TestCase
{
    public function test_optional_post_can_be_offered_after_latest_external_repeat(): void
    {
        $this->assertSame(1, PostObservationWorkflow::nextAttempt('REPEAT', []));
        $this->assertNull(PostObservationWorkflow::nextAttempt('PASS', []));
        $this->assertNull(PostObservationWorkflow::nextAttempt(null, []));
        $this->assertTrue(PostObservationWorkflow::canOfferOptionalPost('REPEAT', []));
        $this->assertFalse(PostObservationWorkflow::canOfferOptionalPost('PASS', []));
    }

    public function test_post_repeat_unblocks_next_attempt_and_pending_or_pass_blocks_it(): void
    {
        $this->assertSame(2, PostObservationWorkflow::nextAttempt('REPEAT', [['attempt'=>1, 'result'=>'REPEAT']]));
        $this->assertSame(4, PostObservationWorkflow::nextAttempt('REPEAT', [
            ['attempt'=>1, 'result'=>'REPEAT'], ['attempt'=>2, 'result'=>'REPEAT'], ['attempt'=>3, 'result'=>'REPEAT'],
        ]));
        $this->assertNull(PostObservationWorkflow::nextAttempt('REPEAT', [['attempt'=>1, 'result'=>null]]));
        $this->assertNull(PostObservationWorkflow::nextAttempt('REPEAT', [['attempt'=>1, 'result'=>'PASS']]));
        // Once External passes, old Post history is retained but no new Post is required.
        $this->assertNull(PostObservationWorkflow::nextAttempt('PASS', [['attempt'=>1, 'result'=>'REPEAT']]));
    }

    public function test_repeated_external_attempts_do_not_force_post(): void
    {
        // GN0007 has EXTERNAL attempt 1 REPEAT then attempt 2 PASS.
        // The latest submitted External decision controls any *new* Post.
        $submittedExternal = [
            ['attempt'=>1, 'result'=>'REPEAT'],
            ['attempt'=>2, 'result'=>'PASS'],
        ];
        $latest = end($submittedExternal);
        $this->assertNull(PostObservationWorkflow::nextAttempt($latest['result'], []));
        $this->assertSame(1, PostObservationWorkflow::nextAttempt('REPEAT', []));
    }

    public function test_submitted_post_with_null_result_blocks_the_next_attempt(): void
    {
        // GN0005 has submitted POST 1 with a score but NULL final result.
        $this->assertNull(PostObservationWorkflow::nextAttempt('REPEAT', [
            ['attempt'=>1, 'result'=>null, 'status'=>'Submitted'],
        ]));
    }

    public function test_previous_post_history_is_kept_even_after_external_pass(): void
    {
        $history = [['attempt'=>1, 'result'=>'REPEAT']];
        $this->assertCount(1, $history);
        $this->assertNull(PostObservationWorkflow::nextAttempt('PASS', $history));
    }
}
