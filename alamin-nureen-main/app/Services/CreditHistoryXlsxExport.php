<?php

namespace App\Services;

use ZipArchive;

/** Small XLSX report. Uses the same portable ZIP fallback as attendance export. */
final class CreditHistoryXlsxExport
{
    private static function esc(mixed $v): string
    {
        return htmlspecialchars((string) $v, ENT_XML1 | ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private static function cell(string $address, mixed $value, int $style = 0): string
    {
        if (is_int($value) || is_float($value)) {
            return '<c r="'.$address.'" s="'.$style.'"><v>'.(float)$value.'</v></c>';
        }
        return '<c r="'.$address.'" s="'.$style.'" t="inlineStr"><is><t xml:space="preserve">'.self::esc($value).'</t></is></c>';
    }

    private static function row(int $number, array $cells): string
    {
        $xml = '<row r="'.$number.'">';
        foreach ($cells as $col => $cell) {
            $xml .= self::cell($col.$number, $cell[0], $cell[1] ?? 0);
        }
        return $xml.'</row>';
    }

    /** @param array<string,mixed> $totals */
    public static function write(string $path, string $staffID, int $year, array $totals): void
    {
        $rows = [
            self::row(1, ['A'=>['TrainHub Al Amin | Staff Credit History',1]]),
            self::row(2, ['A'=>['Staff ID',2], 'B'=>[$staffID], 'D'=>['Year',2], 'E'=>[$year]]),
            self::row(3, ['A'=>['Training (capped)',2], 'B'=>[(float)$totals['training']], 'D'=>['Tarbiah (capped)',2], 'E'=>[(float)$totals['tarbiah']]]),
            self::row(4, ['A'=>['Annual credit',2], 'B'=>[(float)$totals['total']], 'D'=>['Annual goal',2], 'E'=>[(float)$totals['target']]]),
            self::row(6, ['A'=>['Date',3], 'B'=>['Activity',3], 'C'=>['Type',3], 'D'=>['Earned hours',3], 'E'=>['Credit rule',3]]),
        ];
        $n = 7;
        foreach ($totals['records'] as $record) {
            $rows[] = self::row($n++, [
                'A'=>[(string)($record['date'] ?? ''),4],
                'B'=>[(string)($record['name'] ?? ''),4],
                'C'=>[(string)($record['category'] ?? ''),4],
                'D'=>[(float)($record['hours'] ?? 0),4],
                'E'=>[(string)($record['rule'] ?? ''),4],
            ]);
        }
        if (!$totals['records']) $rows[] = self::row($n++, ['A'=>['No approved attendance for this year',4]]);
        $sheet = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<sheetViews><sheetView showGridLines="0" workbookViewId="0"/></sheetViews>'
            .'<cols><col min="1" max="1" width="19" customWidth="1"/><col min="2" max="2" width="47" customWidth="1"/>'
            .'<col min="3" max="3" width="18" customWidth="1"/><col min="4" max="4" width="22" customWidth="1"/>'
            .'<col min="5" max="5" width="64" customWidth="1"/></cols>'
            .'<sheetData>'.implode('', $rows).'</sheetData>'
            .'<mergeCells count="1"><mergeCell ref="A1:E1"/></mergeCells>'
            .'<autoFilter ref="A6:E'.max(7,$n-1).'"/>'
            .'<pageMargins left="0.25" right="0.25" top="0.4" bottom="0.4" header="0" footer="0"/></worksheet>';
        $styles = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<fonts count="3"><font><sz val="11"/><name val="Calibri"/></font>'
            .'<font><b/><sz val="15"/><color rgb="FF17365D"/><name val="Calibri"/></font>'
            .'<font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font></fonts>'
            .'<fills count="3"><fill><patternFill patternType="none"/></fill>'
            .'<fill><patternFill patternType="gray125"/></fill>'
            .'<fill><patternFill patternType="solid"><fgColor rgb="FF2563EB"/><bgColor indexed="64"/></patternFill></fill></fills>'
            .'<borders count="2"><border><left/><right/><top/><bottom/><diagonal/></border>'
            .'<border><left style="thin"/><right style="thin"/><top style="thin"/><bottom style="thin"/></border></borders>'
            .'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            .'<cellXfs count="5"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            .'<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0"/>'
            .'<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0"/>'
            .'<xf numFmtId="0" fontId="2" fillId="2" borderId="0" xfId="0"/>'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0"/></cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>';
        $files = [
            '[Content_Types].xml'=>'<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/></Types>',
            '_rels/.rels'=>'<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>',
            'xl/workbook.xml'=>'<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Credit History" sheetId="1" r:id="rId1"/></sheets></workbook>',
            'xl/_rels/workbook.xml.rels'=>'<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>',
            'xl/worksheets/sheet1.xml'=>$sheet,
            'xl/styles.xml'=>$styles,
        ];
        if (class_exists(ZipArchive::class)) {
            $zip = new ZipArchive();
            if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) throw new \RuntimeException('Cannot create credit history XLSX');
            foreach ($files as $file => $contents) $zip->addFromString($file, $contents);
            $zip->close();
        } else {
            AttendanceXlsxExport::writePortableZip($path, $files);
        }
    }
}
