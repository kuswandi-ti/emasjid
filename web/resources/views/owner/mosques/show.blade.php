@extends('owner.layouts.app')

@section('title', $mosque->name)

@section('content')

{{-- Page Header --}}
<div class="row mb-4">
    <div class="col">
        <div class="d-flex align-items-center gap-3">
            <a href="{{ route('owner.mosques.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Back to Mosque List
            </a>
            <div>
                <h2 class="mb-0">{{ $mosque->name }}</h2>
                <p class="text-muted mb-0">Mosque detail information</p>
            </div>
        </div>
    </div>
</div>

{{-- 14.2 Basic Information --}}
<div class="row mb-4">
    <div class="col-lg-8">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white py-3">
                <h5 class="card-title mb-0">
                    <i class="bi bi-info-circle me-2 text-primary"></i>Basic Information
                </h5>
            </div>
            <div class="card-body">
                {{-- Mosque photo --}}
                @if($mosque->photo)
                    <div class="mb-4 text-center">
                        <img src="{{ $mosque->photo }}"
                             alt="{{ $mosque->name }}"
                             class="img-fluid rounded"
                             style="max-height: 280px; object-fit: cover; width: 100%;">
                    </div>
                @endif

                {{-- Status badge --}}
                <div class="mb-3">
                    <span class="text-muted small d-block mb-1">Status</span>
                    @php
                        $statusColors = [
                            'active'    => 'success',
                            'pending'   => 'warning',
                            'suspended' => 'danger',
                            'rejected'  => 'secondary',
                        ];
                        $statusValue = $mosque->status instanceof \App\Enums\MosqueStatus
                            ? $mosque->status->value
                            : $mosque->status;
                        $statusLabel = $mosque->status instanceof \App\Enums\MosqueStatus
                            ? $mosque->status->labels()
                            : ucfirst($mosque->status);
                        $badgeColor  = $statusColors[$statusValue] ?? 'secondary';
                    @endphp
                    <span class="badge bg-{{ $badgeColor }} fs-6">{{ $statusLabel }}</span>
                </div>

                <div class="row g-3">
                    <div class="col-sm-6">
                        <span class="text-muted small d-block mb-1">Mosque Name</span>
                        <span class="fw-semibold">{{ $mosque->name }}</span>
                    </div>

                    <div class="col-sm-6">
                        <span class="text-muted small d-block mb-1">City</span>
                        <span>{{ $mosque->city ?? '-' }}</span>
                    </div>

                    <div class="col-sm-6">
                        <span class="text-muted small d-block mb-1">Province</span>
                        <span>{{ $mosque->province ?? '-' }}</span>
                    </div>

                    <div class="col-12">
                        <span class="text-muted small d-block mb-1">Address</span>
                        <span>{{ $mosque->address ?? '-' }}</span>
                    </div>

                    @if($mosque->description)
                        <div class="col-12">
                            <span class="text-muted small d-block mb-1">Description</span>
                            <p class="mb-0">{{ $mosque->description }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- 14.3 Contact Details & 14.4 Banking Information stacked in the right column --}}
    <div class="col-lg-4">

        {{-- 14.3 Contact Details --}}
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="card-title mb-0">
                    <i class="bi bi-person-lines-fill me-2 text-primary"></i>Contact Details
                </h5>
            </div>
            <div class="card-body">
                @if($mosque->admin)
                    <div class="mb-3">
                        <span class="text-muted small d-block mb-1">Admin Name</span>
                        <span class="fw-semibold">{{ $mosque->admin->name }}</span>
                    </div>
                    <div class="mb-3">
                        <span class="text-muted small d-block mb-1">Email</span>
                        <a href="mailto:{{ $mosque->admin->email }}" class="text-decoration-none">
                            {{ $mosque->admin->email }}
                        </a>
                    </div>
                    <div class="mb-0">
                        <span class="text-muted small d-block mb-1">Phone</span>
                        <span>{{ $mosque->admin->phone ?? '-' }}</span>
                    </div>
                @else
                    <p class="text-muted mb-0">No admin assigned.</p>
                @endif
            </div>
        </div>

        {{-- 14.4 Banking Information --}}
        <div class="card shadow-sm">
            <div class="card-header bg-white py-3">
                <h5 class="card-title mb-0">
                    <i class="bi bi-bank me-2 text-primary"></i>Banking Information
                </h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <span class="text-muted small d-block mb-1">Bank Name</span>
                    <span class="fw-semibold">{{ $mosque->bank_name ?? '-' }}</span>
                </div>
                <div class="mb-3">
                    <span class="text-muted small d-block mb-1">Account Number</span>
                    <span class="fw-semibold font-monospace">{{ $mosque->bank_account_number ?? '-' }}</span>
                </div>
                <div class="mb-0">
                    <span class="text-muted small d-block mb-1">Account Holder</span>
                    <span>{{ $mosque->bank_account_name ?? '-' }}</span>
                </div>
            </div>
        </div>

    </div>
