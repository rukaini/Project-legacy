@php
    $assignedCourses = $assignedCourses ?? collect();
    $upcomingSessions = $upcomingSessions ?? collect();
    $recentProposals = $recentProposals ?? collect();
    $pendingProposals = $pendingProposals ?? 0;
    $trainerName = $trainerName ?? 'Trainer';
    $totalAssignedCourses = $totalAssignedCourses ?? 0;
    $teachingPlansSubmitted = $teachingPlansSubmitted ?? 0;

    $fmtTime = function ($start, $end, $fallback = null) {
        try {
            $s = $start ? \Carbon\Carbon::parse($start)->format('h:i A') : null;
            $e = $end ? \Carbon\Carbon::parse($end)->format('h:i A') : null;
        } catch (\Throwable $ex) { $s = $e = null; }
        if ($s && $e) return $s . ' - ' . $e;
        if ($s) return $s;
        if ($fallback) {
            try { return \Carbon\Carbon::parse($fallback)->format('h:i A'); } catch (\Throwable $ex) {}
        }
        return 'Time not set';
    };

    $fmtRange = function ($start, $end) {
        try {
            $s = $start ? \Carbon\Carbon::parse($start)->format('d M Y') : null;
            $e = $end ? \Carbon\Carbon::parse($end)->format('d M Y') : null;
        } catch (\Throwable $ex) { $s = $e = null; }
        if ($s && $e) return $s . ' – ' . $e;
        if ($s) return 'From ' . $s;
        return null;
    };

    $placeFor = function ($loc, $type) {
        $loc = trim((string) $loc);
        $t = strtolower((string) $type);
        $isUrl = str_starts_with($loc, 'http://') || str_starts_with($loc, 'https://');
        $isOnline = $isUrl || str_contains($t, 'online') || str_contains($t, 'virtual');
        if ($isOnline) {
            return ['icon' => 'bi-camera-video', 'label' => $isUrl ? 'Join meeting' : ($loc !== '' ? $loc : 'Online session'), 'url' => $isUrl ? $loc : null];
        }
        return ['icon' => 'bi-geo-alt', 'label' => $loc !== '' ? $loc : 'Location not set', 'url' => null];
    };

    $openUrlFor = function ($course) {
        $isLms = (($course->training_mode ?? 'standard') === 'lms');
        return $isLms
            ? route('trainer.courses.show', $course->course_id)
            : route('trainer.simple.overview', $course->course_id);
    };

    $proposalStatusClass = function ($status) {
        $s = strtolower(trim((string) $status));
        if (str_contains($s, 'approve')) return 'approved';
        if (str_contains($s, 'reject')) return 'rejected';
        if (str_contains($s, 'pending')) return 'pending';
        return 'other';
    };
@endphp

@extends('trainer.layouts.app')

@section('title', 'Trainer Dashboard | Al Amin Edu Oasis')

