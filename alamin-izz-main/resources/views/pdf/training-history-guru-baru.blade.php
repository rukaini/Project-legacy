<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>EduTrain — Training History (Guru Baru)</title>
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
            color: #4338ca;
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
            background-color: #e0e7ff;
            color: #4338ca;
            text-align: left;
            font-size: 10px;
            padding: 6px 8px;
            border: 1px solid #a5b4fc;
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
        .summary-box {
            width: 100%;
            margin-bottom: 16px;
            border-collapse: collapse;
        }
        .summary-box td {
            padding: 8px 12px;
            border: 1px solid #a5b4fc;
            background: #f5f3ff;
            font-size: 10px;
            text-align: center;
        }
        .summary-box .s-label {
            font-size: 9px;
            color: #6b7280;
            display: block;
        }
        .summary-box .s-value {
            font-size: 15px;
            font-weight: bold;
            color: #4338ca;
        }
    </style>
</head>
<body>
    <h1>EduTrain — Training History ({{ $year }})</h1>
    <div class="system-tagline">
        Educator Training Management System · Al Amin Edu Oasis · Guru Baru Development Report
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
            <td class="right">Role: Guru Baru</td>
        </tr>
    </table>

    {{-- Summary strip --}}
    <table class="summary-box">
        <tr>
            <td>
                <span class="s-label">Total Trainings</span>
                <span class="s-value">{{ $rows->count() }}</span>
            </td>
            <td>
                <span class="s-label">Completed</span>
                <span class="s-value">{{ $rows->where('status', 'complete')->count() }}</span>
            </td>
            <td>
                <span class="s-label">Total Hours</span>
                <span class="s-value">{{ number_format($total_hours, 1) }}</span>
            </td>
            <td>
                <span class="s-label">Total LADAP Hours</span>
                <span class="s-value">{{ number_format($total_ladap, 2) }}</span>
            </td>
        </tr>
    </table>

    <table class="trainings">
        <thead>
            <tr>
                <th style="width: 26%;">Training</th>
                <th style="width: 10%;">Category</th>
                <th style="width: 9%;">Status</th>
                <th style="width: 13%;">Attendance</th>
                <th style="width: 10%; text-align: center;">Tasks</th>
                <th style="width: 10%; text-align: center;">Quiz Avg</th>
                <th style="width: 11%; text-align: right;">Total Hrs</th>
                <th style="width: 11%; text-align: right;">LADAP Hrs</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>
                        <strong>{{ $row['title'] }}</strong><br>
                        <span style="color:#6b7280; font-size:9px;">{{ $row['organiser_name'] ?? '—' }}</span>
                    </td>
                    <td>{{ ucwords(str_replace('_', ' ', $row['course_category'] ?? '—')) }}</td>
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
                    <td style="text-align: center;">
                        @if (($row['course_category'] ?? '') === 'series')
                            {{ $row['submitted_count'] ?? 0 }} / {{ $row['total_tasks'] ?? 0 }}
                        @else
                            —
                        @endif
                    </td>
                    <td style="text-align: center;">
                        @if (($row['course_category'] ?? '') === 'series' && ($row['average_quiz_score'] ?? null) !== null)
                            {{ $row['average_quiz_score'] }}%
                        @else
                            —
                        @endif
                    </td>
                    <td style="text-align: right;">
                        {{ number_format($row['total_session_hours'] ?? 0, 2) }}
                    </td>
                    <td style="text-align: right; font-weight: bold; color: #4338ca;">
                        {{ number_format($row['ladap_hours'] ?? 0, 2) }}
                    </td>
                </tr>
            @empty
                <tr class="empty">
                    <td colspan="8">No training history found.</td>
                </tr>
            @endforelse

            @if ($rows->count() > 0)
                <tr class="total-row">
                    <td colspan="6" style="text-align: right;">Total</td>
                    <td style="text-align: right;">{{ number_format($total_hours, 2) }}</td>
                    <td style="text-align: right; color: #4338ca;">{{ number_format($total_ladap, 2) }}</td>
                </tr>
            @endif
        </tbody>
    </table>

    <div class="footer">
        <strong>Disclaimer:</strong>
        This report reflects training participation and assessment records held in EduTrain for Guru Baru participants.
        LADAP hours are calculated based on verified attendance and the applicable multiplier for each training.
        For any discrepancies, please contact the HR officer or Guru Besar's office.
    </div>
</body>
</html>
