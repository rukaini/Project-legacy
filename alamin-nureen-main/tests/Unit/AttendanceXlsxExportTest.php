<?php

namespace Tests\Unit;

use App\Services\AttendanceXlsxExport;
use PHPUnit\Framework\TestCase;
use ZipArchive;

class AttendanceXlsxExportTest extends TestCase
{
    public function test_clock_in_only_xlsx_contains_reference_layout_and_no_logout(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'trainhub_attendance_');
        try {
            AttendanceXlsxExport::write($file,
                ['title'=>'Training Example','time'=>'9:00 AM','location'=>'Hall A'],
                [['scanned_at'=>'19/10/2026 09:03 AM','organization'=>'School A','name'=>'Test Person']]);
            if (class_exists(ZipArchive::class)) {
                $zip = new ZipArchive();
                $this->assertTrue($zip->open($file) === true);
                $sheet = $zip->getFromName('xl/worksheets/sheet1.xml');
                $zip->close();
            } else {
                // The portable path writes uncompressed ZIP entries.
                $sheet = file_get_contents($file);
            }
            $this->assertIsString($sheet);
            $this->assertStringContainsString('LOG IN', $sheet);
            $this->assertStringContainsString('Training Example', $sheet);
            $this->assertStringContainsString('Test Person', $sheet);
            $this->assertStringNotContainsString('LOG OUT', $sheet);
        } finally {
            if (is_file($file)) unlink($file);
        }
    }
}
