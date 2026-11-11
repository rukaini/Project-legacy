@extends('layouts.portal')
@section('title', 'Access denied | ELIT Portal')
@section('content')
<section class="error-card"><span class="error-code">403</span><h1>This system is not available for your active role.</h1><p>Your verified account has not been changed. Return to your approved systems and choose another destination.</p><a class="primary-button link-button" href="{{ route('systems.index') }}">Back to systems</a></section>
@endsection
