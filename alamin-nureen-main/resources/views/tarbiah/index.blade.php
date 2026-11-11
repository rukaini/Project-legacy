<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>My Enrollment | TrainHub Al Amin</title>
<link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
<link rel="stylesheet" href="{{ asset('assets/css/enrollment.css') }}">
</head><body class="trainhub-app">
@include('partials.topbar')
<main class="enroll-wrap">
<header class="enroll-header"><span class="eyebrow">Learning & Development</span><h1>My Enrollment</h1>
<p>Browse and join training or Tarbiah activities.</p></header>
@if(session('success'))<div class="enroll-alert success" role="status">{{ session('success') }}</div>@endif
@if(session('error'))<div class="enroll-alert error" role="alert">{{ session('error') }}</div>@endif
@if(!$trainingReady || !$ready)
<div class="enroll-alert error">Enrollment setup required. Contact the database administrator.</div>
@endif
<div class="enroll-controls"><div class="enroll-search-field"><span>⌕</span><input type="search" id="enrollSearch" placeholder="Search activities..." aria-label="Search activities"></div>
<div class="enroll-filter" role="group" aria-label="Filter activities"><button type="button" class="active" data-filter="all">All</button><button type="button" data-filter="upcoming">Upcoming</button><button type="button" data-filter="ongoing">Ongoing</button><button type="button" data-filter="history">History</button></div></div>
@php $today = date('Y-m-d'); $nowTime = date('H:i:s'); @endphp
<section class="enroll-section"><div class="enroll-section-heading"><h2>Training Courses</h2><span>{{ $training->count() }} course(s)</span></div>
<div class="enroll-grid">
@forelse($training as $t)
@php
  $dates = $sessionDates->get($t->courseID);
  $firstDate = $dates->first_date ?? null;
  $lastDate = $dates->last_date ?? null;
  $status = strtolower((string)($t->status ?: 'upcoming'));
  $phase = $status === 'cancelled' || $status === 'completed' || ($lastDate && $lastDate < $today) ? 'history'
    : (($firstDate && $firstDate > $today) ? 'upcoming' : (($firstDate && $firstDate <= $today) ? 'ongoing' : ($status === 'ongoing' ? 'ongoing' : 'upcoming')));
  $joined = !empty($t->my_participant);
  $closed = $t->closeDate && $t->closeDate < $today;
@endphp
<article class="enroll-card enroll-item" data-phase="{{ $phase }}" data-search="{{ strtolower($t->courseName.' '.$t->organiserName.' '.$t->courseCategory) }}">
<div class="enroll-card-upper"><span class="enroll-type training">Training</span><span class="enroll-phase {{ $phase }}">{{ ucfirst($phase) }}</span></div>
<h3>{{ $t->courseName }}</h3><p>{{ $t->description ?: 'Professional development training course' }}</p>
<dl><div><dt>Organiser</dt><dd>{{ $t->organiserName ?: 'Not specified' }}</dd></div><div><dt>Session dates</dt><dd>{{ $firstDate ?: 'TBA' }}{{ $lastDate && $lastDate !== $firstDate ? ' – '.$lastDate : '' }}</dd></div><div><dt>Training type</dt><dd>{{ $t->courseType ?: 'Training' }}</dd></div></dl>
<div class="enroll-card-footer">
@if($joined)<span class="enroll-joined">✓ {{ $t->is_pic ? 'Enrolled as PIC' : 'Enrolled as Staff Participant' }}</span>
@elseif(!$trainingReady)<span class="enroll-disabled">Enrollment setup required</span>
@elseif(!in_array($status,['upcoming','ongoing']) || $phase === 'history' || $closed)<span class="enroll-disabled">Enrollment closed</span>
@else<form method="POST" action="{{ route('enrollment.training.join',$t->courseID) }}">@csrf<button type="submit" class="enroll-button">Enroll in Training</button></form>@endif
<a href="{{ route('course.index',['view'=>$t->courseID]) }}">View details</a></div>
</article>
@empty<div class="enroll-empty">No training courses found.</div>@endforelse
</div></section>
<section class="enroll-section"><div class="enroll-section-heading"><h2>Tarbiah Activities</h2><span>{{ $activities->count() }} activity(s)</span></div>
<div class="enroll-grid">
@forelse($activities as $a)
@php
 $phase = $a->session_date < $today || ($a->session_date === $today && (string)$a->end_time < $nowTime) ? 'history'
    : ($a->session_date === $today && (string)$a->start_time <= $nowTime ? 'ongoing' : 'upcoming');
