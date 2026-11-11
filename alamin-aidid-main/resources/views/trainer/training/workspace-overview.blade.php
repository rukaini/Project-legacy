@php
    $pendingSubmissions = $pendingSubmissions ?? 0;
    $totalParticipants = $totalParticipants ?? 0;
    $maleCount = $maleCount ?? 0;
    $femaleCount = $femaleCount ?? 0;
    $materials = $materials ?? collect();
    $moduleCount = $moduleCount ?? 0;
    $contentBlockCount = $contentBlockCount ?? 0;
    $assessmentCount = $assessmentCount ?? 0;
    $sessionCount = $sessionCount ?? 0;
    $recentWorkspaceLogs = $recentWorkspaceLogs ?? collect();
    $trainingTeam = $trainingTeam ?? collect();

    $genderTotal = $maleCount + $femaleCount;

    $hasMaterial    = $materials->count() > 0 || $contentBlockCount > 0;
    $hasAssessment  = $assessmentCount > 0;
    $hasSession     = $sessionCount > 0;
    $hasParticipants = $totalParticipants > 0;
    $readyCount = ($hasParticipants ? 1 : 0) + ($hasMaterial ? 1 : 0) + ($hasAssessment ? 1 : 0) + ($hasSession ? 1 : 0);
    $allReady = $readyCount === 4;
@endphp

@extends('trainer.layouts.app')

@section('title', $course->course_name . ' | Training Workspace')

