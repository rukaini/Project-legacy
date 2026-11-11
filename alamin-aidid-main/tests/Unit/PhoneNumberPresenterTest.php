<?php

namespace Tests\Unit;

use App\Support\PhoneNumberPresenter;
use PHPUnit\Framework\TestCase;

class PhoneNumberPresenterTest extends TestCase
{
    public function test_it_formats_unformatted_malaysian_local_mobile_numbers(): void
    {
        $this->assertSame('010-000 0003', PhoneNumberPresenter::display('0100000003'));
        $this->assertSame('012-345 6789', PhoneNumberPresenter::display('0123456789'));
        $this->assertSame('011-1234 5678', PhoneNumberPresenter::display('01112345678'));
    }

    public function test_it_preserves_formatted_and_unrecognized_values_and_handles_missing_values(): void
    {
        $this->assertSame('012-345 6789', PhoneNumberPresenter::display(' 012-345 6789 '));
        $this->assertSame('+60 12-345 6789', PhoneNumberPresenter::display('+60 12-345 6789'));
        $this->assertSame('03-1234 5678', PhoneNumberPresenter::display('03-1234 5678'));
        $this->assertSame('Not available', PhoneNumberPresenter::display(null));
        $this->assertSame('Unavailable', PhoneNumberPresenter::display(' ', 'Unavailable'));
    }
}
