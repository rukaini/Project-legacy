<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>EduTrain — Training History</title>
    <style>
        @page { margin: 30px 36px; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #1f2937;
            margin: 0;
        }
        h1 {
            font-size: 18px;
            margin: 0 0 4px 0;
            color: #4c1d95;
        }
        .system-tagline {
            font-size: 10px;
            color: #6b7280;
            margin-bottom: 18px;
            border-bottom: 1px solid #d1d5db;
            padding-bottom: 10px;
        }
        .meta {
            width: 100%;
            margin-bottom: 18px;
        }
        .meta td {
            vertical-align: top;
            padding: 2px 0;
        }
        .meta .label {
            font-weight: bold;
            color: #374151;
            width: 90px;
        }
        .meta .right {
            text-align: right;
            color: #6b7280;
            font-size: 10px;
        }
        table.trainings {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }
        table.trainings th {
            background-color: #ede9fe;
            color: #4c1d95;
            text-align: left;
            font-size: 10px;
            padding: 6px 8px;
            border: 1px solid #c4b5fd;
        }
        table.trainings td {
            padding: 6px 8px;
            border: 1px solid #d1d5db;
            font-size: 10px;
            vertical-align: top;
        }
        table.trainings tr.empty td {
            text-align: center;
            color: #9ca3af;
            padding: 18px;
        }
        .total-row td {
            font-weight: bold;
            background-color: #f3f4f6;
        }
        .footer {
            margin-top: 24px;
            padding-top: 10px;
            border-top: 1px solid #d1d5db;
            font-size: 9px;
            color: #6b7280;
            font-style: italic;
            line-height: 1.5;
        }
        .footer strong {
            color: #374151;
            font-style: normal;
        }
        .status-pill {
            display: inline-block;
            font-size: 9px;
            font-weight: bold;
            padding: 1px 6px;
            border-radius: 8px;
        }
        .status-complete { background: #dcfce7; color: #166534; }
        .status-upcoming { background: #dbeafe; color: #1e40af; }
        .status-ongoing  { background: #fef3c7; color: #92400e; }
        h2.section-title {
            font-size: 13px;
            color: #4c1d95;
            margin: 0 0 8px 0;
        }
        table.tarbiah th {
            background-color: #ffe4e6;
            color: #9f1239;
            text-align: left;
            font-size: 10px;
            padding: 6px 8px;
            border: 1px solid #fda4af;
        }
        table.tarbiah td {
            padding: 6px 8px;
            border: 1px solid #d1d5db;
            font-size: 10px;
            vertical-align: top;
        }
        table.tarbiah tr.empty td {
            text-align: center;
            color: #9ca3af;
            padding: 18px;
        }
        .total-row.tarbiah td { background-color: #fff1f2; }
        table.penyelaras th {
            background-color: #f3e8ff;
            color: #6b21a8;
            text-align: left;
            font-size: 10px;
            padding: 6px 8px;
            border: 1px solid #d8b4fe;
        }
        table.penyelaras td {
            padding: 6px 8px;
            border: 1px solid #d1d5db;
            font-size: 10px;
            vertical-align: top;
        }
        table.penyelaras tr.empty td {
            text-align: center;
            color: #9ca3af;
            padding: 18px;
        }
        .total-row.penyelaras td { background-color: #faf5ff; }
        table.certificate th {
            background-color: #d1fae5;
            color: #065f46;
            text-align: left;
            font-size: 10px;
            padding: 6px 8px;
            border: 1px solid #6ee7b7;
        }
        table.certificate td {
            padding: 6px 8px;
            border: 1px solid #d1d5db;
            font-size: 10px;
            vertical-align: top;
        }
        table.certificate tr.empty td {
            text-align: center;
            color: #9ca3af;
            padding: 18px;
        }
        .total-row.certificate td { background-color: #ecfdf5; }
        table.summary {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table.summary td {
            padding: 8px 10px;
            border: 1px solid #d1d5db;
            font-size: 11px;
        }
        table.summary .summary-label { color: #374151; }
        table.summary .summary-value { text-align: right; font-weight: bold; }
        table.summary .grand-total td {
            background-color: #ede9fe;
            color: #4c1d95;
            font-weight: bold;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <h1>EduTrain — Training History ({{ $year }})</h1>
    <div class="system-tagline">
        Educator Training Management System · Al Amin Edu Oasis
    </div>

    <table class="meta">
        <tr>
            <td class="label">Name:</td>
            <td>{{ $user->name }}</td>
            <td class="right">Exported: {{ $exported_at->format('d M Y, H:i') }}</td>
        </tr>
        <tr>
            <td class="label">Email:</td>
            <td>{{ $user->email }}</td>
            <td class="right">Role: {{ str_replace('_', ' ', ucwords($user->getRoleNames()->first() ?? '—', '_')) }}</td>
        </tr>
    </table>

    <h2 class="section-title">Training History</h2>
    <table class="trainings">
        <thead>
            <tr>
                <th style="width: 30%;">Training</th>
                <th style="width: 14%;">Organiser</th>
                <th style="width: 10%;">Status</th>
                <th style="width: 18%;">Attendance</th>
                <th style="width: 14%; text-align: right;">Total Hrs</th>
                <th style="width: 14%; text-align: right;">LADAP Hours</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>
                        <strong>{{ $row['title'] }}</strong><br>
                        <span style="color: #6b7280; font-size: 9px;">
                            {{ ucwords(str_replace('_', ' ', $row['course_category'] ?? '—')) }}
                        </span>
                    </td>
                    <td>{{ $row['organiser_name'] ?? '—' }}</td>
                    <td>
                        <span class="status-pill status-{{ $row['status'] }}">
                            {{ ucfirst($row['status']) }}
                        </span>
                    </td>
                    <td>
                        @if (($row['total_sessions'] ?? 0) > 0)
                            {{ $row['present_count'] }} / {{ $row['total_sessions'] }}
                            ({{ $row['attendance_rate'] ?? 0 }}%)
                        @else
                            —
                        @endif
                    </td>
                    <td style="text-align: right;">
                        {{ number_format($row['total_session_hours'] ?? 0, 2) }}
                    </td>
                    <td style="text-align: right;">
                        {{ number_format($row['ladap_hours'], 2) }}
                    </td>
                </tr>
            @empty
                <tr class="empty">
                    <td colspan="6">No training history found.</td>
                </tr>
            @endforelse

            @if ($rows->count() > 0)
                <tr class="total-row">
                    <td colspan="5" style="text-align: right;">Total Training LADAP Hours</td>
                    <td style="text-align: right;">{{ number_format($total_ladap_training, 2) }}</td>
                </tr>
            @endif
        </tbody>
    </table>

    <h2 class="section-title">LADAP History</h2>
    <table class="penyelaras">
        <thead>
            <tr>
                <th style="width: 34%;">Session</th>
                <th style="width: 22%;">Location</th>
                <th style="width: 16%;">Status</th>
                <th style="width: 14%; text-align: right;">Total Hrs</th>
                <th style="width: 14%; text-align: right;">LADAP Hours</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($penyelaras_ladap_rows as $p)
                <tr>
                    <td><strong>{{ $p['title'] }}</strong></td>
                    <td>{{ $p['mode'] ?? '—' }}</td>
                    <td>
                        <span class="status-pill status-{{ $p['status'] }}">
                            {{ ucfirst($p['status']) }}
                        </span>
                    </td>
                    <td style="text-align: right;">{{ number_format($p['total_session_hours'], 2) }}</td>
                    <td style="text-align: right;">{{ number_format($p['ladap_hours'], 2) }}</td>
                </tr>
            @empty
                <tr class="empty">
                    <td colspan="5">No LADAP history found.</td>
                </tr>
            @endforelse

            @if ($penyelaras_ladap_rows->count() > 0)
                <tr class="total-row penyelaras">
                    <td colspan="4" style="text-align: right;">Total LADAP Hours</td>
                    <td style="text-align: right;">{{ number_format($total_ladap_penyelaras, 2) }}</td>
                </tr>
            @endif
        </tbody>
    </table>

    <h2 class="section-title">Certificate History</h2>
    <table class="certificate">
        <thead>
            <tr>
                <th style="width: 34%;">Certificate</th>
                <th style="width: 22%;">Location</th>
                <th style="width: 16%;">Date</th>
                <th style="width: 14%; text-align: right;">Total Hrs</th>
                <th style="width: 14%; text-align: right;">Credit Hours</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($certificate_rows as $c)
                <tr>
                    <td><strong>{{ $c['title'] }}</strong></td>
                    <td>{{ $c['mode'] ?? '—' }}</td>
                    <td>{{ \Carbon\Carbon::parse($c['date'])->format('d M Y') }}</td>
                    <td style="text-align: right;">{{ number_format($c['total_session_hours'], 2) }}</td>
                    <td style="text-align: right;">{{ number_format($c['ladap_hours'], 2) }}</td>
                </tr>
            @empty
                <tr class="empty">
                    <td colspan="5">No approved certificates found.</td>
                </tr>
            @endforelse

            @if ($certificate_rows->count() > 0)
                <tr class="total-row certificate">
                    <td colspan="4" style="text-align: right;">Total Certificate LADAP Hours</td>
                    <td style="text-align: right;">{{ number_format($total_ladap_certificate, 2) }}</td>
                </tr>
            @endif
        </tbody>
    </table>

    <h2 class="section-title">Tarbiah History</h2>
    <table class="tarbiah">
        <thead>
            <tr>
                <th style="width: 34%;">Tarbiah</th>
                <th style="width: 22%;">Location</th>
                <th style="width: 16%;">Date</th>
                <th style="width: 14%;">Time</th>
                <th style="width: 14%; text-align: right;">LADAP Hours</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($tarbiah_rows as $t)
                <tr>
                    <td><strong>{{ $t['title'] }}</strong></td>
                    <td>{{ $t['location'] ?? '—' }}</td>
                    <td>{{ \Carbon\Carbon::parse($t['session_date'])->format('d M Y') }}</td>
                    <td>{{ \Carbon\Carbon::parse($t['start_time'])->format('H:i') }} - {{ \Carbon\Carbon::parse($t['end_time'])->format('H:i') }}</td>
                    <td style="text-align: right;">{{ number_format($t['ladap_hours'], 2) }}</td>
                </tr>
            @empty
                <tr class="empty">
                    <td colspan="5">No Tarbiah history found.</td>
                </tr>
            @endforelse

            @if ($tarbiah_rows->count() > 0)
                <tr class="total-row tarbiah">
                    <td colspan="4" style="text-align: right;">Total Tarbiah LADAP Hours</td>
                    <td style="text-align: right;">{{ number_format($total_ladap_tarbiah, 2) }}</td>
                </tr>
            @endif
        </tbody>
    </table>

    <table class="summary">
        <tr>
            <td class="summary-label" style="width: 70%;">Training LADAP Hours</td>
            <td class="summary-value">{{ number_format($total_ladap_training, 2) }}</td>
        </tr>
        <tr>
            <td class="summary-label">LADAP Hours</td>
            <td class="summary-value">{{ number_format($total_ladap_penyelaras, 2) }}</td>
        </tr>
        <tr>
            <td class="summary-label">Certificate LADAP Hours</td>
            <td class="summary-value">{{ number_format($total_ladap_certificate, 2) }}</td>
        </tr>
        <tr>
            <td class="summary-label">Tarbiah LADAP Hours</td>
            <td class="summary-value">{{ number_format($total_ladap_tarbiah, 2) }}</td>
        </tr>
        <tr class="grand-total">
            <td>Combined Total LADAP Hours</td>
            <td class="summary-value">{{ number_format($total_ladap, 2) }}</td>
        </tr>
    </table>

    <div class="footer">
        <strong>Disclaimer:</strong>
        This report reflects records held in EduTrain only. LADAP hours are calculated
        based on verified attendance records and the applicable multiplier for each training.
        Official LADAP hour totals are maintained by the institution and may differ.
        For any discrepancies, please contact the Guru Besar's office.
    </div>
</body>
</html>
