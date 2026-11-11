@extends('layouts.portal')
@section('title', 'Portal unavailable | ELIT Portal')
@section('content')
<section class="error-card"><span class="error-code">!</span><h1>We could not complete that request.</h1><p>Please return to the systems page and try again. No account information was changed.</p><a class="primary-button link-button" href="{{ route('systems.index') }}">Back to systems</a></section>
@endsection
