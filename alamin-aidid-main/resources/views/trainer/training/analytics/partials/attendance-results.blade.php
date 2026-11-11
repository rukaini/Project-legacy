@if ((string) $selectedSessionId !== '')
    <div class="ana-table-divider">
        <div class="ana-table-title">Participant Attendance</div>
        <div class="ana-table-wrap">
            <table class="ana-table" data-noun="participants">
                <thead>
                    <tr>
                        <th>Participant</th>
                        <th>Type</th>
                        <th>Attendance</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($participantAttendance as $participant)
                        @php($attendanceClass = str_starts_with(strtolower($participant->attendance_status), 'recorded') ? 'recorded' : 'not-recorded')
                        <tr>
                            <td>{{ $participant->participant_name }}</td>
                            <td>{{ $participant->participant_type }}</td>
                            <td><span class="ana-attendance-badge {{ $attendanceClass }}">{{ $participant->attendance_status }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="ana-inline-empty">No participants are enrolled in this training.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@else
    <div class="ana-table-divider">
        <div class="ana-table-title">Session results</div>
        <div class="ana-table-wrap">
            <table class="ana-table" data-noun="sessions">
                <thead><tr><th>Session</th><th>Date</th><th>Participants</th><th>Record coverage</th><th>Recorded</th><th>Not recorded</th></tr></thead>
                <tbody>
                    @forelse($sessionResults as $session)
                        <tr><td>{{ $session->session_title ?: 'Session '.$session->session_id }}</td><td>{{ $session->date_label ?: 'Not scheduled' }}</td><td>{{ $session->participant_count }}</td><td>{{ $session->attendance_summary['available'] ? $session->attendance_summary['rate'].'%' : '—' }}</td><td>{{ $session->attendance_summary['counts']['Recorded'] }}</td><td>{{ $session->attendance_summary['counts']['Not Recorded'] }}</td></tr>
                    @empty
                        <tr><td colspan="6" class="ana-inline-empty">No sessions are available for this selection.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endif
