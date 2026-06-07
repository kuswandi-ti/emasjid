@props([
    'title'       => '',
    'breadcrumbs' => [],
])
{{--
    Usage:
    <x-page-header title="Daftar Kegiatan" :breadcrumbs="[...]">
        <x-slot:actions>
            <a href="{{ route('...') }}" class="btn btn-primary btn-sm">Tambah</a>
        </x-slot:actions>
    </x-page-header>
--}}

<div class="container-fluid">
    <div class="row mb-2">
        <div class="col-sm-6">
            <h1 class="m-0 h4">{{ $title }}</h1>
        </div>
        <div class="col-sm-6">
            <div class="d-flex align-items-center justify-content-sm-end gap-2 mt-2 mt-sm-0">
                @isset($actions)
                    {{ $actions }}
                @endisset
                @if (count($breadcrumbs))
                    <x-breadcrumb :items="$breadcrumbs" />
                @endif
            </div>
        </div>
    </div>
</div>
