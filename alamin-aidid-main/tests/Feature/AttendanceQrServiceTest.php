<?php

namespace Tests\Feature;

use App\Services\AttendanceQrService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AttendanceQrServiceTest extends TestCase
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
        Carbon::setTestNow('2026-09-05 10:00:00');

        Schema::create('qr_session', function (Blueprint $table): void {
            $table->string('qrID', 20)->primary();
            $table->text('qrCode');
            $table->string('attendanceLink', 255)->nullable();
            $table->dateTime('expiryTime')->nullable();
            $table->string('sessionID', 20)->index('qr_session_session_id_index');
        });
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        Schema::dropIfExists('qr_session');

        parent::tearDown();
    }

    public function test_presentation_alias_uses_the_canonical_string_session_id_directly(): void
    {
        $this->insertQr('QR-MAPPED', 'CS0007', 'https://example.test/mapped.png', null);
        $this->insertQr('QR-OTHER', 'CS1007', 'https://example.test/external.png', null);

        $qr = $this->qrFor($this->courseSession('CS0007'), 'C00010');

        $this->assertSame('active', $qr['state']);
        $this->assertTrue($qr['can_view']);
        $this->assertSame('https://example.test/mapped.png', $qr['image_url']);
        $this->assertSame(route('trainer.courses.attendance.qr', ['C00010', 'CS0007']), $qr['presentation_url']);
        $this->assertArrayNotHasKey('qr_id', $qr);
        $this->assertArrayNotHasKey('session_id', $qr);
        $this->assertArrayNotHasKey('attendance_link', $qr);
    }

    public function test_canonical_string_session_id_is_used_directly_without_prefix_or_numeric_conversion(): void
    {
        $this->insertQr('QR-CANONICAL', 'CSTEST01', 'https://example.test/canonical.png', null);

        $qr = $this->qrFor((object) [
            'sessionID' => 'CSTEST01',
            'sessionDate' => '2026-09-05',
            'sessionName' => 'Synthetic Canonical Session',
            'startTime' => '09:00:00',
            'endTime' => '12:00:00',
        ], 'CTEST01');

        $this->assertSame('active', $qr['state']);
        $this->assertTrue($qr['can_view']);
        $this->assertSame('https://example.test/canonical.png', $qr['image_url']);
        $this->assertSame(
            route('trainer.courses.attendance.qr', ['CTEST01', 'CSTEST01']),
            $qr['presentation_url'],
        );
    }

    public function test_missing_and_future_qrs_cannot_be_viewed_or_expose_an_image_url(): void
    {
        $this->insertQr('QR-UPCOMING', 'CS0007', 'https://example.test/upcoming.png', null);

        $upcoming = $this->qrFor($this->courseSession('CS0007', '2026-09-06'));
        $missing = $this->qrFor($this->courseSession('CS0008'));

        $this->assertSame('upcoming', $upcoming['state']);
        $this->assertFalse($upcoming['can_view']);
        $this->assertNull($upcoming['image_url']);
        $this->assertNull($upcoming['presentation_url']);
        $this->assertSame('missing', $missing['state']);
        $this->assertFalse($missing['can_view']);
        $this->assertNull($missing['image_url']);
        $this->assertNull($missing['presentation_url']);
    }

    public function test_active_session_with_null_expiry_is_viewable_until_session_end(): void
    {
        $this->insertQr('QR-NO-EXPIRY', 'CS0007', 'https://example.test/no-expiry.png', null);

        $qr = $this->qrFor($this->courseSession('CS0007'));

        $this->assertSame('active', $qr['state']);
        $this->assertTrue($qr['can_view']);
        $this->assertSame('https://example.test/no-expiry.png', $qr['image_url']);
        $this->assertSame('Available until 05 Sep 2026, 12:00 PM', $qr['expiry_label']);
    }

    public function test_future_expiry_inside_session_window_shortens_the_viewable_period(): void
    {
        $this->insertQr('QR-EARLY-CLOSE', 'CS0007', 'https://example.test/early-close.png', '2026-09-05 11:30:00');

        $qr = $this->qrFor($this->courseSession('CS0007'));

        $this->assertSame('active', $qr['state']);
        $this->assertTrue($qr['can_view']);
        $this->assertSame('Available until 05 Sep 2026, 11:30 AM', $qr['expiry_label']);
    }

    public function test_passed_expiry_closes_an_otherwise_active_session_qr(): void
    {
        $this->insertQr('QR-EXPIRED-EARLY', 'CS0007', 'https://example.test/expired-early.png', '2026-09-05 09:30:00');

        $qr = $this->qrFor($this->courseSession('CS0007'));

        $this->assertSame('expired', $qr['state']);
        $this->assertFalse($qr['can_view']);
        $this->assertNull($qr['image_url']);
        $this->assertNull($qr['expiry_label']);
    }

    public function test_expiry_after_session_end_cannot_extend_qr_access(): void
    {
        Carbon::setTestNow('2026-09-05 12:15:00');
        $this->insertQr('QR-LATE-EXPIRY', 'CS0007', 'https://example.test/late-expiry.png', '2026-09-05 13:00:00');

        $qr = $this->qrFor($this->courseSession('CS0007'));

        $this->assertSame('expired', $qr['state']);
        $this->assertFalse($qr['can_view']);
        $this->assertNull($qr['image_url']);
        $this->assertSame('12:00:00', $qr['closes_at']->format('H:i:s'));
    }

    public function test_completed_session_with_null_expiry_cannot_be_viewed(): void
    {
        $this->insertQr('QR-COMPLETED', 'CS0007', 'https://example.test/completed.png', null);

        $qr = $this->qrFor($this->courseSession('CS0007', '2026-05-03'));

        $this->assertSame('expired', $qr['state']);
        $this->assertFalse($qr['can_view']);
        $this->assertNull($qr['image_url']);
        $this->assertNull($qr['instruction']);
    }

    public function test_missing_session_end_fails_closed_instead_of_inventing_a_duration(): void
    {
        $this->insertQr('QR-NO-END', 'CS0007', 'https://example.test/no-end.png', null);

        $qr = $this->qrFor($this->courseSession('CS0007', end: null));

        $this->assertSame('closed', $qr['state']);
        $this->assertFalse($qr['can_view']);
        $this->assertNull($qr['image_url']);
        $this->assertNull($qr['closes_at']);
    }

    public function test_legacy_time_is_used_as_start_when_start_time_is_unavailable(): void
    {
        Carbon::setTestNow('2026-09-05 08:30:00');
        $this->insertQr('QR-LEGACY-TIME', 'CS0007', 'https://example.test/legacy.png', null);

        $qr = $this->qrFor($this->courseSession('CS0007', legacyTime: '09:00:00', start: null));

        $this->assertSame('upcoming', $qr['state']);
        $this->assertFalse($qr['can_view']);
        $this->assertSame('09:00:00', $qr['starts_at']->format('H:i:s'));
    }

    public function test_multiple_rows_prefer_the_currently_active_qr_deterministically(): void
    {
        $this->insertQr('QR-EXPIRED', 'CS0007', 'https://example.test/expired.png', '2026-09-05 09:00:00');
        $this->insertQr('QR-A', 'CS0007', 'https://example.test/a.png', '2026-09-05 11:30:00');
        $this->insertQr('QR-Z', 'CS0007', 'https://example.test/z.png', '2026-09-05 11:30:00');

        $qr = $this->qrFor($this->courseSession('CS0007'));

        $this->assertSame('active', $qr['state']);
        $this->assertTrue($qr['can_view']);
        $this->assertSame('https://example.test/z.png', $qr['image_url']);
    }

    private function qrFor(object $session, ?string $courseId = null): array
    {
        return app(AttendanceQrService::class)->forSession($session, $courseId);
    }

    private function courseSession(
        string $id,
        string $date = '2026-09-05',
        ?string $start = '09:00:00',
        ?string $end = '12:00:00',
        ?string $legacyTime = null,
    ): object {
        return (object) [
            'session_id' => $id,
            'date' => $date,
            'start_time' => $start,
            'end_time' => $end,
            'time' => $legacyTime,
        ];
    }

    private function insertQr(string $qrId, string $sessionId, string $imageUrl, ?string $expiry): void
    {
        DB::table('qr_session')->insert([
            'qrID' => $qrId,
            'qrCode' => $imageUrl,
            'attendanceLink' => 'http://localhost/trainhub/attendance.php?sessionID='.$sessionId,
            'expiryTime' => $expiry,
            'sessionID' => $sessionId,
        ]);
    }
}