@push('styles')
<style>
    :root {
        --db-primary: #2563eb;
        --db-primary-dark: #1d4ed8;
        --db-primary-soft: #eff6ff;
        --db-indigo: #4338ca;
        --db-green: #059669;
        --db-green-soft: #ecfdf5;
        --db-amber: #d97706;
        --db-red: #dc2626;
        --db-slate-900: #0f172a;
        --db-slate-600: #475569;
        --db-slate-500: #64748b;
        --db-slate-400: #94a3b8;
        --db-border: #e2e8f0;
        --db-shadow: 0 6px 20px rgba(15, 23, 42, 0.05);
    }

    /* Matches every other page in the system */
    body { background: #f8fafc; }

    .db-wrap { color: var(--db-slate-900); }

    /* Header */
    .db-head {
        display: flex; align-items: flex-end; justify-content: space-between;
        gap: 16px; flex-wrap: wrap; margin-bottom: 22px;
    }
    .db-head > div { min-width: 0; }
    /* Same 26px / 850 / -0.035em as every other page title */
    .db-title { font-size: 26px; font-weight: 850; letter-spacing: -0.035em; margin: 0 0 5px; line-height: 1.18; }
    .db-subtitle { overflow-wrap: anywhere; font-size: 14px; font-weight: 600; color: var(--db-slate-500); margin: 0; }
    .db-head-btn {
        display: inline-flex; align-items: center; gap: 8px;
        background: var(--db-primary); color: #fff; border: 0;
        border-radius: 11px; padding: 11px 17px; font-size: 13px; font-weight: 850;
        text-decoration: none; transition: 0.15s ease; box-shadow: 0 6px 16px rgba(37, 99, 235, 0.18);
    }
    .db-head-btn:hover { background: var(--db-primary-dark); color: #fff; }

    /* Stat cards */
    .db-stat {
        background: #fff; border: 1px solid var(--db-border); border-radius: 16px;
        padding: 16px 17px; box-shadow: var(--db-shadow); height: 100%; transition: 0.15s ease;
    }
    .db-stat:hover { transform: translateY(-3px); border-color: #bfdbfe; box-shadow: 0 12px 24px rgba(15, 23, 42, 0.08); }
    .db-stat-icon {
        width: 40px; height: 40px; border-radius: 12px;
        display: flex; align-items: center; justify-content: center; font-size: 19px; margin-bottom: 12px;
    }
    .db-ic-blue  { background: var(--db-primary-soft); color: var(--db-primary); }
    .db-ic-green { background: var(--db-green-soft); color: var(--db-green); }
    .db-ic-amber { background: #fffbeb; color: var(--db-amber); }
    .db-stat-label {
        font-size: 11px; font-weight: 850; color: var(--db-slate-500);
        text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 5px;
    }
    .db-stat-value { font-size: 27px; font-weight: 900; line-height: 1; margin: 0; }
    .db-stat-note { margin-top: 5px; color: var(--db-slate-500); font-size: 10.5px; font-weight: 650; }
    .db-stat-row { display: flex; align-items: flex-start; justify-content: space-between; }

    /* Pending dot — now AMBER to match the rest of the system's pending convention
       (was red — only inconsistency in the whole pending visual language) */
    .db-dot {
        width: 10px; height: 10px; border-radius: 50%; background: var(--db-amber);
        display: inline-block; margin-top: 4px; box-shadow: 0 0 0 4px rgba(217, 119, 6, 0.14);
    }

    /* Next session (highlight) */
    .db-next {
        background: linear-gradient(135deg, #1e3a8a, #0f766e); color: #fff;
        border-radius: 16px; padding: 16px 17px; height: 100%;
        box-shadow: 0 8px 22px rgba(15, 23, 42, 0.16); transition: 0.15s ease;
    }
    .db-next:hover { transform: translateY(-3px); box-shadow: 0 14px 28px rgba(15, 23, 42, 0.22); }
    .db-next-label {
        font-size: 11px; font-weight: 850; text-transform: uppercase; letter-spacing: 0.06em;
        color: rgba(255, 255, 255, 0.82); margin-bottom: 9px;
    }
    .db-next-name { font-size: 15px; font-weight: 850; line-height: 1.3; margin-bottom: 7px; }
    .db-next-line {
        display: flex; align-items: center; gap: 7px;
        font-size: 12px; font-weight: 650; color: rgba(255, 255, 255, 0.9); margin-top: 4px;
    }
    .db-next-line a { color: #fff; text-decoration: underline; }
    .db-next-empty { font-size: 13px; font-weight: 650; color: rgba(255, 255, 255, 0.85); }

    /* Panels */
    .db-panel {
        background: #fff; border: 1px solid var(--db-border);
        border-radius: 16px; box-shadow: var(--db-shadow); overflow: hidden;
    }
    .db-panel-head {
        padding: 16px 20px; border-bottom: 1px solid #f1f5f9;
        display: flex; align-items: center; justify-content: space-between; gap: 12px;
    }
    .db-panel-title { font-size: 15px; font-weight: 850; margin: 0; }
    .db-panel-sub { font-size: 12px; font-weight: 600; color: var(--db-slate-500); margin-top: 2px; }
    .db-link {
        font-size: 12px; font-weight: 850; color: var(--db-primary);
        text-decoration: none; display: inline-flex; align-items: center; gap: 5px; white-space: nowrap;
    }
    .db-link:hover { color: var(--db-primary-dark); }
    .db-head-chip {
        font-size: 10.5px; font-weight: 850; color: var(--db-amber);
        background: #fffbeb; border: 1px solid #fde68a; border-radius: 999px; padding: 4px 10px; white-space: nowrap;
    }
    .db-panel-footer { padding: 14px 20px; border-top: 1px solid #f1f5f9; }
    .db-footer-btn {
        display: flex; align-items: center; justify-content: center; gap: 7px; width: 100%;
        background: var(--db-primary); color: #fff; border-radius: 11px; padding: 10px 14px;
        font-size: 13px; font-weight: 850; text-decoration: none; transition: 0.15s ease;
    }
    .db-footer-btn:hover { background: var(--db-primary-dark); color: #fff; }

    /* Table (centered columns, Training name stays left) */
    .db-table { width: 100%; margin: 0; font-size: 13.5px; border-collapse: collapse; }
    .db-table thead th {
        background: #f8fafc; color: var(--db-slate-500);
        font-size: 11px; font-weight: 850; text-transform: uppercase; letter-spacing: 0.05em;
        padding: 11px 18px; border-bottom: 1px solid var(--db-border); text-align: center; white-space: nowrap;
    }
    .db-table tbody td { padding: 14px 18px; vertical-align: middle; border-bottom: 1px solid #f1f5f9; text-align: center; }
    .db-table tbody tr:last-child td { border-bottom: 0; }
    .db-table tbody tr:hover { background: #fbfcfe; }
    .db-table th:first-child, .db-table td:first-child { text-align: left; width: 38%; }
    .db-course-name { font-weight: 800; color: var(--db-slate-900); }

    .db-mode {
        font-size: 10px; font-weight: 850; text-transform: uppercase; letter-spacing: 0.04em;
        border-radius: 999px; padding: 3px 9px; border: 1px solid; white-space: nowrap;
    }
    .db-mode.lms    { background: #eef2ff; color: var(--db-indigo); border-color: #c7d2fe; }
    .db-mode.simple { background: var(--db-primary-soft); color: var(--db-primary); border-color: #bfdbfe; }

    .db-enrol { display: flex; align-items: center; justify-content: center; gap: 9px; }
    .db-enrol-bar { width: 70px; height: 7px; background: #eef2f7; border-radius: 999px; overflow: hidden; }
    .db-enrol-fill { height: 100%; width: 0; background: linear-gradient(90deg, #2563eb, #06b6d4); border-radius: 999px; transition: width 0.4s ease; }
    .db-enrol-num { font-size: 12px; font-weight: 800; color: var(--db-slate-600); white-space: nowrap; }

    .db-status {
        font-size: 10px; font-weight: 850; text-transform: uppercase; letter-spacing: 0.04em;
        border-radius: 999px; padding: 4px 10px; border: 1px solid; white-space: nowrap;
    }
    .db-status.active { background: var(--db-green-soft); color: var(--db-green); border-color: #a7f3d0; }
    .db-status.other  { background: #f1f5f9; color: var(--db-slate-600); border-color: var(--db-border); }

    .db-open {
        display: inline-flex; align-items: center; gap: 5px;
        border: 1px solid #bfdbfe; background: #fff; color: var(--db-primary);
        border-radius: 9px; padding: 7px 13px; font-size: 12.5px; font-weight: 850;
        text-decoration: none; transition: 0.15s ease;
    }
    .db-open:hover { background: var(--db-primary-soft); color: var(--db-primary-dark); }

    /* Quick actions */
    .db-qa {
        display: block; height: 100%; background: #fff;
        border: 1px solid var(--db-border); border-radius: 13px; padding: 16px;
        text-decoration: none; color: var(--db-slate-900); transition: 0.15s ease;
    }
    .db-qa:hover { border-color: var(--db-primary); transform: translateY(-3px); box-shadow: 0 10px 20px rgba(37, 99, 235, 0.08); }
    .db-qa-icon {
        width: 40px; height: 40px; border-radius: 12px; background: var(--db-primary-soft); color: var(--db-primary);
        display: flex; align-items: center; justify-content: center; font-size: 18px; margin-bottom: 11px;
    }
    .db-qa-title { font-weight: 850; font-size: 14px; }
    .db-qa-sub { font-size: 12px; font-weight: 600; color: var(--db-slate-500); margin-top: 2px; }

    /* Schedule rows */
    .db-item { padding: 14px 20px; border-bottom: 1px solid #f1f5f9; }
    .db-item:last-child { border-bottom: 0; }
    .db-sched { display: flex; gap: 14px; align-items: flex-start; text-decoration: none; transition: 0.15s ease; }
    .db-sched:hover { background: #fbfcfe; }
    .db-date {
        width: 50px; flex-shrink: 0; text-align: center;
        border: 1px solid var(--db-border); border-radius: 11px; overflow: hidden; background: #fff;
    }
    .db-date-m { background: var(--db-primary); color: #fff; font-size: 10px; font-weight: 850; letter-spacing: 0.08em; padding: 3px 0; }
    .db-date-d { font-size: 19px; font-weight: 900; color: var(--db-slate-900); padding: 5px 0; }
    .db-sched-main { min-width: 0; flex: 1; }
    .db-sched-name { overflow-wrap: anywhere; font-size: 13.5px; font-weight: 800; color: var(--db-slate-900); }
    .db-sched-meta { font-size: 11.5px; font-weight: 650; color: var(--db-slate-500); margin-top: 3px; display: flex; flex-wrap: wrap; gap: 3px 12px; }
    .db-sched-meta span { display: inline-flex; align-items: center; gap: 5px; }
    .db-tag {
        display: inline-block; margin-top: 7px;
        font-size: 10px; font-weight: 850; text-transform: uppercase; letter-spacing: 0.04em;
        border-radius: 999px; padding: 3px 9px; border: 1px solid;
    }
    .db-tag.online   { background: #ecfeff; color: #0e7490; border-color: #a5f3fc; }
    .db-tag.physical { background: var(--db-primary-soft); color: var(--db-primary); border-color: #bfdbfe; }

    /* My Proposals rows */
    .db-prop { display: flex; align-items: flex-start; gap: 12px; }
    .db-prop-main { flex: 1; min-width: 0; }
    .db-prop-title { overflow-wrap: anywhere; font-size: 13.5px; font-weight: 800; color: var(--db-slate-900); line-height: 1.35; }
    .db-prop-detail {
        display: flex; flex-wrap: wrap; gap: 3px 14px; margin-top: 5px;
        font-size: 11.5px; font-weight: 650; color: var(--db-slate-500);
    }
    .db-prop-detail span { display: inline-flex; align-items: center; gap: 5px; }
    .db-prop-detail i { color: var(--db-slate-400); }
    .db-prop-status {
        font-size: 10px; font-weight: 850; text-transform: uppercase; letter-spacing: 0.04em;
        border-radius: 999px; padding: 4px 10px; border: 1px solid; white-space: nowrap; flex-shrink: 0;
    }
    .db-prop-status.pending  { background: #fffbeb; color: var(--db-amber); border-color: #fde68a; }
    .db-prop-status.approved { background: var(--db-green-soft); color: var(--db-green); border-color: #a7f3d0; }
    .db-prop-status.rejected { background: #fef2f2; color: var(--db-red); border-color: #fecaca; }
    .db-prop-status.other    { background: #f1f5f9; color: var(--db-slate-600); border-color: var(--db-border); }

    .db-empty { padding: 26px 20px; text-align: center; color: var(--db-slate-500); font-size: 13px; font-weight: 650; }
    .db-empty i { font-size: 26px; color: var(--db-slate-400); display: block; margin-bottom: 8px; }

    @media (max-width: 768px) {
        .db-title { font-size: 21px; }
        .db-head-btn { width: 100%; justify-content: center; }
    }
</style>
@endpush

@section('content')

<main class="db-wrap app-page-shell">

    {{-- Header --}}
    <div class="db-head">
        <div>
            <h1 class="db-title">Trainer Dashboard</h1>
            <p class="db-subtitle">Welcome back, {{ $trainerName }}. Here is your training overview.</p>
        </div>
        <a href="{{ route('trainer.courses') }}" class="db-head-btn">
            View My Training <i class="bi bi-arrow-right"></i>
        </a>
    </div>

    {{-- Stat cards --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <div class="db-stat">
                <div class="db-stat-icon db-ic-blue"><i class="bi bi-journal-bookmark"></i></div>
                <div class="db-stat-label">Assigned Training</div>
                <h3 class="db-stat-value">{{ $totalAssignedCourses }}</h3>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="db-stat">
                <div class="db-stat-icon db-ic-green"><i class="bi bi-file-earmark-check"></i></div>
                <div class="db-stat-label">Teaching Plans</div>
                <h3 class="db-stat-value">{{ $teachingPlansSubmitted }} / {{ $totalAssignedCourses }}</h3>
                <div class="db-stat-note">Submitted / assigned</div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="db-stat">
                <div class="db-stat-row">
                    <div>
                        <div class="db-stat-icon db-ic-amber"><i class="bi bi-clipboard-check"></i></div>
                        <div class="db-stat-label">Pending Review</div>
                        <h3 class="db-stat-value">{{ $pendingGrades }}</h3>
                    </div>
                    @if ($pendingGrades > 0)
                        <span class="db-dot" title="{{ $pendingGrades }} submission(s) awaiting your review"></span>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="db-next">
                <div class="db-next-label">Next Session</div>
                @if ($nextSession)
                    @php $np = $placeFor($nextSession->location, $nextSession->session_type); @endphp
                    <div class="db-next-name">{{ $nextSession->course_name }}</div>
                    <div class="db-next-line">
                        <i class="bi bi-calendar-event"></i>
                        {{ \Carbon\Carbon::parse($nextSession->date)->format('d M Y') }}
                        · {{ $fmtTime($nextSession->start_time, $nextSession->end_time, $nextSession->time) }}
                    </div>
                    <div class="db-next-line">
                        <i class="bi {{ $np['icon'] }}"></i>
                        @if ($np['url'])
                            <a href="{{ $np['url'] }}" target="_blank" rel="noopener">{{ $np['label'] }}</a>
                        @else
                            {{ $np['label'] }}
                        @endif
                    </div>
                @else
                    <div class="db-next-name">No upcoming session</div>
                    <div class="db-next-empty">Nothing scheduled right now.</div>
                @endif
            </div>
        </div>
    </div>

    <div class="row g-4">

        {{-- LEFT: Assigned training + quick actions --}}
        <div class="col-lg-8">

            <div class="db-panel mb-4">
                <div class="db-panel-head">
                    <div>
                        <h2 class="db-panel-title">Assigned Training</h2>
                        <div class="db-panel-sub">Trainings you run, with live session and enrolment counts.</div>
                    </div>
                    <a href="{{ route('trainer.courses') }}" class="db-link">View all <i class="bi bi-arrow-right"></i></a>
                </div>

                @if ($assignedCourses->count() > 0)
                    <div class="table-responsive">
                        <table class="db-table">
                            <thead>
                                <tr>
                                    <th>Training</th>
                                    <th>Mode</th>
                                    <th>Sessions</th>
                                    <th>Enrolment</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($assignedCourses as $course)
                                    @php
                                        $isLms = (($course->training_mode ?? 'standard') === 'lms');
                                        $cap = (int) ($course->capacity ?? 0);
                                        $enr = (int) ($course->total_participants ?? 0);
                                        $fill = $cap > 0 ? min(100, (int) round($enr / $cap * 100)) : 0;
                                        $isActive = strtolower($course->status ?? '') === 'active';
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="db-course-name">{{ $course->course_name }}</div>
                                        </td>
                                        <td>
                                            <span class="db-mode {{ $isLms ? 'lms' : 'simple' }}">{{ $isLms ? 'LMS' : 'Simple' }}</span>
                                        </td>
                                        <td><span class="db-enrol-num">{{ $course->total_sessions }}</span></td>
                                        <td>
                                            <div class="db-enrol">
                                                <div class="db-enrol-bar">
                                                    <div class="db-enrol-fill" data-fill="{{ $fill }}"></div>
                                                </div>
                                                <span class="db-enrol-num">{{ $enr }}@if ($cap > 0) / {{ $cap }}@endif</span>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="db-status {{ $isActive ? 'active' : 'other' }}">{{ $course->status ?: 'Unknown' }}</span>
                                        </td>
                                        <td>
                                            <a href="{{ $openUrlFor($course) }}" class="db-open">Open <i class="bi bi-arrow-right"></i></a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="db-empty">
                        <i class="bi bi-journal-x"></i>
                        No training has been assigned to you yet.
                    </div>
                @endif
            </div>

            <div class="db-panel">
                <div class="db-panel-head">
                    <div>
                        <h2 class="db-panel-title">Quick Actions</h2>
                        <div class="db-panel-sub">Common trainer tasks.</div>
                    </div>
                </div>
                <div class="p-3">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <a href="{{ route('trainer.courses') }}" class="db-qa">
                                <div class="db-qa-icon"><i class="bi bi-journal-bookmark"></i></div>
                                <div class="db-qa-title">Manage Training</div>
                                <div class="db-qa-sub">View trainings and participants.</div>
                            </a>
                        </div>
                        <div class="col-md-4">
                            <a href="{{ route('trainer.proposals') }}" class="db-qa">
                                <div class="db-qa-icon"><i class="bi bi-lightbulb"></i></div>
                                <div class="db-qa-title">Propose Training</div>
                                <div class="db-qa-sub">Submit a new training idea.</div>
                            </a>
                        </div>
                        <div class="col-md-4">
                            <a href="{{ route('trainer.profile') }}" class="db-qa">
                                <div class="db-qa-icon"><i class="bi bi-person-gear"></i></div>
                                <div class="db-qa-title">My Profile</div>
                                <div class="db-qa-sub">Update details and password.</div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        {{-- RIGHT: Upcoming sessions + my proposals --}}
        <div class="col-lg-4">

            <div class="db-panel mb-4">
                <div class="db-panel-head">
                    <h2 class="db-panel-title">Upcoming Sessions</h2>
                    <a href="{{ route('trainer.courses') }}" class="db-link">View all</a>
                </div>

                @forelse ($upcomingSessions as $session)
                    @php
                        $sp = $placeFor($session->location, $session->session_type);
                        $isOnline = str_contains(strtolower($session->session_type ?? ''), 'online')
                                 || str_contains(strtolower($session->session_type ?? ''), 'virtual')
                                 || $sp['url'] !== null;
                    @endphp
                    <a href="{{ $openUrlFor($session) }}" class="db-item db-sched">
                        <div class="db-date">
                            <div class="db-date-m">{{ \Carbon\Carbon::parse($session->date)->format('M') }}</div>
                            <div class="db-date-d">{{ \Carbon\Carbon::parse($session->date)->format('d') }}</div>
                        </div>
                        <div class="db-sched-main">
                            <div class="db-sched-name">{{ $session->course_name }}</div>
                            <div class="db-sched-meta">
                                <span><i class="bi bi-clock"></i> {{ $fmtTime($session->start_time, $session->end_time, $session->time) }}</span>
                                <span><i class="bi {{ $sp['icon'] }}"></i> {{ $sp['label'] }}</span>
                            </div>
                            <span class="db-tag {{ $isOnline ? 'online' : 'physical' }}">{{ $isOnline ? 'Online' : 'Physical' }}</span>
                        </div>
                    </a>
                @empty
                    <div class="db-empty">
                        <i class="bi bi-calendar-x"></i>
                        No upcoming sessions.
                    </div>
                @endforelse
            </div>

            <div class="db-panel">
                <div class="db-panel-head">
                    <h2 class="db-panel-title">My Proposals</h2>
                    @if ($pendingProposals > 0)
                        <span class="db-head-chip">{{ $pendingProposals }} pending</span>
                    @endif
                </div>

                @forelse ($recentProposals as $proposal)
                    @php $range = $fmtRange($proposal->proposed_start_date, $proposal->proposed_end_date); @endphp
                    <div class="db-item db-prop">
                        <div class="db-prop-main">
                            <div class="db-prop-title">{{ $proposal->proposed_title }}</div>
                            <div class="db-prop-detail">
                                @if ($range)
                                    <span><i class="bi bi-calendar-range"></i> {{ $range }}</span>
                                @endif
                                <span>
                                    <i class="bi bi-clock-history"></i>
                                    Submitted {{ $proposal->created_at ? \Carbon\Carbon::parse($proposal->created_at)->format('d M Y') : 'recently' }}
                                </span>
                            </div>
                        </div>
                        <span class="db-prop-status {{ $proposalStatusClass($proposal->status) }}">
                            {{ $proposal->status ?: 'Pending' }}
                        </span>
                    </div>
                @empty
                    <div class="db-empty">
                        <i class="bi bi-lightbulb"></i>
                        You haven't proposed any training yet.
                    </div>
                @endforelse

                <div class="db-panel-footer">
                    <a href="{{ route('trainer.proposals') }}" class="db-footer-btn">
                        <i class="bi bi-lightbulb"></i> Manage Proposals
                    </a>
                </div>
            </div>

        </div>
    </div>
</main>

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.db-enrol-fill').forEach(function (bar) {
            const fill = Number(bar.getAttribute('data-fill')) || 0;
            bar.style.width = fill + '%';
        });
    });
</script>
@endpush
