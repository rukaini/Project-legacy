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

        html,
        body {
            margin: 0;
            padding: 0;
        }

        body {
            background: #eef2f7;
            color: #111;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 8pt;
            line-height: 1.15;
        }


        /* Toolbar */
        .toolbar {
            position: sticky;
            top: 0;
            z-index: 50;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 20px;
            background: #0f172a;
            color: white;
        }

        .toolbar-title {
            font-size: 13px;
            font-weight: 600;
        }

        .toolbar-actions {
            display: flex;
            gap: 8px;
        }

        .button {
            display: inline-block;
            padding: 8px 13px;
            border: 0;
            border-radius: 6px;
            background: #2563eb;
            color: white;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
        }

        .button.secondary {
            background: white;
            color: #334155;
        }


        /* A4 paper */
        .paper {
            width: 210mm;
            min-height: 297mm;
            margin: 12px auto;
            padding: 5mm 9mm;
            background: white;
            box-shadow: 0 8px 25px rgba(15, 23, 42, 0.15);
        }


        /* Report header */
        .report-header {
            margin: 0 0 2.2mm;
            text-align: center;
            line-height: 1.1;
        }

        .report-header h1 {
            margin: 0 0 1px;
            font-size: 9pt;
            font-weight: 700;
        }

        .report-header p {
            margin: 0;
            font-size: 9pt;
            font-weight: 700;
        }


        /* Observation information */
        .meta {
            width: 84%;
            margin: 0 auto 2.5mm;
            font-size: 8pt;
        }

        .meta-row {
            display: grid;
            grid-template-columns: 33mm 4mm 1fr;
            min-height: 3.5mm;
            align-items: center;
        }

        .meta-label {
            text-transform: uppercase;
        }

        .meta-colon {
            text-align: center;
        }

        .meta-value {
            min-height: 3.3mm;
            padding: 0 2px 1px;
            border-bottom: 1px solid #111;
            text-align: left;
        }


        /* Sections */
        .print-section {
            margin-bottom: 1.5mm;
        }


        /* Tables */
        .display-table,
        .answer-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            font-size: 8pt;
        }

        .display-table th,
        .display-table td,
        .answer-table th,
        .answer-table td {
            border: 1px solid #111;
            padding: 1.3mm 1.8mm;
        }


        /* Section heading */
        .section-title {
            background: #dbeafe;
            text-align: left;
            vertical-align: middle;
            font-size: 8.2pt;
            font-weight: 700;
            text-transform: uppercase;
            padding-top: 1.2mm !important;
            padding-bottom: 1.2mm !important;
        }


        /* Display fields */
        .display-table td {
            text-align: left;
            vertical-align: top !important;
            line-height: 1.15;
        }


        /* Answer label */
        .answer-label {
            width: 42%;
            background: #f8fafc;
            text-align: left;
            vertical-align: top !important;
            font-weight: 600;
            line-height: 1.15;
        }


        /* Answer value */
        .answer-value {
            width: 58%;
            text-align: left !important;
            vertical-align: top !important;
            font-weight: 400;
            line-height: 1.2;
            white-space: pre-line;
            overflow-wrap: break-word;
            word-break: normal;
        }

        .answer-value.long-answer {
            min-height: 0;
        }


        /* Keep rows compact */
        .answer-table tr,
        .display-table tr {
            height: auto;
        }

        .answer-table td,
        .display-table td {
            min-height: 0;
        }


        /* Signature */
        .signature-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20mm;
            width: 82%;
            margin: 2.5mm auto 0;
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .signature-box {
            font-size: 7.8pt;
            text-align: center;
        }

        .signature-title {
            margin: 0;
        }

        .signature-line {
            width: 62%;
            margin: 4mm auto 1.2mm;
            border-top: 1px solid #111;
        }

        .signature-name {
            margin: 0;
            font-size: 7.3pt;
        }


        /* Print */
        @page {
            size: A4 portrait;
            margin: 0;
        }

        @media print {

            html,
            body {
                width: 210mm;
                height: auto;
                margin: 0;
                padding: 0;
                background: white;
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }

            .toolbar {
                display: none !important;
            }

            .paper {
                width: 210mm;
                min-height: 297mm;
                height: auto;
                margin: 0;
                padding: 5mm 9mm;
                box-shadow: none;
            }

            /* Sections may continue to next page */
            .print-section {
                page-break-inside: auto;
                break-inside: auto;
            }

            /* Keep each row together where possible */
            .answer-table tr,
            .display-table tr {
                page-break-inside: avoid;
                break-inside: avoid;
            }

            /* Repeat section title if table continues */
            .display-table thead,
            .answer-table thead {
                display: table-header-group;
            }

            /* Keep signature block together */
            .signature-section {
                page-break-inside: avoid;
                break-inside: avoid;
            }

        }
    </style>

</head>