@endphp
<article class="enroll-card enroll-item" data-phase="{{ $phase }}" data-search="{{ strtolower($a->title.' '.$a->description.' '.($a->schoolName ?: '')) }}">
<div class="enroll-card-upper"><span class="enroll-type tarbiah">Tarbiah</span><span class="enroll-phase {{ $phase }}">{{ ucfirst($phase) }}</span></div>
<h3>{{ $a->title }}</h3><p>{{ $a->description ?: 'Tarbiah learning activity' }}</p>
<dl><div><dt>Date & Time</dt><dd>{{ $a->session_date }} · {{ substr($a->start_time,0,5) }} – {{ substr($a->end_time,0,5) }}</dd></div><div><dt>Location</dt><dd>{{ $a->location ?: 'To be confirmed' }}</dd></div><div><dt>School</dt><dd>{{ $a->schoolName ?: $a->schoolID }}</dd></div><div><dt>Credit hours</dt><dd>{{ $a->credit_hours !== null ? number_format($a->credit_hours,2).' h' : 'Not specified' }}</dd></div></dl>
<div class="enroll-card-footer">
@if($a->my_status)<span class="enroll-joined">✓ Enrollment {{ ucfirst($a->my_status) }}</span>
@elseif($phase==='history' || !$ready)<span class="enroll-disabled">Enrollment closed</span>
@else<form method="POST" action="{{ route('tarbiah.enroll',$a->tarbiah_id) }}">@csrf<button type="submit" class="enroll-button">Enroll in Tarbiah</button></form>@endif
</div>
</article>
@empty<div class="enroll-empty">No Tarbiah activities found.</div>@endforelse
</div></section>
<section class="enroll-review"><div><h2>Management Order Verification</h2><p>Review official orders for external training. Attendance approval is required.</p></div>
@if(!$managementOrdersReady)<div class="enroll-alert error">Management order approval will be available after the database patch is installed.</div>
@elseif($staffToVerify->isEmpty())<p class="enroll-muted">No external-organiser Staff Participants available for verification.</p>
@else
<form method="POST" action="{{ route('enrollment.order.verify') }}" class="order-verify-form">@csrf
<label><span>Staff participant and course</span><select id="orderParticipant" required><option value="">Choose a participant and course</option>
@foreach($staffToVerify as $p)<option value="{{ $p->courseID }}|{{ $p->staffID }}" @disabled($p->staffID === $staffID)>{{ $p->participantName }} — {{ $p->courseName }} ({{ $p->staffID }}){{ $p->staffID === $staffID ? ' · Cannot self-approve' : '' }}</option>@endforeach
</select></label>
<input name="staffID" id="orderStaffID" type="hidden" required><input name="courseID" id="orderCourseID" type="hidden" required>
<label><span>Decision</span><select name="decision" required><option value="approved">Approve management order</option><option value="revoked">Revoke previous approval</option></select></label>
<label class="order-reference"><span>Order reference</span><input name="order_reference" maxlength="150" minlength="5" required placeholder="e.g. ORD-TRAIN-2026-015"></label>
<label class="order-check"><input type="checkbox" name="verified_document" value="1" required><span>I checked the order document for this staff member.</span></label>
<button type="submit" class="enroll-button">Save Verification</button>
</form>
@endif</section>
<div class="enroll-no-results" id="enrollNoResults" hidden>No activities match your search or status filter.</div>
</main>
<script>
(()=>{
  const search=document.getElementById('enrollSearch'),buttons=[...document.querySelectorAll('.enroll-filter button')],items=[...document.querySelectorAll('.enroll-item')],empty=document.getElementById('enrollNoResults');
  let phase='all';
  function apply(){const term=(search.value||'').trim().toLowerCase();let matches=0;items.forEach(el=>{const visible=(phase==='all'||el.dataset.phase===phase)&&(!term||(el.dataset.search||'').includes(term));el.hidden=!visible;if(visible)matches++;});empty.hidden=!!matches;}
  buttons.forEach(btn=>btn.addEventListener('click',()=>{phase=btn.dataset.filter;buttons.forEach(b=>b.classList.toggle('active',b===btn));apply();}));search.addEventListener('input',apply);apply();
  const chosen=document.getElementById('orderParticipant');chosen?.addEventListener('change',()=>{const [course='',staff='']=(chosen.value||'').split('|');document.getElementById('orderCourseID').value=course;document.getElementById('orderStaffID').value=staff;});
})();
</script></body></html>
