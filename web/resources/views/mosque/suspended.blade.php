@extends('layouts.app')

@section('title', 'Masjid Ditangguhkan')

@section('body-class', 'bg-body-secondary')

@section('body')
<div class="container min-vh-100 d-flex align-items-center justify-content-center py-5">
    <div class="row justify-content-center w-100">
        <div class="col-12 col-sm-10 col-md-8 col-lg-6 col-xl-5">
            <div class="card shadow-sm border-warning">
                <div class="card-body text-center p-5">

                    <div class="mb-4">
                        <i class="fas fa-pause-circle fa-4x text-warning"></i>
                    </div>

                    <h2 class="card-title fw-bold text-warning mb-3">
                        Masjid Ditangguhkan
                    </h2>

                    @if (!empty($mosqueName))
                        <p class="text-muted mb-1">
                            <strong>{{ $mosqueName }}</strong>
                        </p>
                    @endif

                    <p class="card-text text-muted mb-4">
                        Masjid Anda sedang ditangguhkan dan tidak dapat diakses saat ini.
                        Silakan hubungi administrator platform untuk informasi lebih lanjut
                        atau untuk mengajukan reaktivasi.
                    </p>

                    <hr class="my-4">

                    <p class="text-muted small mb-4">
                        <i class="fas fa-info-circle me-1"></i>
                        Jika Anda merasa ini adalah kesalahan atau ingin mengajukan keberatan,
                        harap menghubungi tim eMasjid melalui saluran dukungan resmi.
                    </p>

                    <a href="{{ route('login') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-arrow-left me-1"></i> Kembali ke Login
                    </a>

                </div>
            </div>
        </div>
    </div>
</div>
@endsection
