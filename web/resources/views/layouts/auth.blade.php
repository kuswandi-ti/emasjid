@extends('layouts.app')

@section('body-class', 'login-page bg-body-secondary')

@section('body')
<div class="login-box">
    <div class="login-logo">
        <a href="{{ url('/') }}">
            <b>e</b>Masjid
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body login-card-body">
            @yield('content')
        </div>
    </div>
</div>
@endsection
