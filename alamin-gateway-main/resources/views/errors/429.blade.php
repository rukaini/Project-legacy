@extends('layouts.portal')
@section('title', 'Please wait | ELIT Portal')
@section('content')
<section class="error-card"><span class="error-code">429</span><h1>Too many attempts.</h1><p>For your security, please wait a minute before trying again.</p><a class="primary-button link-button" href="{{ route('login') }}">Return to login</a></section>
@endsection
