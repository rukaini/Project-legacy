<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>{{ $form->form_name }}_{{ $guru->gn_name }}_{{ $response->observation_date ? \Carbon\Carbon::parse($response->observation_date)->format('d-m-Y') : 'No Date' }}</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #eef2f7;
            color: #111;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
        }

        /* Toolbar */
        .toolbar {
            position: sticky;
            top: 0;
            z-index: 50;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 12px 20px;
            background: #0f172a;
            color: white;
            font-size: 12px;
        }

        .toolbar-actions {
            display: flex;
            gap: 8px;
        }

        .button {
            display: inline-block;
            padding: 9px 14px;
            border: 0;
            border-radius: 7px;
            background: #2563eb;
            color: white;
            font: inherit;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
        }

        .button.secondary {
            background: white;
            color: #334155;
        }

        /* Paper */
        .paper {
            width: 210mm;
            margin: 18px auto;
            background: white;
            box-shadow: 0 10px 30px rgba(15, 23, 42, .15);
        }

        .page {
            width: 100%;
            min-height: 297mm;
            padding: 8mm 10mm 10mm;
            background: white;
        }

        /* Header */
        .report-header {
            margin: 0 0 14px;
            text-align: center;
        }

        .report-header h1 {
            margin: 0;
            font-size: 13px;
            font-weight: 700;
        }

        .report-header h2 {
            margin: 3px 0 0;
            font-size: 13px;
            font-weight: 700;
        }

        /* Teacher information */
        .meta-table {
            width: 100%;
            margin-bottom: 12px;
            border-collapse: collapse;
        }

        .meta-table td {
            padding: 5px 4px;
            vertical-align: bottom;
        }

        .meta-label {
            width: 28%;
            font-weight: 700;
        }

        .meta-colon {
            width: 3%;
            text-align: center;
        }

        .meta-value {
            border-bottom: 1px solid #111;
        }

        /* Evaluation table */
        .score-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .score-table th,
        .score-table td {
            border: 1px solid #111;
            padding: 5px 6px;
        }

        .score-table th {
            text-align: center;
            vertical-align: middle;
            font-weight: 700;
        }

        .bil-col {
            width: 6%;
            text-align: center;
            vertical-align: top;
        }

        .item-col {
            width: 46%;
            vertical-align: top;
        }

        .score-col {
            width: 18%;
            text-align: center;
            vertical-align: top;
        }

        .comment-col {
            width: 30%;
            vertical-align: top;
            line-height: 1.5;
            white-space: pre-line;
        }

        .section-title {
            margin-bottom: 4px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .criteria-line {
            margin: 2px 0;
            line-height: 1.4;
        }

        /* Score */
        .score-grid {
            display: flex;
            width: 100%;
            gap: 2px;
            margin-bottom: 3px;
        }

        .score-box {
            flex: 1;
            min-width: 0;
            min-height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid transparent;
        }

        .score-box.selected {
            border: 1px solid #111;
            background: #e0e7ff;
            font-weight: 700;
        }

        /* Summary */
        .summary-row td {
            vertical-align: middle;
            font-weight: 700;
        }

        .summary-label {
            text-align: right;
        }

        /* Page 2 */
        .page-two {
            page-break-before: always;
            break-before: page;
        }

        /* Other comment */
        .other-comment {
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .other-comment-title {
            margin-bottom: 7px;
            font-weight: 700;
        }

        .other-comment-box {
            min-height: 90px;
            padding: 9px;
            border: 1px solid #111;
            line-height: 1.5;
            white-space: pre-line;
        }

        /* Achievement */
        .achievement-section {
            margin-top: 22px;
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .achievement-title {
            padding: 8px;
            border: 1px solid #111;
            border-bottom: 0;
            text-align: center;
            font-weight: 700;
        }

        .achievement-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .achievement-table th,
        .achievement-table td {
            border: 1px solid #111;
            padding: 8px 5px;
            text-align: center;
        }

        .achievement-table th {
            font-weight: 400;
        }

        .achievement-active {
            background: #dbeafe;
            font-weight: 700;
        }

        .check {
            color: #16a34a;
            font-size: 16px;
            font-weight: 700;
        }

        /* Signature */
        .signature-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
            margin-top: 30px;
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .signature-box {
            text-align: center;
        }

        .signature-label {
            margin-bottom: 42px;
            text-align: left;
            font-size: 10px;
        }

        .signature-line {
            min-height: 22px;
            padding-top: 5px;
            border-top: 1px solid #111;
        }

        tr {
            page-break-inside: avoid;
            break-inside: avoid;
        }

        @page {
            size: A4 portrait;
            margin: 0;
        }

        @media print {

            body {
                background: white;
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }

            .toolbar {
                display: none !important;
            }

            .paper {
                width: 100%;
                margin: 0;
                box-shadow: none;
            }

            .page {
                width: 210mm;
                min-height: 297mm;
                margin: 0;
                padding: 8mm 10mm 10mm;
            }

            .page-two {
                page-break-before: always;
                break-before: page;
            }

        }
    </style>

</head>

<body>

    {{-- Toolbar --}}
    <div class="toolbar">

        <strong>
            PRE Observation Result
        </strong>

        <div class="toolbar-actions">

            <a href="{{ route('observer.manage', $response->gn_id) }}" class="button secondary">
                Back
            </a>

            <button type="button" class="button" onclick="window.print()">
                Print / Save PDF
            </button>

        </div>

    </div>


    <main class="paper">

        {{-- PAGE 1 --}}
        <section class="page">

            {{-- Header --}}
            <header class="report-header">

                <h1>
                    PUSAT PENDIDIKAN AL AMIN BERHAD (PPAAB)
                </h1>

                <h2>
                    {{ $form->form_name }}
                </h2>

            </header>


            {{-- Teacher Information --}}
            <table class="meta-table">

                <tr>
                    <td class="meta-label">NAMA GURU</td>
                    <td class="meta-colon">:</td>
                    <td class="meta-value">
                        {{ $guru->gn_name }}
                    </td>
                </tr>

                <tr>
                    <td class="meta-label">MATA PELAJARAN & KELAS</td>
                    <td class="meta-colon">:</td>
                    <td class="meta-value">
                        {{ $response->subject_name ?? '-' }}
                        /
                        {{ $response->class_name ?? '-' }}
                    </td>
                </tr>

                <tr>
                    <td class="meta-label">HARI & TARIKH</td>
                    <td class="meta-colon">:</td>
                    <td class="meta-value">
                        {{
                            $response->observation_date
                                ? \Carbon\Carbon::parse($response->observation_date)->format('l, d/m/Y')
                                : '-'
                        }}
                    </td>
                </tr>

                <tr>
                    <td class="meta-label">PENYELIA</td>
                    <td class="meta-colon">:</td>
                    <td class="meta-value">
                        {{
                            $response->observer?->teacher?->teacherName
                            ?? '-'
                        }}
                    </td>
                </tr>

            </table>


            {{-- Evaluation Table --}}
            <table class="score-table">

                <thead>

                    <tr>
                        <th class="bil-col">BIL</th>
                        <th class="item-col">PERKARA</th>
                        <th class="score-col">SKOR</th>
                        <th class="comment-col">ULASAN</th>
                    </tr>

                </thead>

                <tbody>

                    @foreach($form->sections as $section)

                    @if($section->criteria->isNotEmpty())

                    <tr>

                        {{-- Number --}}
                        <td class="bil-col">
                            {{ $loop->iteration }}
                        </td>


                        {{-- Section and Criteria --}}
                        <td class="item-col">

                            <div class="section-title">
                                {{ $section->section_name }}
                            </div>

                            @foreach($section->criteria as $criteria)

                            <div class="criteria-line">
                                {{ chr(96 + $loop->iteration) }}.
                                {{ $criteria->criteria_label }}
                            </div>

                            @endforeach

                        </td>


                        {{-- Score --}}
                        <td class="score-col">

                            @foreach($section->criteria as $criteria)

                            @php
                            $score = $scores[$criteria->criteriaID] ?? null;
                            @endphp

                            <div class="score-grid">

                                @for($i = $form->min_score; $i <= $form->max_score; $i++)

                                    <div class="score-box {{ (int) $score === $i ? 'selected' : '' }}">
                                        {{ $i }}
                                    </div>

                                    @endfor

                            </div>

                            @endforeach

                        </td>


                        {{-- Comment --}}
                        <td class="comment-col">
                            {{
                                        $sectionComments[$section->sectionID]
                                        ?? '-'
                                    }}
                        </td>

                    </tr>

                    @endif

                    @endforeach


                    @php
                    $criteriaCount = $form->sections->sum(
                    fn($section) => $section->criteria->count()
                    );

                    $maximumScore = $criteriaCount * $form->max_score;
                    @endphp


                    {{-- Total --}}
                    <tr class="summary-row">

                        <td></td>

                        <td class="summary-label">
                            JUMLAH
                        </td>

                        <td class="score-col">
                            {{ $response->total_score }}
                            /
                            {{ $maximumScore }}
                        </td>

                        <td>
                            PERATUS :
                            {{ number_format((float) $response->percentage, 2) }}%
                        </td>

                    </tr>

                </tbody>

            </table>

        </section>


        {{-- PAGE 2 --}}
        <section class="page page-two">

            {{-- Other Comment --}}
            <section class="other-comment">

                <div class="other-comment-title">
                    ULASAN LAIN :
                </div>

                <div class="other-comment-box">
                    {{ $response->other_comment ?: '-' }}
                </div>

            </section>


            {{-- Achievement --}}
            <section class="achievement-section">

                <div class="achievement-title">
                    TAHAP PENCAPAIAN :
                </div>

                <table class="achievement-table">

                    <thead>

                        <tr>
                            <th>LEMAH</th>
                            <th>MEMUASKAN</th>
                            <th>BAIK</th>
                            <th>SANGAT BAIK</th>
                            <th>CEMERLANG</th>
                        </tr>

                    </thead>

                    <tbody>

                        <tr>

                            <td class="{{ $response->achievement_level === 'Weak' ? 'achievement-active' : '' }}">
                                0% - 39%
                            </td>

                            <td class="{{ $response->achievement_level === 'Satisfactory' ? 'achievement-active' : '' }}">
                                40% - 59%
                            </td>

                            <td class="{{ $response->achievement_level === 'Good' ? 'achievement-active' : '' }}">
                                60% - 79%
                            </td>

                            <td class="{{ $response->achievement_level === 'Very Good' ? 'achievement-active' : '' }}">
                                80% - 89%
                            </td>

                            <td class="{{ $response->achievement_level === 'Excellent' ? 'achievement-active' : '' }}">
                                90% - 100%
                            </td>

                        </tr>

                        <tr>

                            <td class="check">
                                @if($response->achievement_level === 'Weak')
                                ✓
                                @endif
                            </td>

                            <td class="check">
                                @if($response->achievement_level === 'Satisfactory')
                                ✓
                                @endif
                            </td>

                            <td class="check">
                                @if($response->achievement_level === 'Good')
                                ✓
                                @endif
                            </td>

                            <td class="check">
                                @if($response->achievement_level === 'Very Good')
                                ✓
                                @endif
                            </td>

                            <td class="check">
                                @if($response->achievement_level === 'Excellent')
                                ✓
                                @endif
                            </td>

                        </tr>

                    </tbody>

                </table>

            </section>


            {{-- Signature --}}
            <section class="signature-section">

                <div class="signature-box">

                    <div class="signature-label">
                        DISELIA OLEH:
                    </div>

                    <div class="signature-line">
                        (
                        {{
                            $response->observer?->teacher?->teacherName
                            ?? '-'
                        }}
                        )
                    </div>

                </div>


                <div class="signature-box">

                    <div class="signature-label">
                        GURU DISELIA :
                    </div>

                    <div class="signature-line">
                        ( {{ $guru->gn_name }} )
                    </div>

                </div>

            </section>

        </section>

    </main>

</body>

</html>