<body>

    {{-- Toolbar --}}
    <div class="toolbar">

        <div class="toolbar-title">
            {{ $response->observation_stage }} Observation Result
        </div>

        <div class="toolbar-actions">

            <a
                href="{{ route(
                    $role === 'observer'
                        ? 'observer.manage'
                        : 'external.manage',
                    $guru->gn_id
                ) }}"
                class="button secondary">

                Back

            </a>

            <button
                type="button"
                class="button"
                onclick="window.print()">

                Print / Save PDF

            </button>

        </div>

    </div>


    {{-- Printable page --}}
    <main class="paper">

        {{-- Header --}}
        <header class="report-header">

            <h1>
                PUSAT PENDIDIKAN AL AMIN BERHAD (PPAAB)
            </h1>

            <p>
                {{ strtoupper($form->form_name) }}
            </p>

        </header>


        {{-- Observation Information --}}
        <section class="meta">

            <div class="meta-row">

                <div class="meta-label">
                    Teacher Name
                </div>

                <div class="meta-colon">
                    :
                </div>

                <div class="meta-value">
                    {{ strtoupper($guru->gn_name) }}
                </div>

            </div>


            <div class="meta-row">

                <div class="meta-label">
                    Subject & Class
                </div>

                <div class="meta-colon">
                    :
                </div>

                <div class="meta-value">

                    {{ $response->subject_name ?? '-' }}
                    /
                    {{ $response->class_name ?? '-' }}

                </div>

            </div>


            <div class="meta-row">

                <div class="meta-label">
                    Date
                </div>

                <div class="meta-colon">
                    :
                </div>

                <div class="meta-value">

                    {{
                        $response->observation_date
                            ? \Carbon\Carbon::parse(
                                $response->observation_date
                            )->format('d/m/Y')
                            : '-'
                    }}

                </div>

            </div>


            <div class="meta-row">

                <div class="meta-label">
                    Time
                </div>

                <div class="meta-colon">
                    :
                </div>

                <div class="meta-value">

                    {{
                        $response->observation_time
                            ? \Carbon\Carbon::parse(
                                $response->observation_time
                            )->format('h:i A')
                            : '-'
                    }}

                </div>

            </div>


            <div class="meta-row">

                <div class="meta-label">

                    {{
                        $role === 'observer'
                            ? 'Observer'
                            : 'External Observer'
                    }}

                </div>

                <div class="meta-colon">
                    :
                </div>

                <div class="meta-value">

                    ( {{ strtoupper($evaluatorName ?? '-') }} )

                </div>

            </div>


            <div class="meta-row">

                <div class="meta-label">
                    School
                </div>

                <div class="meta-colon">
                    :
                </div>

                <div class="meta-value">

                    {{ $guru->school?->schoolName ?? '-' }}

                </div>

            </div>

        </section>


        {{-- Dynamic Sections --}}
        @foreach($form->sections as $section)

        @php

        $displayFields =
        $section->fields
        ->where('field_type', 'display')
        ->values();

        $inputFields =
        $section->fields
        ->where('field_type', '!=', 'display')
        ->values();

        @endphp


        {{-- Display Fields --}}
        @if($displayFields->isNotEmpty())

        <section class="print-section">

            <table class="display-table">

                <thead>

                    <tr>

                        <th
                            colspan="4"
                            class="section-title">

                            {{ $section->section_name }}

                        </th>

                    </tr>

                </thead>

                <tbody>

                    @foreach($displayFields->chunk(4) as $row)

                    <tr>

                        @foreach($row as $field)

                        <td>
                            {{ $field->field_label }}
                        </td>

                        @endforeach


                        @for($i = $row->count(); $i < 4; $i++)

                            <td>
                            </td>

                            @endfor

                    </tr>

                    @endforeach

                </tbody>

            </table>

        </section>

        @endif


        {{-- Input Fields --}}
        @if($inputFields->isNotEmpty())

        <section class="print-section">

            <table class="answer-table">

                <thead>

                    <tr>

                        <th
                            colspan="2"
                            class="section-title">

                            {{ $section->section_name }}

                        </th>

                    </tr>

                </thead>

                <tbody>

                    @foreach($inputFields as $field)

                    @php

                    $answer =
                    $existingAnswers[
                    $field->fieldID
                    ] ?? null;

                    $displayAnswer =
                    is_array($answer)
                    ? implode(', ', $answer)
                    : $answer;

                    @endphp


                    <tr>

                        <td class="answer-label">

                            {{ $field->field_label }}

                        </td>


                        <td
                            class="
                                            answer-value
                                            {{
                                                $field->field_type === 'textarea'
                                                    ? 'long-answer'
                                                    : ''
                                            }}
                                        ">

                            {{
                                            $displayAnswer !== null
                                            && $displayAnswer !== ''
                                                ? $displayAnswer
                                                : '-'
                                        }}

                        </td>

                    </tr>

                    @endforeach

                </tbody>

            </table>

        </section>

        @endif

        @endforeach


        {{-- Signature --}}
        <section class="signature-section">

            <div class="signature-box">

                <div class="signature-title">

                    {{
                        $role === 'observer'
                            ? 'Observer,'
                            : 'External Observer,'
                    }}

                </div>


                <div class="signature-line"></div>


                <div class="signature-name">

                    (
                    {{ strtoupper($evaluatorName ?? '-') }}
                    )

                </div>

            </div>


            <div class="signature-box">

                <div class="signature-title">
                    Teacher Observed,
                </div>


                <div class="signature-line"></div>


                <div class="signature-name">

                    (
                    {{ strtoupper($guru->gn_name) }}
                    )

                </div>

            </div>

        </section>

    </main>

</body>

</html>