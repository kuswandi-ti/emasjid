@extends('owner.layouts.app')

@section('title', 'All Mosques')

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
@endpush

@section('content')
<div class="row mb-4">
    <div class="col">
        <h2>All Mosques</h2>
        <p class="text-muted">Manage all registered mosques on the platform</p>
    </div>
</div>

{{-- Task 12.2: Filter Form --}}
<div class="card mb-4" x-data="{ status: '{{ $filters['status'] ?? '' }}' }">
    <div class="card-body">
        <form method="GET" action="{{ route('owner.mosques.index') }}" class="row g-3">
            <div class="col-md-3">
                <label for="status" class="form-label">Status</label>
                <select id="status" name="status" class="form-select" x-model="status">
                    <option value="">All Status</option>
                    <option value="active" {{ ($filters['status'] ?? '') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="pending" {{ ($filters['status'] ?? '') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="suspended" {{ ($filters['status'] ?? '') === 'suspended' ? 'selected' : '' }}>Suspended</option>
                    <option value="rejected" {{ ($filters['status'] ?? '') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                </select>
            </div>
            <div class="col-md-6">
                <label for="search" class="form-label">Search</label>
                <input type="text"
                       id="search"
                       name="search"
                       class="form-control"
                       placeholder="Search by name or city..."
                       value="{{ $filters['search'] ?? '' }}">
            </div>
            <div class="col-md-3 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-search"></i> Filter
                </button>
                <a href="{{ route('owner.mosques.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-x-circle"></i> Clear
                </a>
            </div>
        </form>
    </div>
</div>

{{-- Task 12.3: Mosques Data Table --}}
<div class="card">
    <div class="card-body" id="mosque-table-wrapper">
        @if($mosques->isEmpty())
            <div class="text-center py-5">
                <i class="bi bi-building fs-1 text-muted"></i>
                <p class="mt-3 text-muted">
                    @if(!empty($filters['status']) || !empty($filters['search']))
                        No mosques found matching
                        @if(!empty($filters['status']))
                            status <strong>{{ $filters['status'] }}</strong>
                        @endif
                        @if(!empty($filters['search']))
                            search <strong>"{{ $filters['search'] }}"</strong>
                        @endif
                        . Try adjusting your filters.
                    @else
                        No mosques have been registered yet.
                    @endif
                </p>
            </div>
        @else
            <div class="table-responsive">
                <table id="mosquesTable" class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Name</th>
                            <th>City</th>
                            <th>Status</th>
                            <th>Admin</th>
                            <th>Members</th>
                            <th>Registered</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($mosques as $mosque)
                        <tr>
                            <td><strong>{{ $mosque->name }}</strong></td>
                            <td>{{ $mosque->city }}</td>
                            <td>
                                @php
                                    $statusMap = [
                                        'active'    => ['class' => 'success',   'label' => 'Active'],
                                        'pending'   => ['class' => 'warning',   'label' => 'Pending'],
                                        'suspended' => ['class' => 'warning',   'label' => 'Suspended'],
                                        'rejected'  => ['class' => 'secondary', 'label' => 'Rejected'],
                                    ];
                                    $statusValue = $mosque->status instanceof \BackedEnum
                                        ? $mosque->status->value
                                        : (string) $mosque->status;
                                    $cfg = $statusMap[$statusValue] ?? ['class' => 'secondary', 'label' => ucfirst($statusValue)];
                                @endphp
                                <span class="badge bg-{{ $cfg['class'] }}">{{ $cfg['label'] }}</span>
                            </td>
                            <td>
                                @if($mosque->admin)
                                    {{ $mosque->admin->name }}
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>{{ $mosque->members_count ?? 0 }}</td>
                            <td>{{ $mosque->created_at->format('d M Y') }}</td>
                            <td>
                                <a href="{{ route('owner.mosques.show', $mosque->id) }}"
                                   class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye"></i> View
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            <div class="mt-3">
                {{ $mosques->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

{{-- Task 12.4 / 16.1: DataTables initialization with loading indicator --}}
@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script>
    $(document).ready(function () {
        // Only initialise DataTables when the table is in the DOM (not in empty-state)
        if ($('#mosquesTable').length === 0) return;

        // Task 16.1: show a spinner inside the table wrapper while DataTables initialises
        var $wrapper = $('#mosque-table-wrapper');
        var $spinner = $('<div id="dt-loading" class="text-center py-4">' +
            '<div class="spinner-border text-primary" role="status" style="width:2rem;height:2rem;">' +
            '<span class="visually-hidden">Loading…</span></div>' +
            '<p class="mt-2 text-muted small">Loading table…</p></div>');
        $wrapper.prepend($spinner);

        var table = $('#mosquesTable').DataTable({
            paging: false,          // server-side pagination is handled by Laravel
            info: false,            // hide "Showing X to Y of Z entries"
            searching: true,        // enable client-side search box
            ordering: true,         // enable column sorting
            responsive: true,
            language: {
                search: 'Quick search:',
                searchPlaceholder: 'Filter visible rows...',
                zeroRecords: 'No matching mosques found',
            },
            columnDefs: [
                { orderable: false, targets: -1 }  // disable sorting on Actions column
            ],
            initComplete: function () {
                // Remove loading spinner once DataTables is ready
                $spinner.remove();
            }
        });
    });
</script>
@endpush
