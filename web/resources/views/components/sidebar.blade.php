@props([
    'items'      => [],
    'brand'      => config('app.name', 'EMasjid'),
    'brandRoute' => '#',
])

<aside class="app-sidebar bg-dark shadow" data-bs-theme="dark">

    {{-- Brand --}}
    <div class="sidebar-brand">
        <a href="{{ Route::has($brandRoute) ? route($brandRoute) : '#' }}" class="brand-link">
            <i class="fas fa-mosque me-2 text-success"></i>
            <span class="brand-text fw-semibold">{{ $brand }}</span>
        </a>
    </div>

    {{-- Sidebar menu --}}
    <div class="sidebar-wrapper">
        <nav class="mt-2">
            <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview"
                role="menu" data-accordion="false">

                @foreach ($items as $item)
                    @if (isset($item['children']))
                        {{-- Treeview (dropdown) item --}}
                        @php
                            $isActive = collect($item['children'])
                                ->contains(fn($child) => Route::has($child['route']) && request()->routeIs($child['route']));
                        @endphp
                        <li class="nav-item{{ $isActive ? ' menu-open' : '' }}">
                            <a href="#" class="nav-link{{ $isActive ? ' active' : '' }}">
                                <i class="{{ $item['icon'] }} nav-icon"></i>
                                <p>
                                    {{ $item['label'] }}
                                    <i class="nav-arrow fas fa-chevron-right ms-auto"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                @foreach ($item['children'] as $child)
                                    @php
                                        $childActive = Route::has($child['route']) && request()->routeIs($child['route']);
                                    @endphp
                                    <li class="nav-item">
                                        <a href="{{ Route::has($child['route']) ? route($child['route']) : '#' }}"
                                           class="nav-link{{ $childActive ? ' active' : '' }}">
                                            <i class="fas fa-circle nav-icon" style="font-size:.4rem"></i>
                                            <p>{{ $child['label'] }}</p>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </li>
                    @else
                        {{-- Single item --}}
                        @php
                            $active = Route::has($item['route']) && request()->routeIs($item['route']);
                        @endphp
                        <li class="nav-item">
                            <a href="{{ Route::has($item['route']) ? route($item['route']) : '#' }}"
                               class="nav-link{{ $active ? ' active' : '' }}">
                                <i class="{{ $item['icon'] }} nav-icon"></i>
                                <p>{{ $item['label'] }}</p>
                            </a>
                        </li>
                    @endif
                @endforeach

            </ul>
        </nav>
    </div>

</aside>
