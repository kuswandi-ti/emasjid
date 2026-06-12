@props(['items' => []])
{{--
    Usage:
    <x-breadcrumb :items="[
        ['label' => 'Dashboard', 'route' => 'admin.dashboard'],
        ['label' => 'Kegiatan',  'route' => 'admin.activities.index'],
        ['label' => 'Tambah'],   // no route = current page
    ]" />
--}}

<nav aria-label="breadcrumb">
    <ol class="breadcrumb mb-0">
        @foreach ($items as $item)
            @if ($loop->last)
                <li class="breadcrumb-item active" aria-current="page">
                    {{ $item['label'] }}
                </li>
            @else
                <li class="breadcrumb-item">
                    @if (isset($item['route']) && Route::has($item['route']))
                        <a href="{{ route($item['route']) }}">{{ $item['label'] }}</a>
                    @else
                        {{ $item['label'] }}
                    @endif
                </li>
            @endif
        @endforeach
    </ol>
</nav>
