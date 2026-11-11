<?php

namespace Tests\Unit;

use App\Support\ParticipantTypeLabel;
use PHPUnit\Framework\TestCase;

class ParticipantTypeLabelTest extends TestCase
{
    public function test_outsider_is_mapped_for_display_without_changing_the_internal_value(): void
    {
        $internalValue = 'Outsider';

        $this->assertSame('Public', ParticipantTypeLabel::display($internalValue));
        $this->assertSame('Public participants', ParticipantTypeLabel::audience('Outsider participants'));
        $this->assertSame('Outsider', $internalValue);
    }

    public function test_other_participant_labels_and_fallbacks_are_preserved(): void
    {
        $this->assertSame('Teacher', ParticipantTypeLabel::display('Teacher'));
        $this->assertSame('New Teacher', ParticipantTypeLabel::display('New Teacher'));
        $this->assertSame('New Teacher', ParticipantTypeLabel::display('Guru New'));
        $this->assertSame('New Teacher', ParticipantTypeLabel::display('guru_new'));
        $this->assertSame('New Teacher applicants', ParticipantTypeLabel::audience('Guru Baru applicants'));
        $this->assertSame('Participant', ParticipantTypeLabel::display(null));
        $this->assertSame('Target not set', ParticipantTypeLabel::audience('', 'Target not set'));
    }
}
