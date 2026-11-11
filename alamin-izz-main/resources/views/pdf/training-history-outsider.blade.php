<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>EduTrain — Training History (Outsider)</title>
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
            color: #0369a1;
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
            background-color: #e0f2fe;
            color: #0369a1;
            text-align: left;
            font-size: 10px;
            padding: 6px 8px;
            border: 1px solid #7dd3fc;
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
    </style>
</head>
<body>
    <h1>EduTrain — Training History ({{ $year }})</h1>
    <div class="system-tagline">
        Educator Training Management System · Al Amin Edu Oasis · Outsider Participant Report
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
            <td class="right">Role: Outsider Participant</td>
        </tr>
    </table>

    <table class="trainings">
        <thead>
            <tr>
                <th style="width: 32%;">Training</th>
                <th style="width: 18%;">Organiser</th>
                <th style="width: 10%;">Category</th>
                <th style="width: 10%;">Status</th>
                <th style="width: 16%;">Attendance</th>
                <th style="width: 14%; text-align: right;">Total Hours</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td><strong>{{ $row['title'] }}</strong></td>
                    <td>{{ $row['organiser_name'] ?? '—' }}</td>
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
                    <td style="text-align: right;">
                        {{ number_format($row['total_session_hours'] ?? 0, 2) }}
                    </td>
                </tr>
            @empty
                <tr class="empty">
                    <td colspan="6">No training history found.</td>
                </tr>
            @endforelse

            @if ($rows->count() > 0)
                <tr class="total-row">
                    <td colspan="5" style="text-align: right;">Total Training Hours</td>
                    <td style="text-align: right;">{{ number_format($total_hours, 2) }}</td>
                </tr>
            @endif
        </tbody>
    </table>

    <div class="footer">
        <strong>Note:</strong>
        This report reflects training participation records held in EduTrain for outsider participants.
        Attendance hours are based on verified QR scan records only.
        For official certification or verification purposes, please contact the training organiser directly.
    </div>
</body>
</html>
