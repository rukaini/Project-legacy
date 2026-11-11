@extends('trainer.layouts.app')

@section('title', 'Teaching Plan | '.$course->course_name)

@push('styles')
    @if ($isLmsMode)
        @include('trainer.training.partials.workspace-header-styles')
    @else
        @include('trainer.simple.partials.workspace-styles')
    @endif
    @include('trainer.teaching-plan.partials.styles')
@endpush

@section('content')
<div class="{{ $isLmsMode ? 'lw-shell' : 'sw-shell' }}">
    @if ($isLmsMode)
        @include('trainer.training.partials.workspace-header', ['activeLmsTab' => 'teaching-plan'])
    @else
        @include('trainer.simple.partials.header', ['activeSimpleTab' => 'teaching-plan'])
    @endif

    <main class="tp-page app-workspace-shell">
        @include('trainer.teaching-plan.partials.card')
    </main>
</div>
@endsection
