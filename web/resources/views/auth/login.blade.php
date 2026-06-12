@extends('layouts.auth')

@section('title', 'Login')

@section('content')

<p class="login-box-msg text-muted">Masuk ke akun Anda</p>

@if ($errors->any())
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-circle me-1"></i>
        {{ $errors->first() }}
    </div>
@endif

<form method="POST" action="{{ route('login') }}">
    @csrf

    <x-form.input
        type="email"
        name="email"
        label="Email"
        placeholder="admin@emasjid.id"
        :value="old('email')"
        required
    />

    <x-form.input
        type="password"
        name="password"
        label="Password"
        placeholder="••••••••"
        required
    />

    <div class="mb-3 form-check">
        <input type="checkbox" class="form-check-input" id="remember" name="remember">
        <label class="form-check-label" for="remember">Ingat saya</label>
    </div>

    <div class="d-grid">
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-sign-in-alt me-1"></i> Masuk
        </button>
    </div>
</form>

@endsection
