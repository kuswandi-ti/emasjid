@extends('owner.layouts.app')

@section('title', $mosque->name)

@section('content')

{{-- Task 10.1: Flash Messages --}}
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

{{-- Page Header --}}
<div class="row mb-4">
    <div class="col">
        <div class="d-flex align-items-center gap-3">
            <a href="{{ route('owner.mosques.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Back to Mosque List
            </a>
            <div>
                <h2 class="mb-0">{{ $mosque->name }}</h2>
                <p class="text-muted mb-0">Registered on {{ $mosque->created_at->format('d M Y H:i') }}</p>
            </div>
        </div>
    </div>
</div>

{{-- Tasks 10.2–10.5: Pending Verification card (only shown when status is PENDING) --}}
@if($mosque->status === \App\Enums\MosqueStatus::Pending)
    <div class="card mb-4 border-warning shadow-sm">
        <div class="card-body">
            <h5 class="card-title text-warning">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>Pending Verification
                <span class="badge bg-warning text-dark ms-2">Pending</span>
            </h5>
            <p class="card-text text-muted">
                This mosque is awaiting your approval. Please review the details below before taking action.
            </p>
            {{-- Task 10.3: Approve button | Task 10.4: Reject button --}}
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-success" onclick="approveMosque({{ $mosque->id }})">
                    <i class="bi bi-check-circle me-1"></i>Approve
                </button>
                <button type="button" class="btn btn-danger" onclick="rejectMosque({{ $mosque->id }})">
                    <i class="bi bi-x-circle me-1"></i>Reject
                </button>
            </div>
        </div>
    </div>
@endif
{{-- /Tasks 10.2–10.5 --}}

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

                    <div class="col-sm-6">
                        <span class="text-muted small d-block mb-1">Postal Code</span>
                        <span>{{ $mosque->postal_code ?? '-' }}</span>
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
                    <div class="alert alert-danger d-flex align-items-start gap-3 mb-0" role="alert">
                        <i class="bi bi-x-circle-fill fs-4 flex-shrink-0 mt-1"></i>
                        <div>
                            <span class="fw-semibold d-block mb-1">Rejection Reason</span>
                            <p class="mb-0">{{ $mosque->rejection_reason ?? '-' }}</p>
                        </div>
                    </div>
                @endif

                @if($statusValue === 'active')
                    <div class="alert alert-success d-flex align-items-start gap-3 mb-0" role="alert">
                        <i class="bi bi-check-circle-fill fs-4 flex-shrink-0 mt-1"></i>
                        <div>
                            <span class="fw-semibold d-block mb-1">Approved At</span>
                            <p class="mb-0">
                                @if($mosque->approved_at)
                                    {{ \Carbon\Carbon::parse($mosque->approved_at)->format('d M Y H:i') }}
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

@push('scripts')
{{-- Task 11.1: SweetAlert2 CDN --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    {{-- Task 11.2: approveMosque function --}}
    function approveMosque(mosqueId) {
        Swal.fire({
            title: 'Approve This Mosque?',
            text: 'The mosque will be activated and the admin will receive a notification email.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Approve',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = `/owner/mosques/${mosqueId}/approve`;

                const csrfToken = document.createElement('input');
                csrfToken.type = 'hidden';
                csrfToken.name = '_token';
                csrfToken.value = '{{ csrf_token() }}';

                form.appendChild(csrfToken);
                document.body.appendChild(form);
                form.submit();
            }
        });
    }

    {{-- Task 11.3: rejectMosque function --}}
    function rejectMosque(mosqueId) {
        Swal.fire({
            title: 'Reject This Mosque?',
            html: '<textarea id="rejection-reason" class="swal2-textarea" placeholder="Enter rejection reason (min 10 characters)" style="width: 100%; height: 100px;"></textarea>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Reject',
            cancelButtonText: 'Cancel',
            preConfirm: () => {
                const reason = document.getElementById('rejection-reason').value;
                if (!reason || reason.length < 10) {
                    Swal.showValidationMessage('Rejection reason must be at least 10 characters');
                    return false;
                }
                return reason;
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = `/owner/mosques/${mosqueId}/reject`;

                const csrfToken = document.createElement('input');
                csrfToken.type = 'hidden';
                csrfToken.name = '_token';
                csrfToken.value = '{{ csrf_token() }}';

                const reasonInput = document.createElement('input');
                reasonInput.type = 'hidden';
                reasonInput.name = 'rejection_reason';
                reasonInput.value = result.value;

                form.appendChild(csrfToken);
                form.appendChild(reasonInput);
                document.body.appendChild(form);
                form.submit();
            }
        });
    }
</script>
@endpush
