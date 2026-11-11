<?php

namespace Tests\Unit;

use App\Services\CreditHistoryXlsxExport;
use PHPUnit\Framework\TestCase;
use ZipArchive;

class CreditHistoryXlsxExportTest extends TestCase
{
    public function test_credit_report_includes_year_and_activity(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'th_credit_test_');
        try {
            CreditHistoryXlsxExport::write($file, 'SE001', 2026, [
                'training'=>6.0, 'tarbiah'=>2.0, 'total'=>8.0, 'target'=>40.0,
                'records'=>[['date'=>'2026-10-20', 'name'=>'External Leadership',
                    'category'=>'Training', 'hours'=>6.0, 'rule'=>'Approved order x1.0']],
            ]);
            if (class_exists(ZipArchive::class)) {
                $zip = new ZipArchive();
                $this->assertTrue($zip->open($file) === true);
                $sheet = $zip->getFromName('xl/worksheets/sheet1.xml');
                $zip->close();
            } else {
                $sheet = file_get_contents($file);
            }
            $this->assertIsString($sheet);
            $this->assertStringContainsString('Credit History', $sheet);
            $this->assertStringContainsString('External Leadership', $sheet);
            $this->assertStringContainsString('SE001', $sheet);
        } finally {
            @unlink($file);
        }
    }
}
