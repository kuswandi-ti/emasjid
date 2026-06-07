@props([
    'title'   => 'Belum ada data',
    'message' => 'Data akan muncul di sini setelah ditambahkan.',
    'icon'    => 'fas fa-inbox',
    'action'  => null,   // label tombol
    'route'   => null,   // route tombol
])
{{--
    Usage:
    <x-empty-state
        title="Belum ada kegiatan"
        message="Tambahkan kegiatan pertama untuk masjid Anda."
        icon="fas fa-calendar-alt"
        action="Tambah Kegiatan"
        route="admin.activities.create"
    />
--}}

<div class="text-center py-5 text-muted">
    <i class="{{ $icon }} fa-3x mb-3 opacity-50"></i>
    <h5 class="fw-semibold">{{ $title }}</h5>
    <p class="mb-0">{{ $message }}</p>
    @if ($action && $route && Route::has($route))
        <a href="{{ route($route) }}" class="btn btn-primary mt-3">
            <i class="fas fa-plus me-1"></i> {{ $action }}
        </a>
    @endif
</div>
