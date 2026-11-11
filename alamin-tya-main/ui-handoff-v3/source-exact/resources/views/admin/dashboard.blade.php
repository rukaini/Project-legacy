@extends('admin.layouts.app')

@section('title', 'Admin Dashboard | Al Amin Edu Oasis')

@push('styles')
<style>
    /* KPI cards (5-up) */
    .adm-kpis { display: grid; grid-template-columns: repeat(5, 1fr); gap: 12px; margin-bottom: 18px; }
    @media (max-width: 1100px) { .adm-kpis { grid-template-columns: repeat(3, 1fr); } }
    @media (max-width: 680px)  { .adm-kpis { grid-template-columns: 1fr 1fr; } }
    .adm-kpi {
        background: #fff; border: 1px solid var(--adm-border); border-radius: 16px;
        padding: 14px 15px; box-shadow: var(--adm-shadow);
        border-left: 4px solid var(--adm-slate-400);
        transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
    }
    .adm-kpi:hover { transform: translateY(-2px); box-shadow: 0 12px 28px rgba(15, 23, 42, 0.08); }
    .adm-kpi.blue   { border-left-color: var(--adm-primary); }
    .adm-kpi.indigo { border-left-color: var(--adm-indigo); }
    .adm-kpi.green  { border-left-color: var(--adm-green); }
    .adm-kpi.amber  { border-left-color: var(--adm-amber); }
    .adm-kpi.red    { border-left-color: var(--adm-red); }
    .adm-kpi-icon { width: 36px; height: 36px; border-radius: 11px; display: flex; align-items: center; justify-content: center; font-size: 17px; margin-bottom: 9px; }
    .adm-ic-blue   { background: var(--adm-primary-soft); color: var(--adm-primary); }
    .adm-ic-indigo { background: var(--adm-indigo-soft); color: var(--adm-indigo); }
    .adm-ic-green  { background: var(--adm-green-soft);  color: var(--adm-green); }
    .adm-ic-amber  { background: var(--adm-amber-soft);  color: var(--adm-amber); }
    .adm-ic-red    { background: var(--adm-red-soft);    color: var(--adm-red); }
    .adm-kpi-label { font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.06em; color: var(--adm-slate-400); margin-bottom: 4px; }
    .adm-kpi-value { font-size: 24px; font-weight: 800; line-height: 1.05; color: var(--adm-slate-900); }
    .adm-kpi-note  { font-size: 11px; font-weight: 600; color: var(--adm-slate-500); margin-top: 4px; }
    .adm-kpi.amber .adm-kpi-note.urgent { color: var(--adm-red); font-weight: 800; }

    /* Schedule panel */
    .adm-week { margin-bottom: 18px; }
    .adm-week-card {
        background: #fff; border: 1px solid var(--adm-border); border-radius: 16px; box-shadow: var(--adm-shadow);
        border-left: 4px solid var(--adm-primary); overflow: hidden;
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }
    .adm-week-card:hover { transform: translateY(-2px); box-shadow: 0 12px 28px rgba(15, 23, 42, 0.08); }
    .adm-week-head { padding: 14px 18px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between; gap: 10px; }
    .adm-week-head h3 { font-size: 13.5px; font-weight: 800; margin: 0; display: flex; align-items: center; gap: 8px; color: var(--adm-slate-900); }
    .adm-week-head h3 i { color: var(--adm-primary); }
    .adm-week-count { font-size: 11px; font-weight: 800; color: var(--adm-slate-400); }
    .adm-week-body { max-height: 360px; overflow-y: auto; }
    .adm-week-section { padding: 11px 18px 6px; font-size: 10px; font-weight: 800; letter-spacing: 0.06em; text-transform: uppercase; color: var(--adm-slate-400); background: #f8fafc; border-bottom: 1px solid #f1f5f9; }
    .adm-week-row { padding: 11px 18px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; gap: 12px; text-decoration: none; color: inherit; transition: background 0.12s ease; }
    .adm-week-row:hover { background: #f8fafc; }
    .adm-week-row:last-child { border-bottom: 0; }
    .adm-week-date { width: 44px; flex-shrink: 0; text-align: center; border: 1px solid var(--adm-border); border-radius: 10px; overflow: hidden; }
    .adm-week-date .m { background: var(--adm-primary); color: #fff; font-size: 9px; font-weight: 800; letter-spacing: 0.06em; padding: 2px 0; }
    .adm-week-date.up .m { background: var(--adm-indigo); }
    .adm-week-date .d { font-size: 15px; font-weight: 800; color: var(--adm-slate-900); padding: 3px 0; }
    .adm-week-main { flex: 1; min-width: 0; }
    .adm-week-title { overflow-wrap: anywhere; font-size: 13px; font-weight: 800; color: var(--adm-slate-900); }
    .adm-week-meta  { overflow-wrap: anywhere; font-size: 11.5px; font-weight: 600; color: var(--adm-slate-500); margin-top: 2px; }
    .adm-week-time  { font-size: 11px; font-weight: 700; color: var(--adm-slate-400); white-space: nowrap; text-align: right; }

    /* Existing panels — accent stripes + hover */
    .adm-panel {
        background: #fff; border: 1px solid var(--adm-border);
        border-radius: 16px; box-shadow: var(--adm-shadow); overflow: hidden;
        height: 100%; display: flex; flex-direction: column;
        border-left: 4px solid var(--adm-primary);
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }
    .adm-panel.amber { border-left-color: var(--adm-amber); }
    .adm-panel:hover { transform: translateY(-2px); box-shadow: 0 12px 28px rgba(15, 23, 42, 0.08); }
    .adm-panel-head { padding: 16px 20px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between; gap: 12px; }
    .adm-panel-title { font-size: 15px; font-weight: 800; margin: 0; display: flex; align-items: center; gap: 9px; color: var(--adm-slate-900); }
    .adm-panel-title i { color: var(--adm-primary); }
    .adm-panel.amber .adm-panel-title i { color: var(--adm-amber); }
    .adm-panel-sub { font-size: 12px; font-weight: 500; color: var(--adm-slate-500); margin-top: 2px; }
    .adm-head-chip { font-size: 10.5px; font-weight: 800; color: var(--adm-amber); background: var(--adm-amber-soft); border: 1px solid #fde68a; border-radius: 999px; padding: 4px 10px; white-space: nowrap; }
    .adm-viewall-link { font-size: 12px; font-weight: 800; color: var(--adm-primary); text-decoration: none; display: inline-flex; align-items: center; gap: 5px; white-space: nowrap; }
    .adm-viewall-link:hover { text-decoration: underline; color: var(--adm-primary-dark); }
    .adm-panel-body { flex: 1; max-height: 420px; overflow-y: auto; }

    /* Activity rows */
    .adm-item { padding: 13px 20px; border-bottom: 1px solid #f1f5f9; display: flex; gap: 13px; align-items: flex-start; }
    .adm-item:last-child { border-bottom: 0; }
    .adm-act-icon { width: 36px; height: 36px; border-radius: 10px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; font-size: 16px; }
    .adm-act-main { flex: 1; min-width: 0; }
    .adm-act-line { overflow-wrap: anywhere; font-size: 13.5px; font-weight: 500; color: var(--adm-slate-600); line-height: 1.4; }
    .adm-act-line b { font-weight: 800; color: var(--adm-slate-900); }
    .adm-act-detail { font-size: 12px; font-weight: 500; color: var(--adm-slate-500); margin-top: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .adm-act-time { font-size: 11px; font-weight: 700; color: var(--adm-slate-400); white-space: nowrap; flex-shrink: 0; padding-top: 2px; }

    /* Proposal rows */
    .adm-prop { padding: 14px 20px; border-bottom: 1px solid #f1f5f9; }
    .adm-prop:last-of-type { border-bottom: 0; }
    .adm-prop-title { overflow-wrap: anywhere; font-size: 13.5px; font-weight: 800; color: var(--adm-slate-900); line-height: 1.35; }
    .adm-prop-meta { display: flex; flex-wrap: wrap; gap: 3px 12px; margin-top: 5px; font-size: 11.5px; font-weight: 600; color: var(--adm-slate-500); }
    .adm-prop-meta i { color: var(--adm-slate-400); }
    .adm-prop-foot { margin-top: 11px; display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap; }
    .adm-prop-bits { display: inline-flex; align-items: center; gap: 8px; flex-wrap: wrap; }
    .adm-prop-time { font-size: 11px; font-weight: 700; color: var(--adm-slate-400); display: inline-flex; align-items: center; gap: 5px; }

    .adm-wait { display: inline-flex; align-items: center; gap: 5px; font-size: 9.5px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.04em; border-radius: 999px; padding: 3px 9px; border: 1px solid; white-space: nowrap; }
    .adm-wait.fresh  { background: #f1f5f9; color: var(--adm-slate-600); border-color: var(--adm-border); }
    .adm-wait.warn   { background: var(--adm-amber-soft); color: var(--adm-amber); border-color: #fde68a; }
    .adm-wait.urgent { background: var(--adm-red-soft); color: var(--adm-red); border-color: #fecaca; }

    .adm-review-btn {
        display: inline-flex; align-items: center; gap: 6px;
        border: 1px solid #bfdbfe; background: #fff; color: var(--adm-primary);
        border-radius: 9px; padding: 6px 12px; font-size: 12px; font-weight: 800;
        text-decoration: none; transition: 0.15s ease;
    }
    .adm-review-btn:hover { background: var(--adm-primary-soft); color: var(--adm-primary-dark); }

    .adm-panel-footer { padding: 14px 20px; border-top: 1px solid #f1f5f9; background: #fff; margin-top: auto; }
    .adm-footer-btn { display: flex; align-items: center; justify-content: center; gap: 7px; width: 100%; background: var(--adm-primary); color: #fff; border-radius: 11px; padding: 10px 14px; font-size: 13px; font-weight: 800; text-decoration: none; transition: 0.15s ease; border: 0; }
    .adm-footer-btn:hover { background: var(--adm-primary-dark); color: #fff; }
    .adm-footer-btn-outline { display: flex; align-items: center; justify-content: center; gap: 7px; width: 100%; background: #fff; color: var(--adm-slate-700); border: 1px solid var(--adm-border); border-radius: 11px; padding: 10px 14px; font-size: 13px; font-weight: 800; text-decoration: none; transition: 0.15s ease; }
    .adm-footer-btn-outline:hover { background: #f8fafc; color: var(--adm-slate-900); border-color: #cbd5e1; }

    .adm-empty { padding: 30px 20px; text-align: center; color: var(--adm-slate-500); font-size: 13px; font-weight: 500; }
    .adm-empty i { font-size: 28px; color: var(--adm-slate-400); display: block; margin-bottom: 9px; }
</style>
@endpush

@section('content')

<main class="adm-wrap adm-wrap--wide app-page-shell" data-admin-page-shell data-admin-width="wide">

    {{-- Header --}}
    <div class="adm-head">
        <div class="adm-head-row">
            <div class="adm-head-icon"><i class="bi bi-speedometer2" aria-hidden="true"></i></div>
            <div>
                <h1 class="adm-title">Admin Dashboard</h1>
                <p class="adm-subtitle">Welcome back, {{ session('user_name') ?? 'Admin' }}. Here is what is happening across the training centre.</p>
            </div>
        </div>
    </div>

    {{-- KPI strip --}}
    <div class="adm-kpis">
        <div class="adm-kpi blue">
            <div class="adm-kpi-icon adm-ic-blue"><i class="bi bi-person-badge"></i></div>
            <div class="adm-kpi-label">Trainers</div>
            <div class="adm-kpi-value">{{ $totalTrainers ?? 0 }}</div>
            <div class="adm-kpi-note">{{ $activeTrainersWeek ?? 0 }} active this week</div>
        </div>

        <div class="adm-kpi indigo">
            <div class="adm-kpi-icon adm-ic-indigo"><i class="bi bi-mortarboard"></i></div>
            <div class="adm-kpi-label">Trainings</div>
            <div class="adm-kpi-value">{{ $totalCourses ?? 0 }}</div>
            <div class="adm-kpi-note">{{ $ongoingTrainings ?? 0 }} ongoing · {{ $completedThisMonth ?? 0 }} done this month</div>
        </div>

        <div class="adm-kpi green">
            <div class="adm-kpi-icon adm-ic-green"><i class="bi bi-people"></i></div>
            <div class="adm-kpi-label">Participants</div>
            <div class="adm-kpi-value">{{ $totalParticipants ?? 0 }}</div>
            <div class="adm-kpi-note">
                @if (!is_null($newParticipantsMonth))
                    +{{ $newParticipantsMonth }} this month
                @else
                    total enrolled
                @endif
            </div>
        </div>

        <div class="adm-kpi amber">
            <div class="adm-kpi-icon adm-ic-amber"><i class="bi bi-clipboard-check"></i></div>
            <div class="adm-kpi-label">Pending proposals</div>
            <div class="adm-kpi-value">{{ $pendingProposals ?? 0 }}</div>
            @if (($stalePendingProposals ?? 0) > 0)
                <div class="adm-kpi-note urgent">{{ $stalePendingProposals }} waiting over 7 days</div>
            @else
                <div class="adm-kpi-note">all fresh</div>
            @endif
        </div>

        <div class="adm-kpi red">
            <div class="adm-kpi-icon adm-ic-red"><i class="bi bi-file-earmark-text"></i></div>
            <div class="adm-kpi-label">Teaching Plans</div>
            <div class="adm-kpi-value">{{ $teachingPlansNeedReview ?? 0 }}</div>
            <div class="adm-kpi-note">{{ ($teachingPlansNeedReview ?? 0) > 0 ? 'need review' : 'all reviewed' }}</div>
        </div>
    </div>

    {{-- Schedule (today + upcoming combined) --}}
    @php
        $hasToday = $todaySessions->count() > 0;
        $hasUpcoming = $upcomingSessions->count() > 0;
        $upcomingHeading = ($upcomingWindow ?? 'this week') === 'soon' ? 'Coming up soon' : 'Coming up this week';
    @endphp
    @if ($hasToday || $hasUpcoming)
        <div class="adm-week">
            <div class="adm-week-card">
                <div class="adm-week-head">
                    <h3><i class="bi bi-calendar3"></i> Schedule</h3>
                    <span class="adm-week-count">
                        @if ($hasToday) {{ $todaySessions->count() }} today @endif
                        @if ($hasToday && $hasUpcoming) &middot; @endif
                        @if ($hasUpcoming) {{ $upcomingSessions->count() }} upcoming @endif
                    </span>
                </div>
                <div class="adm-week-body">
                    @if ($hasToday)
                        <div class="adm-week-section">Today</div>
                        @foreach ($todaySessions as $s)
                            <a href="{{ route('admin.courses', ['course' => $s->course_id]) }}" class="adm-week-row">
                                <div class="adm-week-date">
                                    <div class="m">{{ \Carbon\Carbon::now()->format('M') }}</div>
                                    <div class="d">{{ \Carbon\Carbon::now()->format('d') }}</div>
                                </div>
                                <div class="adm-week-main">
                                    <div class="adm-week-title">{{ $s->session_title ?: 'Training session' }}</div>
                                    <div class="adm-week-meta">
                                        {{ $s->course_name ?: '—' }}
                                        @if (!empty($s->trainer_names))
                                            · {{ implode(', ', $s->trainer_names) }}
                                        @endif
                                        @if (!empty($s->session_type))
                                            · {{ $s->session_type }}
                                        @endif
                                    </div>
                                </div>
                                <div class="adm-week-time">
                                    {{ $s->start_time ? \Carbon\Carbon::parse($s->start_time)->format('g:i A') : '' }}
                                </div>
                            </a>
                        @endforeach
                    @endif
                    @if ($hasUpcoming)
                        <div class="adm-week-section">{{ $upcomingHeading }}</div>
                        @foreach ($upcomingSessions as $s)
                            @php $d = $s->date ? \Carbon\Carbon::parse($s->date) : null; @endphp
                            <a href="{{ route('admin.courses', ['course' => $s->course_id]) }}" class="adm-week-row">
                                <div class="adm-week-date up">
                                    <div class="m">{{ $d ? strtoupper($d->format('M')) : '--' }}</div>
                                    <div class="d">{{ $d ? $d->format('d') : '--' }}</div>
                                </div>
                                <div class="adm-week-main">
                                    <div class="adm-week-title">{{ $s->session_title ?: 'Training session' }}</div>
                                    <div class="adm-week-meta">
                                        {{ $s->course_name ?: '—' }}
                                        @if (!empty($s->trainer_names))
                                            · {{ implode(', ', $s->trainer_names) }}
                                        @endif
                                        @if (!empty($s->session_type))
                                            · {{ $s->session_type }}
                                        @endif
                                    </div>
                                </div>
                                <div class="adm-week-time">
                                    {{ $d ? $d->format('D') : '' }}@if ($s->start_time) · {{ \Carbon\Carbon::parse($s->start_time)->format('g:i A') }} @endif
                                </div>
                            </a>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
    @else
        <div class="adm-week">
            <div class="adm-week-card">
                <div class="adm-week-head">
                    <h3><i class="bi bi-calendar3" aria-hidden="true"></i> Schedule</h3>
                    <span class="adm-week-count">No upcoming sessions</span>
                </div>
                <div class="adm-empty">
                    <i class="bi bi-calendar2-check" aria-hidden="true"></i>
                    <div class="t">The schedule is clear</div>
                    <div class="s">No training sessions are scheduled for today or the upcoming window.</div>
                </div>
            </div>
        </div>
    @endif

    {{-- Activity + Proposals panels --}}
    <div class="row g-4">

        {{-- LEFT: Recent activity --}}
        <div class="col-lg-7">
            <div class="adm-panel">
                <div class="adm-panel-head">
                    <div>
                        <h2 class="adm-panel-title"><i class="bi bi-activity"></i> Recent Trainer Activity</h2>
                        <div class="adm-panel-sub">What your trainers have been doing.</div>
                    </div>
                    <a href="{{ route('admin.activity') }}" class="adm-viewall-link">View all <i class="bi bi-arrow-right"></i></a>
                </div>

                <div class="adm-panel-body">
                    @php
                        $actionMap = collect($recentActivity ?? [])
                            ->pluck('action')
                            ->unique()
                            ->mapWithKeys(fn ($action) => [$action => \App\Support\TrainerActivityLogger::actionMeta($action)])
                            ->all();
                    @endphp

                    @forelse ($recentActivity ?? [] as $a)
                        @php
                            $meta = $actionMap[$a->action] ?? [ucfirst(str_replace(['.', '_'], ' ', (string) $a->action)), 'bi-dot', 'slate'];
                        @endphp
                        <div class="adm-item">
                            <div class="adm-act-icon adm-ic-{{ $meta[2] }}"><i class="bi {{ $meta[1] }}"></i></div>
                            <div class="adm-act-main">
                                <div class="adm-act-line"><b>{{ $a->trainer_name ?? 'A trainer' }}</b> &middot; {{ $meta[0] }}</div>
                                @if (!empty($a->detail))
                                    <div class="adm-act-detail">{{ $a->detail }}</div>
                                @endif
                            </div>
                            <div class="adm-act-time">
                                {{ $a->created_at ? \Carbon\Carbon::parse($a->created_at)->diffForHumans() : '' }}
                            </div>
                        </div>
                    @empty
                        <div class="adm-empty"><i class="bi bi-activity"></i>No trainer activity recorded yet.</div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- RIGHT: Proposals --}}
        <div class="col-lg-5">
            <div class="adm-panel amber">
                <div class="adm-panel-head">
                    <h2 class="adm-panel-title"><i class="bi bi-clipboard-check"></i> Proposals to Review</h2>
                    <div class="d-flex align-items-center gap-2">
                        @if (isset($pendingProposals) && $pendingProposals > 0)
                            <span class="adm-head-chip">{{ $pendingProposals }} pending</span>
                        @endif
                        <a href="{{ route('admin.proposals') }}" class="adm-viewall-link">View all <i class="bi bi-arrow-right"></i></a>
                    </div>
                </div>

                <div class="adm-panel-body">
                    @forelse ($proposalsToReview ?? [] as $p)
                        @php
                            $created = $p->created_at ? \Carbon\Carbon::parse($p->created_at) : null;
                            $wait = $created ? max(0, (int) $created->diffInDays(\Carbon\Carbon::now())) : 0;
                            if ($wait >= 14)    { $waitClass = 'urgent'; $waitText = $wait . 'd waiting'; }
                            elseif ($wait >= 7) { $waitClass = 'warn';   $waitText = $wait . 'd waiting'; }
                            else                { $waitClass = 'fresh';  $waitText = $wait === 0 ? 'Today' : $wait . 'd waiting'; }
                        @endphp
                        <div class="adm-prop">
                            <div class="adm-prop-title">{{ $p->proposed_title }}</div>
                            <div class="adm-prop-meta">
                                <span><i class="bi bi-person"></i> {{ $p->trainer_name ?? 'Unknown trainer' }}</span>
                                @if (!empty($p->training_mode))
                                    <span><i class="bi bi-easel"></i> {{ $p->training_mode }}</span>
                                @endif
                            </div>
                            <div class="adm-prop-foot">
                                <span class="adm-prop-bits">
                                    <span class="adm-prop-time">
                                        <i class="bi bi-clock-history"></i>
                                        {{ $created ? $created->format('d M Y') : 'recently' }}
                                    </span>
                                    <span class="adm-wait {{ $waitClass }}"><i class="bi bi-hourglass-split"></i> {{ $waitText }}</span>
                                </span>
                                <a href="{{ route('admin.proposals') }}" class="adm-review-btn">Review <i class="bi bi-arrow-right"></i></a>
                            </div>
                        </div>
                    @empty
                        <div class="adm-empty"><i class="bi bi-clipboard-check"></i>No proposals waiting for review. All caught up.</div>
                    @endforelse
                </div>
            </div>
        </div>

    </div>
</main>

@endsection
