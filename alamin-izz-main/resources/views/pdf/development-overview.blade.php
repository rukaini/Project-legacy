<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>EduTrain — Teacher Development Overview</title>
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
        .tr-alt { background-color: #faf9ff; }
        .text-right { text-align: right; }
        .text-violet { color: #2563eb; font-weight: bold; }
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
    <h1>EduTrain — Teacher Development Overview</h1>
    <div class="tagline">Educator Training Management System · Al Amin Edu Oasis</div>

    <div class="meta">
        <strong>Prepared by:</strong> {{ $gb_name }}
        &nbsp;&nbsp;|&nbsp;&nbsp;
        <strong>Generated:</strong> {{ $exported_at->format('d M Y, H:i') }}
        &nbsp;&nbsp;|&nbsp;&nbsp;
        <strong>Teachers:</strong> {{ $overview->count() }}
    </div>

    <table class="report">
        <thead>
            <tr>
                <th style="width: 24%;">Teacher</th>
                <th style="width: 18%;">IC Number</th>
                <th class="text-right" style="width: 12%;">Completed</th>
                <th class="text-right" style="width: 10%;">Total</th>
                <th class="text-right" style="width: 16%;">Attendance Rate</th>
                <th class="text-right" style="width: 14%;">LADAP Hours</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($overview as $i => $row)
                <tr class="{{ $i % 2 === 1 ? 'tr-alt' : '' }}">
                    <td>
                        <strong>{{ $row['name'] }}</strong><br>
                        <span style="color:#6b7280;font-size:8px;">{{ $row['email'] }}</span>
                    </td>
                    <td style="font-family: monospace;">{{ $row['ic_number'] ?? '—' }}</td>
                    <td class="text-right">{{ $row['completed_trainings'] }}</td>
                    <td class="text-right">{{ $row['total_trainings'] }}</td>
                    <td class="text-right">
                        {{ $row['attendance_rate'] !== null ? $row['attendance_rate'] . '%' : '—' }}
                    </td>
                    <td class="text-right text-violet">{{ number_format($row['ladap_hours'], 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align:center;color:#9ca3af;padding:14px;">
                        No teacher data available.
                    </td>
                </tr>
            @endforelse

        </tbody>
    </table>

    <div class="footer">
        <strong>Note:</strong>
        LADAP hours are accumulated from verified attendance records only.
        Attendance rate is calculated as sessions attended divided by total sessions per training.
        This report covers teachers from the same school as the Guru Besar.
    </div>
</body>
</html>
