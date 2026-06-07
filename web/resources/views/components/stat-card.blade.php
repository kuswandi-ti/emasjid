@props([
    'label'  => '',
    'value'  => '0',
    'icon'   => 'fas fa-chart-bar',
    'color'  => 'primary',    // bootstrap color: primary, success, warning, danger, info
    'footer' => null,
    'route'  => null,
])
{{--
    Usage:
    <x-stat-card
        label="Total Masjid"
        value="{{ $totalMosques }}"
        icon="fas fa-mosque"
        color="success"
        footer="Lihat semua"
        route="owner.mosques.index"
    />
--}}

<div class="col-md-3 col-sm-6">
    <div class="info-box card-stat-accent shadow-sm">
        <span class="info-box-icon bg-{{ $color }} text-white">
            <i class="{{ $icon }}"></i>
        </span>
        <div class="info-box-content">
            <span class="info-box-text text-muted">{{ $label }}</span>
            <span class="info-box-number fw-bold">{{ $value }}</span>
            @if ($footer)
                <div class="progress mt-1" style="height:3px">
                    <div class="progress-bar bg-{{ $color }}" style="width:100%"></div>
                </div>
                <small class="text-muted">
                    @if ($route && Route::has($route))
                        <a href="{{ route($route) }}" class="text-decoration-none">{{ $footer }}</a>
                    @else
                        {{ $footer }}
                    @endif
                </small>
            @endif
        </div>
    </div>
</div>
