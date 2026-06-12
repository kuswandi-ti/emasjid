@extends('owner.layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="row mb-4">
    <div class="col">
        <h2>Platform Overview</h2>
        <p class="text-muted">Monitor platform-wide statistics and activity</p>
    </div>
</div>

{{-- Statistics Cards --}}
<div class="row g-4 mb-4">
    {{-- Active Mosques --}}
    <div class="col-md-6 col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-2">Active Mosques</h6>
                        <h2 class="mb-0">{{ $statistics['total_mosques_active'] }}</h2>
                    </div>
                    <div class="bg-success bg-opacity-10 p-3 rounded">
                        <i class="bi bi-building fs-1 text-success"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Pending Verification --}}
    <div class="col-md-6 col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-2">Pending Verification</h6>
                        <h2 class="mb-0">{{ $statistics['total_mosques_pending'] }}</h2>
                    </div>
                    <div class="bg-warning bg-opacity-10 p-3 rounded">
                        <i class="bi bi-clock-history fs-1 text-warning"></i>
                    </div>
                </div>
                @if($statistics['total_mosques_pending'] > 0)
                    <a href="{{ route('owner.mosques.pending') }}" class="btn btn-sm btn-warning mt-3">
                        <i class="bi bi-clock-history me-1"></i>Review Pending
                    </a>
                @endif
            </div>
        </div>
    </div>

    {{-- Suspended Mosques --}}
    <div class="col-md-6 col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-2">Suspended</h6>
                        <h2 class="mb-0">{{ $statistics['total_mosques_suspended'] }}</h2>
                    </div>
                    <div class="bg-danger bg-opacity-10 p-3 rounded">
                        <i class="bi bi-exclamation-triangle fs-1 text-danger"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Total Members --}}
    <div class="col-md-6 col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-2">Total Members</h6>
                        <h2 class="mb-0">{{ number_format($statistics['total_congregation']) }}</h2>
                    </div>
                    <div class="bg-primary bg-opacity-10 p-3 rounded">
                        <i class="bi bi-people fs-1 text-primary"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Platform Revenue --}}
    <div class="col-md-6 col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-2">Platform Revenue</h6>
                        <h2 class="mb-0 fs-4">{{ $statistics['total_platform_fees_formatted'] }}</h2>
                    </div>
                    <div class="bg-info bg-opacity-10 p-3 rounded">
                        <i class="bi bi-cash-stack fs-1 text-info"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Quick Actions --}}
<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Quick Actions</h5>
                <div class="d-grid gap-2">
                    <a href="{{ route('owner.mosques.pending') }}" class="btn btn-outline-primary">
                        <i class="bi bi-clock me-2"></i>Review Pending Mosques
                    </a>
                    <a href="{{ route('owner.mosques.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-list me-2"></i>View All Mosques
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
