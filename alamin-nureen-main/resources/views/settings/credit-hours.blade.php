<!doctype html>
<html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Credit Hour Settings | TrainHub Al Amin</title>
<link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
<link rel="stylesheet" href="{{ asset('assets/css/credit-settings.css') }}">
</head><body class="trainhub-app">@include('partials.topbar')
<main class="credit-page">
  <div class="credit-top"><div><a class="credit-back" href="{{ route('dashboard') }}">← Dashboard</a>
  <span class="credit-kicker">Staff EDU Settings</span><h1>Credit Hour Settings</h1><p>Set yearly goals and training rules.</p></div>
  <a class="credit-secondary" href="{{ route('dashboard') }}">Back to dashboard</a></div>
  @if(session('success'))<div class="credit-alert success" role="status">{{ session('success') }}</div>@endif
  @if($errors->any())<div class="credit-alert error" role="alert">{{ $errors->first() }}</div>@endif
  @unless($snapshotReady)<div class="credit-alert error" role="alert">LMS table missing. Contact the database administrator.</div>@endunless
  <form action="{{ route('dashboard.rules') }}" method="POST">@csrf
    <section class="credit-panel"><div class="credit-panel-head"><div class="credit-panel-icon">01</div><div><h2>Annual Goals</h2><p>Staff EDU total: up to 40 hours</p></div></div>
      <div class="credit-grid">
        @foreach(['training_target_hours'=>'Training goal','tarbiah_target_hours'=>'Tarbiah goal','ladap_target_hours'=>'Teacher LADAP goal'] as $key=>$label)
        <label class="credit-field"><span>{{ $label }}</span><div class="credit-number"><input type="number" name="{{ $key }}" min="0" max="{{ $key === 'ladap_target_hours' ? 500 : 40 }}" step="0.5" value="{{ old($key, $rules[$key]) }}" required><span>hours</span></div></label>
        @endforeach
      </div>
    </section>
    <section class="credit-panel"><div class="credit-panel-head"><div class="credit-panel-icon">02</div><div><h2>New Teacher LMS</h2><p>Newly appointed teachers only</p></div></div>
      <div class="credit-grid"><label class="credit-field"><span>LMS courses required</span><div class="credit-number"><input type="number" name="new_teacher_lms_required" min="1" max="100" step="1" value="{{ old('new_teacher_lms_required', (int)$rules['new_teacher_lms_required']) }}" required><span>courses</span></div></label>
      <div class="credit-info"><strong>Existing teachers keep their target</strong><span>New appointments use the latest default.</span></div></div>
    </section>
    <section class="credit-panel"><div class="credit-panel-head"><div class="credit-panel-icon">03</div><div><h2>Credit Multipliers</h2><p>For approved attendance only</p></div></div>
      <div class="credit-grid credit-rules-grid">
        @foreach([
          'trainer_multiplier'=>['Staff as trainer','Trainer credit'],
          'alamin_multiplier'=>['Al Amin organiser','Internal training'],
          'outside_order_multiplier'=>['Other organiser · Ordered','Approved management order'],
          'other_multiplier'=>['Other organiser · Voluntary','No approved order'],
        ] as $key => $info)
        <label class="credit-field credit-multiplier"><span>{{ $info[0] }}</span><small>{{ $info[1] }}</small><div class="credit-number"><input type="number" name="{{ $key }}" min="0" max="5" step="0.1" value="{{ old($key, $rules[$key]) }}" required><span>×</span></div></label>
        @endforeach
      </div>
    </section>
    <div class="credit-save"><a href="{{ route('dashboard') }}">Cancel</a><button type="submit" @disabled(!$snapshotReady)>Save Changes</button></div>
  </form>
</main></body></html>
