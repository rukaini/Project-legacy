@php
    $appToasts = collect([
        ['type' => 'success', 'message' => session('success')],
        ['type' => 'error', 'message' => session('error')],
        ['type' => 'success', 'message' => session('status')],
    ])->filter(fn (array $toast): bool => filled($toast['message']))
        ->unique(fn (array $toast): string => $toast['type'].'|'.$toast['message'])
        ->values();
@endphp

@if ($appToasts->isNotEmpty())
    <div class="app-toast-region" data-app-toast-region aria-live="polite" aria-atomic="false">
        @foreach ($appToasts as $toast)
            <div class="app-toast {{ $toast['type'] }}"
                 data-app-toast
                 data-auto-dismiss="3500"
                 role="{{ $toast['type'] === 'error' ? 'alert' : 'status' }}">
                <i class="bi {{ $toast['type'] === 'error' ? 'bi-exclamation-triangle-fill' : 'bi-check-circle-fill' }}" aria-hidden="true"></i>
                <span>{{ $toast['message'] }}</span>
            </div>
        @endforeach
    </div>
@endif
