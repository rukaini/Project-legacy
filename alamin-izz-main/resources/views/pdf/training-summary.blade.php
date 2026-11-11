<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>EduTrain — Training Summary Report</title>
    <style>
        @page { margin: 30px 36px; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #1f2937;
            margin: 0;
        }
        h1 {
            font-size: 17px;
            margin: 0 0 4px 0;
            color: #4c1d95;
        }
        .tagline {
            font-size: 9px;
            color: #6b7280;
            margin-bottom: 14px;
            padding-bottom: 10px;
            border-bottom: 1px solid #d1d5db;
        }
        .meta {
            margin-bottom: 14px;
            font-size: 10px;
            color: #374151;
        }
        table.report {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }
        table.report th {
            background-color: #ede9fe;
            color: #4c1d95;
            text-align: left;
            font-size: 9px;
            padding: 5px 7px;
            border: 1px solid #c4b5fd;
        }
        table.report td {
            padding: 5px 7px;
            border: 1px solid #d1d5db;
            font-size: 9px;
            vertical-align: top;
        }
        .total-row td {
            font-weight: bold;
            background-color: #f3f4f6;
        }
        .status-completed { color: #166534; }
        .status-open      { color: #1e40af; }
        .status-closed    { color: #991b1b; }
        .footer {
            margin-top: 20px;
            padding-top: 8px;
            border-top: 1px solid #d1d5db;
            font-size: 8px;
            color: #6b7280;
            font-style: italic;
        }
        .footer strong { color: #374151; font-style: normal; }
    </style>
</head>
<body>
    <h1>EduTrain — Training Summary Report</h1>
    <div class="tagline">Educator Training Management System · Al Amin Edu Oasis</div>

    <div class="meta">
        <strong>Period:</strong>
        {{ $from ? $from->format('d M Y') : 'All time' }}
        –
        {{ $to ? $to->format('d M Y') : 'All time' }}
        &nbsp;&nbsp;|&nbsp;&nbsp;
        <strong>Generated:</strong> {{ $exported_at->format('d M Y, H:i') }}
    </div>

    <table class="report">
        <thead>
            <tr>
                <th style="width: 22%;">Training</th>
                <th style="width: 9%;">Date</th>
                <th style="width: 8%;">Status</th>
                <th style="width: 8%;">Enrolled</th>
                <th style="width: 9%;">Attended</th>
                <th style="width: 10%;">Attend. %</th>
                <th style="width: 10%;">Task Comp. %</th>
                <th style="width: 10%;">Avg Feedback</th>
                <th style="width: 6%;">Responses</th>
                <th style="width: 8%;">LADAP Hrs</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td><strong>{{ $row['title'] }}</strong></td>
                    <td>{{ \Illuminate\Support\Carbon::parse($row['date'])->format('d M Y') }}</td>
                    <td class="status-{{ $row['status'] }}">{{ ucfirst($row['status']) }}</td>
                    <td style="text-align: right;">{{ $row['enrolled'] }}</td>
                    <td style="text-align: right;">{{ $row['attended'] }}</td>
                    <td style="text-align: right;">{{ $row['attendance_rate'] !== null ? $row['attendance_rate'] . '%' : '—' }}</td>
                    <td style="text-align: right;">{{ $row['task_completion_rate'] !== null ? $row['task_completion_rate'] . '%' : '—' }}</td>
                    <td style="text-align: right;">{{ $row['avg_feedback'] !== null ? number_format($row['avg_feedback'], 1) . ' ★' : '—' }}</td>
                    <td style="text-align: right;">{{ $row['feedback_count'] }}</td>
                    <td style="text-align: right;">{{ number_format($row['ladap_hours'], 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" style="text-align: center; color: #9ca3af; padding: 14px;">
                        No trainings found in the selected date range.
                    </td>
                </tr>
            @endforelse

            @if ($rows->count() > 0)
                <tr class="total-row">
                    <td colspan="3">Totals</td>
                    <td style="text-align: right;">{{ $rows->sum('enrolled') }}</td>
                    <td style="text-align: right;">{{ $rows->sum('attended') }}</td>
                    <td colspan="3"></td>
                    <td style="text-align: right;">{{ $rows->sum('feedback_count') }}</td>
                    <td style="text-align: right;">{{ number_format($rows->sum('ladap_hours'), 2) }}</td>
                </tr>
            @endif
        </tbody>
    </table>

    <div class="footer">
        <strong>Note:</strong>
        This report is generated from EduTrain records only. Attendance percentage is calculated
        as present records divided by (enrolled participants × total sessions). LADAP hours reflect
        the configured duration of each training programme.
    </div>
</body>
</html>
