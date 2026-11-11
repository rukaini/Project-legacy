<!DOCTYPE html>
<html lang="ms">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        {{ $form->form_name }}_{{ $guru->gn_name }}_{{ $response->observation_date ? \Carbon\Carbon::parse($response->observation_date)->format('d-m-Y') : 'No Date' }}
    </title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #eef2f7;
            color: #111827;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10px;
        }

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

        .paper {
            width: 100%;
            max-width: 1500px;
            margin: 18px auto;
            padding: 12mm;
            background: white;
            box-shadow: 0 10px 30px rgba(15, 23, 42, .15);
        }

        .report-header {
            margin-bottom: 12px;
            text-align: center;
        }

        .report-header h1 {
            margin: 0 0 6px;
            font-size: 17px;
        }

        .meta {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            margin-bottom: 12px;
            border: 1px solid #111;
        }

        .meta>div {
            min-height: 46px;
            padding: 6px 8px;
            border-right: 1px solid #111;
            border-bottom: 1px solid #111;
        }

        .meta>div:nth-child(4n) {
            border-right: 0;
        }

        .meta>div:nth-last-child(-n+4) {
            border-bottom: 0;
        }

        .meta strong {
            display: block;
            margin-bottom: 3px;
            font-size: 8px;
            text-transform: uppercase;
        }

        .score-table {
            width: 100%;
            margin-bottom: 14px;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .score-table th,
        .score-table td {
            border: 1px solid #111;
            padding: 5px 6px;
        }

        .score-table thead th {
            background: #244294;
            color: white;
            text-align: center;
            vertical-align: middle;
            font-weight: 700;
        }

        .aspect-cell {
            background: #dbeafe;
            text-align: center;
            vertical-align: middle !important;
            font-weight: 700;
        }

        .tums-cell {
            background: #eff6ff;
            text-align: center;
            vertical-align: middle !important;
            font-weight: 700;
        }

        .point-cell {
            background: white;
            vertical-align: middle !important;
            line-height: 1.4;
        }

        .score-cell {
            background: white;
            text-align: center;
            vertical-align: middle !important;
            font-size: 12px;
            font-weight: 700;
        }

        .rubric-cell {
            padding: 0 !important;
            background: white;
            vertical-align: top !important;
        }

        .rubric-row {
            display: grid;
            grid-template-columns: 55px 1fr;
            min-height: 55px;
            border-bottom: 1px solid #111;
        }

        .rubric-row:last-child {
            border-bottom: 0;
        }

        .rubric-score {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 5px 3px;
            border-right: 1px solid #111;
            text-align: center;
            font-weight: 700;
        }

        .rubric-text {
            display: flex;
            align-items: center;
            padding: 7px;
            white-space: pre-line;
            line-height: 1.35;
        }

        .summary-label {
            background: #eff6ff;
            vertical-align: middle !important;
            font-weight: 700;
        }

        .summary-value {
            background: #f8fafc;
            text-align: center;
            vertical-align: middle !important;
        }

        .tums-total-label {
            background: #eff6ff;
            font-weight: 700;
        }

        .tums-total-value {
            background: #2563eb;
            color: white;
            text-align: center;
            font-weight: 700;
        }

        .observation-summary {
            display: grid;
            grid-template-columns: minmax(0, 2fr) minmax(250px, .85fr);
            gap: 22px;
            align-items: start;
            page-break-inside: avoid;
        }

        .summary-title {
            margin: 0 0 6px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .result-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .result-table th,
        .result-table td {
            border: 1px solid #111;
            padding: 5px 6px;
        }

        .result-table th {
            background: #244294;
            color: white;
            text-align: center;
        }

        .result-aspect {
            width: 10%;
            background: #e0e7ff;
            text-align: center;
        }

        .result-aspect-title {
            width: 31%;
            background: #e0e7ff;
        }

        .result-tums {
            width: 13%;
            background: #e0e7ff;
            text-align: center;
        }

        .result-weight {
            width: 14%;
            background: #e0e7ff;
            text-align: center;
        }

        .result-number {
            width: 16%;
            background: #ecfdf5;
            text-align: center;
        }

        .result-total-label {
            background: #e0e7ff;
            text-align: right;
            font-weight: 700;
        }

        .result-total-value {
            background: #2563eb;
            color: white;
            text-align: center;
            font-weight: 700;
        }

        .grade-range,
        .grade-check {
            text-align: center;
        }

        .grade-active td {
            background: #dbeafe;
            font-weight: 700;
        }

        .grade-active .grade-check {
            color: #16a34a;
            font-size: 16px;
            font-weight: 700;
        }

        .result-status {
            width: 100%;
            margin-top: 12px;
            border-collapse: collapse;
        }

        .result-status td {
            border: 1px solid #111;
            padding: 7px 10px;
        }

        .result-status-label {
            width: 62%;
            background: #e0e7ff;
            font-weight: 700;
        }

        .pass {
            width: 38%;
            background: #dcfce7;
            color: #166534;
            text-align: center;
            font-weight: 700;
        }

        .repeat {
            width: 38%;
            background: #fee2e2;
            color: #991b1b;
            text-align: center;
            font-weight: 700;
        }

        tr {
            page-break-inside: avoid;
        }

        @page {
            size: A4 landscape;
            margin: 7mm;
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
                max-width: none;
                margin: 0;
                padding: 0;
                box-shadow: none;
            }

        }
    </style>

</head>


<body>

    {{-- Toolbar --}}
    <div class="toolbar">

        <strong>
            PDPC Observation Result
        </strong>

        <div class="toolbar-actions">

            <a href="{{ route('principal.result.show', $guru->gn_id) }}" class="button secondary">
                Back
            </a>

            <button class="button" type="button" onclick="window.print()">
                Print / Save PDF
            </button>

        </div>

    </div>


    <main class="paper">

        {{-- Header --}}
        <header class="report-header">
            <h1>STANDARD 4: PEMBELAJARAN DAN PEMUDAHCARAAN</h1>
        </header>


        {{-- Information --}}
        <section class="meta">

            <div>
                <strong>Guru Dinilai</strong>
                {{ $guru->gn_name }}
            </div>

            <div>
                <strong>Sekolah</strong>
                {{ $guru->school?->schoolName ?? '-' }}
            </div>

            <div>
                <strong>Kelas</strong>
                {{ $response->class_name ?? '-' }}
            </div>

            <div>
                <strong>Mata Pelajaran</strong>
                {{ $response->subject_name ?? '-' }}
            </div>


            <div>

                <strong>Penilai</strong>

                @if($response->observation_stage === 'EXTERNAL')

                {{ $response->externalObserver?->teacher?->teacherName ?? '-' }}

                @else

                {{ $response->observer?->teacher?->teacherName ?? '-' }}

                @endif

            </div>


            <div>

                <strong>Jenis Penilai</strong>

                {{ $response->observation_stage === 'EXTERNAL' ? 'External Observer' : 'Observer' }}

            </div>


            <div>

                <strong>Tarikh</strong>

                {{ $response->observation_date ? \Carbon\Carbon::parse($response->observation_date)->format('d/m/Y') : '-' }}

            </div>


            <div>

                <strong>Masa</strong>

                {{ $response->observation_time ? substr((string) $response->observation_time, 0, 5) : '-' }}

            </div>

        </section>


        {{-- Aspects --}}
        @foreach($form->aspects as $aspect)

        @php
        $aspectRows = 0;

        foreach ($aspect->tums as $tums) {

        $pointCount = $tums->tt
        ->flatMap(fn($tt) => $tt->points)
        ->count();

        $aspectRows += max(1, $pointCount) + 4;
        }
        @endphp


        <table class="score-table">

            <colgroup>
                <col style="width: 9%">
                <col style="width: 9%">
                <col style="width: 39%">
                <col style="width: 4%">
                <col style="width: 4%">
                <col style="width: 35%">
            </colgroup>


            <thead>

                <tr>
                    <th>ASPEK</th>
                    <th>TUMS</th>
                    <th>TAHAP TINDAKAN</th>
                    <th colspan="2">SKOR</th>
                    <th>RUBRIK TAHAP KUALITI (RTK)</th>
                </tr>

            </thead>


            <tbody>

                @foreach($aspect->tums as $tums)

                @php
                $points = $tums->tt
                ->flatMap(fn($tt) => $tt->points)
                ->values();

                $pointRows = max(1, $points->count());
                $tumsRows = $pointRows + 4;

                $summary = $tumsResults[$tums->tumsID] ?? null;

                $rubrics = $tums->rubrics
                ->sortByDesc('score')
                ->values();
                @endphp


                @forelse($points as $point)

                @php
                $score = $scores->get($point->pointID);
                @endphp


                <tr>

                    {{-- Aspect --}}
                    @if($loop->parent->first && $loop->first)

                    <td class="aspect-cell" rowspan="{{ $aspectRows }}">

                        {{ $aspect->aspect_code }}

                        <br><br>

                        {{ $aspect->aspect_name }}

                    </td>

                    @endif


                    {{-- TUMS --}}
                    @if($loop->first)

                    <td class="tums-cell" rowspan="{{ $tumsRows }}">
                        {{ $tums->tums_code }}
                    </td>

                    @endif


                    {{-- Point --}}
                    <td class="point-cell">

                        @if($loop->first)

                        <strong>
                            {{ $tums->tums_name }}
                        </strong>

                        <br><br>

                        @endif

                        {{ $loop->iteration }}.
                        {{ $point->point_text }}

                    </td>


                    {{-- Saved Score --}}
                    <td colspan="2" class="score-cell">
                        {{ $score ?? '-' }}
                    </td>


                    {{-- Rubric --}}
                    @if($loop->first)

                    <td rowspan="{{ $tumsRows }}" class="rubric-cell">

                        @foreach($rubrics as $rubric)

                        <div class="rubric-row">

                            <div class="rubric-score">
                                RTK {{ $rubric->score }}
                            </div>

                            <div class="rubric-text">
                                {{ $rubric->description ?? '-' }}
                            </div>

                        </div>

                        @endforeach

                    </td>

                    @endif

                </tr>


                @empty

                <tr>

                    @if($loop->first)

                    <td class="aspect-cell" rowspan="{{ $aspectRows }}">

                        {{ $aspect->aspect_code }}

                        <br><br>

                        {{ $aspect->aspect_name }}

                    </td>

                    @endif


                    <td class="tums-cell" rowspan="{{ $tumsRows }}">
                        {{ $tums->tums_code }}
                    </td>

                    <td class="point-cell">
                        <strong>{{ $tums->tums_name }}</strong>
                    </td>

                    <td colspan="2" class="score-cell">
                        -
                    </td>

                    <td rowspan="{{ $tumsRows }}" class="rubric-cell"></td>

                </tr>

                @endforelse


                {{-- Calculation 1 --}}
                <tr>

                    <td class="summary-label">
                        Bilangan Tindakan / Jumlah Skor Kualiti
                    </td>

                    <td class="summary-value">
                        {{ $summary['action_count'] ?? 0 }}
                    </td>

                    <td class="summary-value">
                        {{ $summary['quality_total'] ?? 0 }}
                    </td>

                </tr>


                {{-- Calculation 2 --}}
                <tr>

                    <td class="summary-label">
                        Skor Tahap Tindakan / Min Skor Tahap Kualiti
                    </td>

                    <td class="summary-value">
                        {{ $summary['action_score'] ?? 0 }}
                    </td>

                    <td class="summary-value">
                        {{ isset($summary) ? number_format($summary['quality_mean'], 2) : '0.00' }}
                    </td>

                </tr>


                {{-- Calculation 3 --}}
                <tr>

                    <td class="summary-label">
                        Peratus Skor Tahap Tindakan / Peratus Skor Tahap Kualiti
                    </td>

                    <td class="summary-value">
                        {{ isset($summary) ? number_format($summary['action_percentage'], 2) : '0.00' }}%
                    </td>

                    <td class="summary-value">
                        {{ isset($summary) ? number_format($summary['quality_percentage'], 2) : '0.00' }}%
                    </td>

                </tr>


                {{-- TUMS --}}
                <tr>

                    <td colspan="2" class="tums-total-label">
                        Peratus TUMS
                    </td>

                    <td class="tums-total-value">
                        {{ isset($summary) ? number_format($summary['tums_percentage'], 2) : '0.00' }}%
                    </td>

                </tr>

                @endforeach

            </tbody>

        </table>

        @endforeach


        {{-- Build summary rows --}}
        @php
        $summaryRows = [];

        foreach ($form->aspects as $aspect) {

        foreach ($aspect->tums as $tums) {

        $summary = $tumsResults[$tums->tumsID] ?? null;

        $summaryRows[] = [
        'aspect_code' => $aspect->aspect_code,
        'aspect_name' => $aspect->aspect_name,
        'tums_code' => $tums->tums_code,
        'weight' => (float) $tums->wajaran,
        'percentage' => $summary['tums_percentage'] ?? 0,
        'score' => $summary['weighted_score'] ?? 0,
        ];
        }
        }

        $groupedRows = collect($summaryRows)->groupBy('aspect_code');

        $achievementMap = [
        'Excellent' => 'CEMERLANG',
        'Good' => 'BAIK',
        'Satisfactory' => 'SEDERHANA',
        'Weak' => 'LEMAH',
        'Very Weak' => 'SANGAT LEMAH',
        ];

        $achievementLevel = $achievementMap[$response->achievement_level] ?? '-';
        @endphp


        {{-- Overall Summary --}}
        <section class="observation-summary">

            {{-- Summary --}}
            <div>

                <h2 class="summary-title">
                    Rumusan Pencerapan
                </h2>

                <table class="result-table">

                    <thead>

                        <tr>
                            <th colspan="2">ASPEK</th>
                            <th>TUMS</th>
                            <th>WAJARAN</th>
                            <th>%</th>
                            <th>SKOR</th>
                        </tr>

                    </thead>


                    <tbody>

                        @foreach($groupedRows as $aspectRows)

                        @foreach($aspectRows as $row)

                        <tr>

                            @if($loop->first)

                            <td class="result-aspect" rowspan="{{ $aspectRows->count() }}">
                                {{ $row['aspect_code'] }}
                            </td>

                            <td class="result-aspect-title" rowspan="{{ $aspectRows->count() }}">
                                {{ $row['aspect_name'] }}
                            </td>

                            @endif


                            <td class="result-tums">
                                {{ $row['tums_code'] }}
                            </td>

                            <td class="result-weight">
                                {{ number_format($row['weight'], 2) }}
                            </td>

                            <td class="result-number">
                                {{ number_format($row['percentage'], 2) }}
                            </td>

                            <td class="result-number">
                                {{ number_format($row['score'], 2) }}
                            </td>

                        </tr>

                        @endforeach

                        @endforeach


                        {{-- Final percentage --}}
                        <tr>

                            <td colspan="5" class="result-total-label">
                                JUMLAH
                            </td>

                            <td class="result-total-value">
                                {{ number_format((float) $response->percentage, 2) }}
                            </td>

                        </tr>

                    </tbody>

                </table>

            </div>


            {{-- Achievement --}}
            <div>

                <h2 class="summary-title">
                    TARAF PENCAPAIAN
                </h2>

                <table class="result-table">

                    <thead>

                        <tr>
                            <th>TARAF</th>
                            <th>SKOR</th>
                            <th>✓</th>
                        </tr>

                    </thead>

                    <tbody>

                        @foreach([
                        ['CEMERLANG', '90 - 100'],
                        ['BAIK', '80 - 89.99'],
                        ['SEDERHANA', '50 - 79.99'],
                        ['LEMAH', '20 - 49.99'],
                        ['SANGAT LEMAH', '0 - 19.99'],
                        ] as [$level, $range])

                        <tr class="{{ $achievementLevel === $level ? 'grade-active' : '' }}">

                            <td>
                                {{ $level }}
                            </td>

                            <td class="grade-range">
                                {{ $range }}
                            </td>

                            <td class="grade-check">

                                @if($achievementLevel === $level)
                                ✓
                                @endif

                            </td>

                        </tr>

                        @endforeach

                    </tbody>

                </table>


                {{-- EXTERNAL result only --}}
                @if($response->observation_stage === 'EXTERNAL' && $response->result)

                <table class="result-status">

                    <tr>

                        <td class="result-status-label">
                            Keputusan
                        </td>

                        <td class="{{ $response->result === 'PASS' ? 'pass' : 'repeat' }}">
                            {{ $response->result }}
                        </td>

                    </tr>

                </table>

                @endif

            </div>

        </section>

    </main>

</body>

</html>