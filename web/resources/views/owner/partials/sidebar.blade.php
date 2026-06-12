<div class="bg-dark text-white flex-shrink-0" id="sidebar-wrapper" style="min-height: 100vh; width: 250px;">
    <div class="sidebar-heading p-3 border-bottom border-secondary">
        <h5 class="mb-0 fw-bold">
            <i class="bi bi-building-fill-check me-2 text-warning"></i>EMasjid Owner
        </h5>
    </div>
    <div class="list-group list-group-flush">
        <a href="{{ route('owner.dashboard') }}"
           class="list-group-item list-group-item-action bg-dark text-white border-0 py-3 {{ request()->routeIs('owner.dashboard') ? 'active bg-primary' : '' }}">
            <i class="bi bi-speedometer2 me-2"></i> Dashboard
        </a>

        <a href="{{ route('owner.mosques.index') }}"
           class="list-group-item list-group-item-action bg-dark text-white border-0 py-3 {{ request()->routeIs('owner.mosques.index') ? 'active bg-primary' : '' }}">
            <i class="bi bi-building me-2"></i> All Mosques
        </a>

        <a href="{{ route('owner.mosques.pending') }}"
           class="list-group-item list-group-item-action bg-dark text-white border-0 py-3 {{ request()->routeIs('owner.mosques.pending') ? 'active bg-primary' : '' }}">
            <i class="bi bi-clock-history me-2"></i> Pending Verification
            @if(($pendingCount ?? 0) > 0)
                <span class="badge bg-warning text-dark ms-2">{{ $pendingCount }}</span>
            @endif
        </a>

        <a href="{{ route('owner.users.index') }}"
           class="list-group-item list-group-item-action bg-dark text-white border-0 py-3 {{ request()->routeIs('owner.users*') ? 'active bg-primary' : '' }}">
            <i class="bi bi-people me-2"></i> Akun Admin Platform
        </a>

        <a href="{{ route('owner.reports.fee.index') }}"
           class="list-group-item list-group-item-action bg-dark text-white border-0 py-3 {{ request()->routeIs('owner.reports*') ? 'active bg-primary' : '' }}">
            <i class="bi bi-bar-chart-line me-2"></i> Laporan Fee
        </a>

        <a href="{{ route('owner.settings.index') }}"
           class="list-group-item list-group-item-action bg-dark text-white border-0 py-3 {{ request()->routeIs('owner.settings*') ? 'active bg-primary' : '' }}">
            <i class="bi bi-gear me-2"></i> Settings
        </a>
    </div>
</div>
