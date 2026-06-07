{{--
    Renders Bootstrap 5 dismissible alerts for Laravel flash messages.
    Supports: success, error, warning, info.
    Usage: <x-alert />  — letakkan sekali di layout, otomatis tampil jika ada flash.
--}}

@foreach (['success', 'error', 'warning', 'info'] as $type)
    @if (session($type))
        @php
            $bsType  = $type === 'error' ? 'danger' : $type;
            $iconMap = [
                'success' => 'fas fa-check-circle',
                'danger'  => 'fas fa-times-circle',
                'warning' => 'fas fa-exclamation-triangle',
                'info'    => 'fas fa-info-circle',
            ];
            $icon = $iconMap[$bsType] ?? 'fas fa-bell';
        @endphp
        <div class="alert alert-{{ $bsType }} alert-dismissible fade show d-flex align-items-center gap-2"
             role="alert">
            <i class="{{ $icon }}"></i>
            <span>{{ session($type) }}</span>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
@endforeach