</div>

{{-- 14.5 Location Map --}}
<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header bg-white py-3">
                <h5 class="card-title mb-0">
                    <i class="bi bi-geo-alt me-2 text-primary"></i>Location
                </h5>
            </div>
            <div class="card-body">
                @if($mosque->latitude && $mosque->longitude)
                    <div class="ratio ratio-16x9">
                        <iframe
                            src="https://maps.google.com/maps?q={{ $mosque->latitude }},{{ $mosque->longitude }}&z=15&output=embed"
                            allowfullscreen
                            loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade"
                            class="rounded"
                            title="Mosque Location on Google Maps">
                        </iframe>
                    </div>
                    <p class="text-muted small mt-2 mb-0">
                        <i class="bi bi-pin-map me-1"></i>
                        Coordinates: {{ $mosque->latitude }}, {{ $mosque->longitude }}
                    </p>
                @else
                    <p class="text-muted mb-0">
                        <i class="bi bi-geo-alt-fill me-2"></i>Location coordinates are not available for this mosque.
                    </p>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- 14.6 Statistics --}}
<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header bg-white py-3">
                <h5 class="card-title mb-0">
                    <i class="bi bi-bar-chart me-2 text-primary"></i>Statistics
                </h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <div class="p-3 bg-primary bg-opacity-10 rounded text-center">
                            <i class="bi bi-people fs-2 text-primary mb-2 d-block"></i>
                            <span class="text-muted small d-block mb-1">Congregation Members</span>
                            <span class="fs-4 fw-bold text-primary">{{ number_format($mosque->members_count ?? 0) }}</span>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="p-3 bg-success bg-opacity-10 rounded text-center">
                            <i class="bi bi-cash-stack fs-2 text-success mb-2 d-block"></i>
                            <span class="text-muted small d-block mb-1">Total Donations</span>
                            <span class="fs-4 fw-bold text-success">{{ format_rupiah((int) ($mosque->total_donations ?? 0)) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- 14.7 Status-Specific Information --}}
@if($statusValue === 'rejected' || $statusValue === 'active')
<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header bg-white py-3">
                <h5 class="card-title mb-0">
                    <i class="bi bi-info-square me-2 text-primary"></i>Status Information
                </h5>
            </div>
            <div class="card-body">
                @if($statusValue === 'rejected')
                    <div class="d-flex align-items-start gap-3 p-3 bg-danger bg-opacity-10 rounded">
                        <i class="bi bi-x-circle-fill text-danger fs-4 flex-shrink-0 mt-1"></i>
                        <div>
                            <span class="fw-semibold text-danger d-block mb-1">Rejection Reason</span>
                            <p class="mb-0">{{ $mosque->rejection_reason ?? '-' }}</p>
                        </div>
                    </div>
                @endif

                @if($statusValue === 'active')
                    <div class="d-flex align-items-start gap-3 p-3 bg-success bg-opacity-10 rounded">
                        <i class="bi bi-check-circle-fill text-success fs-4 flex-shrink-0 mt-1"></i>
                        <div>
                            <span class="fw-semibold text-success d-block mb-1">Approved At</span>
                            <p class="mb-0">
                                @if($mosque->approved_at)
                                    {{ \Carbon\Carbon::parse($mosque->approved_at)->translatedFormat('d F Y') }}
                                @else
                                    -
                                @endif
                            </p>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endif

@endsection
