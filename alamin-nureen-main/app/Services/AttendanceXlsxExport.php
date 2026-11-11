<?php

namespace App\Services;

use ZipArchive;

/** Small dependency-free Office Open XML export with the same clock-in arrangement as the provided example. */
final class AttendanceXlsxExport
{
    private static function esc(mixed $value): string
    {
        return htmlspecialchars((string)$value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private static function cell(string $address, mixed $value, int $style = 0): string
    {
        if (is_numeric($value) && !is_string($value)) {
            return '<c r="'.$address.'" s="'.$style.'"><v>'.$value.'</v></c>';
        }
        return '<c r="'.$address.'" t="inlineStr" s="'.$style.'"><is><t xml:space="preserve">'.self::esc($value).'</t></is></c>';
    }

    private static function row(int $num, array $values): string
    {
        $xml = '<row r="'.$num.'" ht="23" customHeight="1">';
        foreach ($values as $column => $entry) {
            $value = $entry[0] ?? '';
            $style = $entry[1] ?? 0;
            $xml .= self::cell($column.$num, $value, $style);
        }
        return $xml.'</row>';
    }

    /** $details: title,time,location; $participants: scanned_at, organization, name. */
    public static function write(string $output, array $details, array $participants): void
    {
        $rows = [];
        $rows[] = self::row(1, ['B' => ['BORANG PENGESAHAN KEHADIRAN (PENYEDIA LATIHAN & MAJIKAN)', 1]]);
        $rows[] = self::row(2, ['B' => ['Tajuk :', 2], 'C' => [$details['title'] ?? '', 0]]);
        $rows[] = self::row(3, ['B' => ['Masa :', 2], 'C' => [$details['time'] ?? '', 0]]);
        $rows[] = self::row(4, ['B' => ['Tempat :', 2], 'C' => [$details['location'] ?? '', 0]]);
        $rows[] = self::row(6, ['B' => ['CLOCK IN / LOG IN', 3]]);
        $rows[] = self::row(7, ['B' => ['NO', 3], 'C' => ['Timestamp', 3], 'D' => ['Nama Sekolah / Organisasi', 3], 'E' => ['Nama Penuh', 3]]);
        $n = 8;
        foreach ($participants as $i => $participant) {
            $rows[] = self::row($n++, [
                'B' => [$i + 1, 4], 'C' => [$participant['scanned_at'] ?? '', 4],
                'D' => [$participant['organization'] ?? '', 4], 'E' => [$participant['name'] ?? '', 4],
            ]);
        }
        if (!$participants) $rows[] = self::row($n++, ['B' => ['No clock-in recorded', 4]]);
        $n += 2;
        $rows[] = self::row($n++, ['B' => ['(Penyedia Latihan)', 2], 'D' => ['(Majikan)', 2]]);
        $rows[] = self::row($n++, ['B' => ['Tandatangan :', 0], 'D' => ['Tandatangan :', 0]]);
        $rows[] = self::row($n++, ['B' => ['Nama :', 0], 'D' => ['Nama :', 0]]);
        $rows[] = self::row($n++, ['B' => ['Jawatan :', 0], 'D' => ['Jawatan :', 0]]);
        $rows[] = self::row($n++, ['B' => ['Cop Syarikat :', 0], 'D' => ['Cop Syarikat :', 0]]);
        $rows[] = self::row($n++, ['B' => ['Tarikh :', 0], 'D' => ['Tarikh :', 0]]);
        $sheet = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<sheetViews><sheetView showGridLines="0" workbookViewId="0"/></sheetViews>'
            .'<cols><col min="1" max="1" width="3" customWidth="1"/><col min="2" max="2" width="23" customWidth="1"/>'
            .'<col min="3" max="3" width="28" customWidth="1"/><col min="4" max="4" width="34" customWidth="1"/>'
            .'<col min="5" max="5" width="38" customWidth="1"/></cols>'
            .'<sheetData>'.implode('', $rows).'</sheetData>'
            .'<mergeCells count="1"><mergeCell ref="B1:E1"/></mergeCells>'
            .'<pageMargins left="0.25" right="0.25" top="0.4" bottom="0.4" header="0" footer="0"/>'
            .'<pageSetup orientation="landscape" fitToWidth="1"/></worksheet>';
        $styles = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<fonts count="3"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="14"/><color rgb="FF17365D"/><name val="Calibri"/></font><font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font></fonts>'
            .'<fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF275E94"/><bgColor indexed="64"/></patternFill></fill></fills>'
            .'<borders count="2"><border><left/><right/><top/><bottom/><diagonal/></border><border><left style="thin"><color rgb="FFE2E8F0"/></left><right style="thin"><color rgb="FFE2E8F0"/></right><top style="thin"><color rgb="FFE2E8F0"/></top><bottom style="thin"><color rgb="FFE2E8F0"/></bottom><diagonal/></border></borders>'
            .'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            .'<cellXfs count="5"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            .'<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            .'<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0"/>'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf></cellXfs></styleSheet>';
        $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            .'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/></Types>';
        $files = [
            '[Content_Types].xml' => $contentTypes,
            '_rels/.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>',
            'xl/workbook.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Clock In" sheetId="1" r:id="rId1"/></sheets></workbook>',
            'xl/_rels/workbook.xml.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>',
            'xl/worksheets/sheet1.xml' => $sheet,
            'xl/styles.xml' => $styles,
        ];
        if (class_exists(ZipArchive::class)) {
            $zip = new ZipArchive();
            if ($zip->open($output, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new \RuntimeException('Unable to create attendance XLSX');
            }
            foreach ($files as $name => $contents) $zip->addFromString($name, $contents);
            $zip->close();
        } else {
            // XAMPP and some Linux PHP installations do not enable ext-zip.
            // A standards-compliant uncompressed ZIP is still a valid XLSX.
            self::writePortableZip($output, $files);
        }
    }

    public static function writePortableZip(string $output, array $files): void
    {
        $fp = fopen($output, 'wb');
        if (!$fp) throw new \RuntimeException('Unable to write the attendance export');
        $directory = '';
        $offset = 0;
        $entries = 0;
        try {
            foreach ($files as $name => $body) {
                $nameLength = strlen($name);
                $length = strlen($body);
                $crc = crc32($body);
                // Stored entry, no compression or data-descriptor; all fields little-endian.
                $local = pack('VvvvvvVVVvv', 0x04034b50, 20, 0, 0, 0, 0, $crc, $length, $length, $nameLength, 0);
                $local .= $name.$body;
                if (fwrite($fp, $local) !== strlen($local)) throw new \RuntimeException('Cannot write XLSX data');
                $directory .= pack('VvvvvvvVVVvvvvvVV', 0x02014b50, 20, 20, 0, 0, 0, 0, $crc, $length, $length, $nameLength, 0, 0, 0, 0, 0, $offset).$name;
                $offset += strlen($local);
                $entries++;
            }
            $directorySize = strlen($directory);
            $footer = pack('VvvvvVVv', 0x06054b50, 0, 0, $entries, $entries, $directorySize, $offset, 0);
            if (fwrite($fp, $directory.$footer) !== $directorySize + strlen($footer)) throw new \RuntimeException('Cannot finalize XLSX data');
        } finally {
            fclose($fp);
        }
    }
}