@push('styles')
<style>
    :root {
        --lw-primary: #2563eb;
        --lw-primary-dark: #1d4ed8;
        --lw-primary-soft: #eff6ff;
        --lw-indigo: #4338ca;
        --lw-green: #059669;
        --lw-green-soft: #ecfdf5;
        --lw-amber: #d97706;
        --lw-amber-soft: #fffbeb;
        --lw-pink: #db2777;
        --lw-red: #dc2626;
        --lw-slate-900: #0f172a;
        --lw-slate-700: #334155;
        --lw-slate-600: #475569;
        --lw-slate-500: #64748b;
        --lw-slate-400: #94a3b8;
        --lw-border: #e2e8f0;
        --lw-shadow: 0 6px 20px rgba(15, 23, 42, 0.05);
    }

    body { background: #f6f8fb; }
    .lw-shell { color: var(--lw-slate-900); }

    /* Header */
    .lw-header { background: #fff; border-bottom: 1px solid var(--lw-border); }
    .lw-page { padding-top: 24px; padding-bottom: 46px; }

    .lw-breadcrumb {
        display: flex; align-items: center; gap: 8px; flex-wrap: wrap;
        font-size: 11px; font-weight: 850; color: var(--lw-slate-400);
        text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 15px;
    }
    .lw-breadcrumb a { color: var(--lw-slate-500); text-decoration: none; }
    .lw-breadcrumb a:hover { color: var(--lw-primary); }
    .lw-breadcrumb .lw-bc-current { color: var(--lw-slate-700); }

    .lw-title-row { display: flex; align-items: flex-start; gap: 16px; }

    .lw-icon {
        width: 58px; height: 58px; border-radius: 16px; flex-shrink: 0;
        background-image: radial-gradient(#93c5fd 1px, transparent 1px);
        background-size: 10px 10px;
        background-color: #eff6ff;
        border: 1px solid #bfdbfe;
        color: var(--lw-primary);
        display: flex; align-items: center; justify-content: center; font-size: 23px;
    }

    .lw-title {
        font-size: 26px; font-weight: 850; letter-spacing: -0.035em;
        color: var(--lw-slate-900); margin: 0 0 9px; line-height: 1.18;
    }

    .lw-meta { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
    .lw-pill {
        display: inline-flex; align-items: center; gap: 6px;
        background: #f1f5f9; color: var(--lw-slate-600); border: 1px solid var(--lw-border);
        border-radius: 999px; padding: 5px 11px; font-size: 12px; font-weight: 750;
    }
    .lw-pill i { color: var(--lw-slate-500); }
    .lw-pill.mode {
        background: #eef2ff; color: var(--lw-indigo); border-color: #c7d2fe;
        font-weight: 850; letter-spacing: 0.04em; text-transform: uppercase; font-size: 11px;
    }
    .lw-pill.mode i { color: var(--lw-indigo); }
    .lw-pill.status-active { background: var(--lw-green-soft); color: var(--lw-green); border-color: #a7f3d0; }
    .lw-pill.status-active i { color: var(--lw-green); }

    /* Tabs */
    .lw-tabs { display: flex; gap: 24px; margin-top: 22px; overflow-x: auto; }
    .lw-tabs a {
        position: relative;
        color: var(--lw-slate-500); text-decoration: none; font-size: 14px; font-weight: 650;
        padding-bottom: 13px; border-bottom: 2.5px solid transparent; white-space: nowrap;
        display: inline-flex; align-items: center; gap: 6px; transition: 0.15s ease;
    }
    .lw-tabs a:hover { color: var(--lw-slate-900); }
    .lw-tabs a.active { color: var(--lw-primary); border-bottom-color: var(--lw-primary); font-weight: 800; }

    .lw-tab-badge {
        display: inline-flex; align-items: center; justify-content: center;
        min-width: 22px; height: 19px; padding: 0 7px;
        background: var(--lw-amber-soft); color: var(--lw-amber);
        border: 1px solid #fde68a;
        border-radius: 999px; font-size: 10.5px; font-weight: 900;
    }

    /* Snapshot cards */
    .lw-stat {
        background: #fff; border: 1px solid var(--lw-border); border-radius: 15px;
        padding: 16px 17px; box-shadow: var(--lw-shadow); height: 100%;
        position: relative; overflow: hidden; transition: 0.15s ease; cursor: default;
    }
    .lw-stat:hover {
        transform: translateY(-3px); border-color: #bfdbfe;
        box-shadow: 0 12px 24px rgba(15, 23, 42, 0.08);
    }

    .lw-stat-icon {
        width: 40px; height: 40px; border-radius: 12px;
        display: flex; align-items: center; justify-content: center; font-size: 18px; margin-bottom: 11px;
    }
    .lw-ic-blue  { background: var(--lw-primary-soft); color: var(--lw-primary); }
    .lw-ic-indigo{ background: #eef2ff; color: var(--lw-indigo); }
    .lw-ic-green { background: var(--lw-green-soft); color: var(--lw-green); }
    .lw-ic-amber { background: var(--lw-amber-soft); color: var(--lw-amber); }

    .lw-stat-label {
        font-size: 11px; font-weight: 850; color: var(--lw-slate-500);
        text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 4px;
    }
    .lw-stat-value { font-size: 25px; font-weight: 900; line-height: 1; color: var(--lw-slate-900); }
    .lw-stat-note { font-size: 11.5px; font-weight: 650; color: var(--lw-slate-500); margin-top: 6px; }
    .lw-stat-row { display: flex; align-items: flex-start; justify-content: space-between; }
    .lw-stat-dot {
        width: 9px; height: 9px; border-radius: 50%; background: var(--lw-amber);
        display: inline-block; margin-top: 4px;
    }

    /* Gender split bar (inside Participants snapshot) */
    .lw-gender {
        margin-top: 9px;
    }
    .lw-gender-bar {
        height: 6px; border-radius: 999px; background: #f1f5f9;
        display: flex; overflow: hidden;
    }
    .lw-gender-seg { height: 100%; width: 0; transition: width 0.4s ease; }
    .lw-gender-seg.male   { background: var(--lw-primary); }
    .lw-gender-seg.female { background: var(--lw-pink); }
    .lw-gender-legend {
        display: flex; gap: 12px; margin-top: 7px;
    }
    .lw-gender-legend span {
        display: inline-flex; align-items: center; gap: 4px;
        font-size: 11.5px; font-weight: 800; color: var(--lw-slate-600);
    }
    .lw-gender-legend .m i { color: var(--lw-primary); }
    .lw-gender-legend .f i { color: var(--lw-pink); }
    .lw-gender-empty {
        font-size: 11.5px; font-weight: 650; color: var(--lw-slate-500);
    }

    /* Panels */
    .lw-panel {
        background: #fff; border: 1px solid var(--lw-border);
        border-radius: 16px; box-shadow: var(--lw-shadow); overflow: hidden;
    }
    .lw-panel-head {
        padding: 16px 20px; border-bottom: 1px solid #f1f5f9;
        display: flex; align-items: center; justify-content: space-between; gap: 12px;
    }
    .lw-panel-title { font-size: 15px; font-weight: 850; margin: 0; color: var(--lw-slate-900); }
    .lw-panel-sub { font-size: 12px; font-weight: 600; color: var(--lw-slate-500); margin-top: 2px; }

    .lw-overview-body { padding: 18px 20px; }
    .lw-overview-text { color: var(--lw-slate-600); font-size: 14px; line-height: 1.65; margin: 0; }
    .lw-overview-text.empty { color: var(--lw-slate-400); font-style: italic; }

    /* Key points list */
    .lw-points {
        list-style: none; padding: 0;
        margin: 16px 0 0;
        display: flex; flex-direction: column; gap: 11px;
    }
    .lw-points.no-desc { margin-top: 0; }
    .lw-points li {
        display: flex; align-items: center; gap: 11px;
        font-size: 13.5px; font-weight: 650; color: var(--lw-slate-600);
        line-height: 1.45;
    }
    .lw-points li i {
        color: var(--lw-primary); font-size: 15px;
        width: 20px; text-align: center; flex-shrink: 0;
    }
    .lw-points li b { color: var(--lw-slate-900); font-weight: 900; }

    /* Activity list */
    .lw-activity { max-height: 580px; overflow-y: auto; }
    .lw-activity-row {
        padding: 14px 20px; border-top: 1px solid #f1f5f9;
        display: flex; gap: 12px; align-items: flex-start;
    }
    .lw-activity-row:first-child { border-top: 0; }
    .lw-activity-icon {
        width: 34px; height: 34px; border-radius: 11px; flex-shrink: 0;
        background: var(--lw-primary-soft); color: var(--lw-primary);
        display: flex; align-items: center; justify-content: center; font-size: 15px;
    }
    .lw-activity-title { font-size: 13px; font-weight: 850; color: var(--lw-slate-900); margin-bottom: 2px; }
    .lw-activity-desc { font-size: 12px; font-weight: 600; color: var(--lw-slate-500); }
    .lw-activity-time { font-size: 11px; font-weight: 700; color: var(--lw-slate-400); margin-top: 3px; }
    .lw-activity-time .lw-activity-actor { color: var(--lw-slate-600); font-weight: 800; }

    .lw-team { display: grid; gap: 10px; padding: 16px 20px; max-height: 260px; overflow-y: auto; }
    .lw-team-member { display: flex; align-items: center; gap: 10px; min-width: 0; }
    .lw-team-avatar {
        width: 34px; height: 34px; border-radius: 11px; flex: 0 0 auto;
        display: inline-flex; align-items: center; justify-content: center;
        background: var(--lw-primary-soft); color: var(--lw-primary);
        border: 1px solid #bfdbfe; font-size: 12px; font-weight: 900;
    }
    .lw-team-name { min-width: 0; color: var(--lw-slate-700); font-size: 12.5px; font-weight: 800; line-height: 1.35; }
    .lw-team-you {
        display: inline-flex; margin-left: 5px; padding: 2px 7px; border-radius: 999px;
        background: var(--lw-green-soft); color: var(--lw-green); font-size: 9.5px;
        font-weight: 900; letter-spacing: .03em; text-transform: uppercase; vertical-align: 1px;
    }

    /* Action panel (gradient) */
    .lw-actions {
        background: linear-gradient(135deg, #1e3a8a, #0f766e); color: #fff;
        border-radius: 16px; padding: 18px;
        box-shadow: 0 8px 22px rgba(15, 23, 42, 0.14);
        position: relative; overflow: hidden;
    }
    .lw-actions::after {
        content: ""; width: 140px; height: 140px; border-radius: 999px;
        background: rgba(255, 255, 255, 0.08); position: absolute; right: -52px; bottom: -56px;
    }
    .lw-actions-title {
        font-size: 11px; font-weight: 850; letter-spacing: 0.08em;
        text-transform: uppercase; opacity: 0.85; margin-bottom: 13px;
    }
    .lw-action-btn {
        position: relative; z-index: 2;
        width: 100%; background: rgba(255, 255, 255, 0.12);
        border: 1px solid rgba(255, 255, 255, 0.24); color: #fff;
        border-radius: 10px; padding: 10px 13px;
        text-decoration: none; font-size: 13px; font-weight: 850;
        display: flex; align-items: center; justify-content: space-between;
        transition: 0.15s ease; margin-bottom: 8px;
    }
    .lw-action-btn:last-child { margin-bottom: 0; }
    .lw-action-btn:hover { background: rgba(255, 255, 255, 0.2); color: #fff; }
    .lw-action-btn.light {
        background: #fff; color: #1e3a8a; border-color: #fff;
    }
    .lw-action-btn.light:hover { background: #eff6ff; color: var(--lw-primary-dark); }
    .lw-action-badge {
        display: inline-flex; align-items: center; justify-content: center;
        min-width: 22px; height: 19px; padding: 0 7px;
        background: #fde68a; color: #92400e;
        border-radius: 999px; font-size: 10.5px; font-weight: 900;
        margin-left: 7px;
    }

    /* Setup checklist */
    .lw-setup-item {
        display: flex; align-items: flex-start; gap: 12px;
        padding: 13px 18px; border-top: 1px solid #f1f5f9;
    }
    .lw-setup-item:first-of-type { border-top: 0; }
    .lw-setup-icon {
        width: 34px; height: 34px; border-radius: 11px; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center; font-size: 15px;
    }
    .lw-setup-icon.ready   { background: var(--lw-green-soft); color: var(--lw-green); border: 1px solid #a7f3d0; }
    .lw-setup-icon.pending { background: var(--lw-amber-soft); color: var(--lw-amber); border: 1px solid #fde68a; }
    .lw-setup-main { flex: 1; min-width: 0; }
    .lw-setup-title { font-size: 13px; font-weight: 850; color: var(--lw-slate-900); }
    .lw-setup-desc { font-size: 11.5px; font-weight: 650; color: var(--lw-slate-500); margin-top: 3px; line-height: 1.45; }
    .lw-setup-badge {
        display: inline-flex; align-items: center; gap: 4px;
        font-size: 10px; font-weight: 900; text-transform: uppercase; letter-spacing: 0.04em;
        border-radius: 999px; padding: 3px 9px; border: 1px solid; margin-top: 7px;
    }
    .lw-setup-badge.ready   { background: var(--lw-green-soft); color: #047857; border-color: #a7f3d0; }
    .lw-setup-badge.pending { background: var(--lw-amber-soft); color: #b45309; border-color: #fde68a; }

    .lw-readiness-summary {
        padding: 16px 18px; display: flex; align-items: center; gap: 12px;
        border-bottom: 1px solid #f1f5f9;
    }
    .lw-readiness-badge {
        width: 42px; height: 42px; border-radius: 12px;
        display: flex; align-items: center; justify-content: center; font-size: 19px; flex-shrink: 0;
    }
    .lw-readiness-badge.ready   { background: var(--lw-green-soft); color: var(--lw-green); }
    .lw-readiness-badge.partial { background: var(--lw-amber-soft); color: var(--lw-amber); }
    .lw-readiness-status { font-size: 14px; font-weight: 900; color: var(--lw-slate-900); }
    .lw-readiness-note { font-size: 11.5px; font-weight: 650; color: var(--lw-slate-500); margin-top: 1px; }

    .lw-empty { padding: 32px 20px; text-align: center; color: var(--lw-slate-500); font-size: 13px; font-weight: 650; }
    .lw-empty i { font-size: 28px; color: var(--lw-slate-400); display: block; margin-bottom: 9px; }
    .lw-empty-title { font-size: 14px; font-weight: 850; color: var(--lw-slate-900); margin-bottom: 2px; }

    @media (max-width: 768px) {
        .lw-title { font-size: 22px; }
    }
</style>
@endpush

@section('content')

<div class="lw-shell">

    @include('trainer.training.partials.workspace-header', ['activeLmsTab' => 'overview'])

    <main class="lw-page app-workspace-shell">

        @include('trainer.training.partials.workspace-page-heading', [
            'title' => 'Overview',
            'description' => 'Review participation, course content, assessments, and upcoming training activity.',
        ])

        {{-- Snapshot cards --}}
        <div class="row g-3 mb-4">
            <div class="col-md-6 col-xl-3">
                <div class="lw-stat">
                    <div class="lw-stat-icon lw-ic-blue"><i class="bi bi-people"></i></div>
                    <div class="lw-stat-label">Participants</div>
                    <div class="lw-stat-value">{{ $totalParticipants }}</div>
                    <div class="lw-gender">
                        @if ($genderTotal > 0)
                            <div class="lw-gender-bar" data-male="{{ $maleCount }}" data-female="{{ $femaleCount }}" data-total="{{ $genderTotal }}">
                                <div class="lw-gender-seg male"></div>
                                <div class="lw-gender-seg female"></div>
                            </div>
                            <div class="lw-gender-legend">
                                <span class="m"><i class="bi bi-gender-male"></i> {{ $maleCount }}</span>
                                <span class="f"><i class="bi bi-gender-female"></i> {{ $femaleCount }}</span>
                            </div>
                        @else
                            <div class="lw-gender-empty">Enrolled participant(s)</div>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="lw-stat">
                    <div class="lw-stat-icon lw-ic-indigo"><i class="bi bi-collection"></i></div>
                    <div class="lw-stat-label">Modules</div>
                    <div class="lw-stat-value">{{ $moduleCount }}</div>
                    <div class="lw-stat-note">{{ $contentBlockCount }} content block(s)</div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="lw-stat">
                    <div class="lw-stat-icon lw-ic-green"><i class="bi bi-folder2-open"></i></div>
                    <div class="lw-stat-label">Materials</div>
                    <div class="lw-stat-value">{{ $materials->count() }}</div>
                    <div class="lw-stat-note">Uploaded learning file(s)</div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="lw-stat">
                    <div class="lw-stat-row">
                        <div>
                            <div class="lw-stat-icon lw-ic-amber"><i class="bi bi-inbox"></i></div>
                            <div class="lw-stat-label">Pending Review</div>
                            <div class="lw-stat-value">{{ $pendingSubmissions }}</div>
                            <div class="lw-stat-note">Submission(s) awaiting</div>
                        </div>
                        @if ($pendingSubmissions > 0)
                            <span class="lw-stat-dot"></span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">

            {{-- LEFT: Overview + Recent Updates --}}
            <div class="col-xl-8">

                <div class="lw-panel mb-4">
                    <div class="lw-panel-head">
                        <div>
                            <h2 class="lw-panel-title">Training Overview</h2>
                            <div class="lw-panel-sub">A short summary of this training workspace.</div>
                        </div>
                    </div>
                    <div class="lw-overview-body">
                        @if (!empty($course->description))
                            <p class="lw-overview-text">{{ $course->description }}</p>
                        @else
                            <p class="lw-overview-text empty">No training description has been added yet.</p>
                        @endif

                        <ul class="lw-points {{ empty($course->description) ? '' : '' }}">
                            <li><i class="bi bi-people-fill"></i>
                                @if (!empty($course->capacity))
                                    <span><b>{{ $totalParticipants }}</b> participant(s) enrolled out of a capacity of <b>{{ $course->capacity }}</b>.</span>
                                @else
                                    <span><b>{{ $totalParticipants }}</b> participant(s) enrolled.</span>
                                @endif
                            </li>
                            <li><i class="bi bi-calendar-event"></i>
                                <span><b>{{ $sessionCount }}</b> session(s) scheduled for this training.</span>
                            </li>
                            <li><i class="bi bi-collection"></i>
                                <span><b>{{ $moduleCount }}</b> module(s) built with <b>{{ $contentBlockCount }}</b> content block(s).</span>
                            </li>
                            <li><i class="bi bi-patch-question"></i>
                                <span><b>{{ $assessmentCount }}</b> assessment/quiz item(s) available for learners.</span>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="lw-panel">
                    <div class="lw-panel-head">
                        <div>
                            <h2 class="lw-panel-title">Recent Workspace Updates</h2>
                            <div class="lw-panel-sub">Latest visible updates from material, assessment, session, and learner activity.</div>
                        </div>
                    </div>

                    @php($activityItems = $recentWorkspaceLogs instanceof \Illuminate\Support\Collection ? $recentWorkspaceLogs : collect())

                    @if ($activityItems->count() > 0)
                        <div class="lw-activity">
                            @foreach ($activityItems->take(12) as $activity)
                                <div class="lw-activity-row">
                                    <div class="lw-activity-icon"><i class="bi {{ $activity->icon ?? 'bi-clock-history' }}"></i></div>
                                    <div>
                                        <div class="lw-activity-title">{{ $activity->title ?? 'Workspace activity' }}</div>
                                        <div class="lw-activity-desc">{{ \Illuminate\Support\Str::limit($activity->description ?? 'Activity recorded.', 90) }}</div>
                                        <div class="lw-activity-time">
                                            <span class="lw-activity-actor">
                                                {{ $activity->trainer_name ?? 'Trainer' }}@if (!empty($activity->is_current_actor)) (You)@endif
                                            </span>
                                            <span aria-hidden="true"> &middot; </span>
                                            @if (!empty($activity->time))
                                                {{ \Carbon\Carbon::parse($activity->time)->diffForHumans() }}
                                            @else
                                                Time not available
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="lw-empty">
                            <i class="bi bi-clock-history"></i>
                            <div class="lw-empty-title">No workspace updates yet</div>
                            <div>Important changes to materials, assessments, submissions, and attendance will appear here.</div>
                        </div>
                    @endif
                </div>

            </div>

            {{-- RIGHT: Actions + Setup Checklist --}}
            <div class="col-xl-4">

                <div class="lw-panel mb-4" aria-labelledby="training-team-title">
                    <div class="lw-panel-head">
                        <div>
                            <h2 class="lw-panel-title" id="training-team-title">Training Team</h2>
                            <div class="lw-panel-sub">Trainers assigned to this training.</div>
                        </div>
                    </div>
                    <div class="lw-team">
                        @foreach ($trainingTeam as $teamMember)
                            <div class="lw-team-member">
                                <span class="lw-team-avatar" aria-hidden="true">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr(trim($teamMember->trainer_name ?? 'T'), 0, 1)) }}</span>
                                <div class="lw-team-name">
                                    {{ $teamMember->trainer_name ?? 'Trainer' }}
                                    @if (!empty($teamMember->is_current))<span class="lw-team-you">You</span>@endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="lw-actions mb-4">
                    <div class="lw-actions-title">Workspace Actions</div>
                    <a href="{{ route('trainer.courses.materials', $course->course_id) }}" class="lw-action-btn light">
                        <span><i class="bi bi-cloud-arrow-up me-2"></i> Manage Materials</span>
                        <i class="bi bi-arrow-right"></i>
                    </a>
                    <a href="{{ route('trainer.courses.submissions', $course->course_id) }}" class="lw-action-btn">
                        <span>
                            <i class="bi bi-inbox me-2"></i> Review Submissions
                            @if ($pendingSubmissions > 0)
                                <span class="lw-action-badge" title="{{ $pendingSubmissions }} submission(s) awaiting your review">
                                    {{ $pendingSubmissions }} pending
                                </span>
                            @endif
                        </span>
                        <i class="bi bi-arrow-right"></i>
                    </a>
                    <a href="{{ route('trainer.courses.learner-tracking', $course->course_id) }}" class="lw-action-btn">
                        <span><i class="bi bi-graph-up-arrow me-2"></i> Track Learning</span>
                        <i class="bi bi-arrow-right"></i>
                    </a>
                    <a href="{{ route('trainer.courses.attendance', $course->course_id) }}" class="lw-action-btn">
                        <span><i class="bi bi-person-check me-2"></i> View Attendance</span>
                        <i class="bi bi-arrow-right"></i>
                    </a>
                    <a href="{{ route('trainer.courses.participants', $course->course_id) }}" class="lw-action-btn">
                        <span><i class="bi bi-people me-2"></i> View Participants</span>
                        <i class="bi bi-arrow-right"></i>
                    </a>
                </div>

                <div class="lw-panel">
                    <div class="lw-panel-head">
                        <div>
                            <h2 class="lw-panel-title">Training Setup Checklist</h2>
                            <div class="lw-panel-sub">Important items needed before learners can use this training smoothly.</div>
                        </div>
                    </div>

                    <div class="lw-readiness-summary">
                        <div class="lw-readiness-badge {{ $allReady ? 'ready' : 'partial' }}">
                            <i class="bi {{ $allReady ? 'bi-check2-circle' : 'bi-hourglass-split' }}"></i>
                        </div>
                        <div>
                            <div class="lw-readiness-status">{{ $allReady ? 'Ready to run' : $readyCount . ' of 4 ready' }}</div>
                            <div class="lw-readiness-note">{{ $allReady ? 'Everything is set up for this training.' : 'Some setup items still need attention.' }}</div>
                        </div>
                    </div>

                    <div class="lw-setup-item">
                        <div class="lw-setup-icon {{ $hasParticipants ? 'ready' : 'pending' }}">
                            <i class="bi {{ $hasParticipants ? 'bi-check2-circle' : 'bi-exclamation-circle' }}"></i>
                        </div>
                        <div class="lw-setup-main">
                            <div class="lw-setup-title">Participants enrolled</div>
                            <div class="lw-setup-desc">{{ $totalParticipants }} participant(s) currently linked to this training.</div>
                            <div class="lw-setup-badge {{ $hasParticipants ? 'ready' : 'pending' }}">{{ $hasParticipants ? 'Ready' : 'Needs enrolment' }}</div>
                        </div>
                    </div>

                    <div class="lw-setup-item">
                        <div class="lw-setup-icon {{ $hasMaterial ? 'ready' : 'pending' }}">
                            <i class="bi {{ $hasMaterial ? 'bi-check2-circle' : 'bi-exclamation-circle' }}"></i>
                        </div>
                        <div class="lw-setup-main">
                            <div class="lw-setup-title">Learning material prepared</div>
                            <div class="lw-setup-desc">{{ $moduleCount }} module(s), {{ $contentBlockCount }} content block(s), {{ $materials->count() }} uploaded file(s).</div>
                            <div class="lw-setup-badge {{ $hasMaterial ? 'ready' : 'pending' }}">{{ $hasMaterial ? 'Ready' : 'Add material' }}</div>
                        </div>
                    </div>

                    <div class="lw-setup-item">
                        <div class="lw-setup-icon {{ $hasAssessment ? 'ready' : 'pending' }}">
                            <i class="bi {{ $hasAssessment ? 'bi-check2-circle' : 'bi-exclamation-circle' }}"></i>
                        </div>
                        <div class="lw-setup-main">
                            <div class="lw-setup-title">Quiz or task available</div>
                            <div class="lw-setup-desc">{{ $assessmentCount }} assessment/quiz item(s) connected to this training.</div>
                            <div class="lw-setup-badge {{ $hasAssessment ? 'ready' : 'pending' }}">{{ $hasAssessment ? 'Ready' : 'Create assessment' }}</div>
                        </div>
                    </div>

                    <div class="lw-setup-item">
                        <div class="lw-setup-icon {{ $hasSession ? 'ready' : 'pending' }}">
                            <i class="bi {{ $hasSession ? 'bi-check2-circle' : 'bi-exclamation-circle' }}"></i>
                        </div>
                        <div class="lw-setup-main">
                            <div class="lw-setup-title">Training session scheduled</div>
                            <div class="lw-setup-desc">{{ $sessionCount }} session(s) exist for attendance and session tracking.</div>
                            <div class="lw-setup-badge {{ $hasSession ? 'ready' : 'pending' }}">{{ $hasSession ? 'Ready' : 'Needs session' }}</div>
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </main>
</div>

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Participants gender split bar
        document.querySelectorAll('.lw-gender-bar').forEach(function (bar) {
            const total = Number(bar.dataset.total) || 0;
            if (total <= 0) return;
            const male = Number(bar.dataset.male) || 0;
            const female = Number(bar.dataset.female) || 0;
            const m = bar.querySelector('.lw-gender-seg.male');
            const f = bar.querySelector('.lw-gender-seg.female');
            if (m) m.style.width = ((male / total) * 100) + '%';
            if (f) f.style.width = ((female / total) * 100) + '%';
        });
    });
</script>
@endpush
