@props([
    'id'      => 'data-table',
    'columns' => [],   // [['title' => 'Nama', 'data' => 'name'], ...]
    'url'     => null, // Ajax URL untuk server-side processing
    'order'   => [[0, 'asc']],
])
{{--
    Usage (server-side):
    <x-datatable
        id="mosques-table"
        :columns="[
            ['title' => '#',      'data' => 'DT_RowIndex', 'orderable' => false, 'searchable' => false],
            ['title' => 'Nama',   'data' => 'name'],
            ['title' => 'Kota',   'data' => 'city'],
            ['title' => 'Aksi',   'data' => 'action', 'orderable' => false],
        ]"
        url="{{ route('owner.mosques.data') }}"
    />
--}}

<div class="card shadow-sm">
    <div class="card-body p-0">
        <table id="{{ $id }}" class="table table-hover table-striped w-100 mb-0">
            @if (count($columns))
                <thead class="table-light">
                    <tr>
                        @foreach ($columns as $col)
                            <th>{{ $col['title'] }}</th>
                        @endforeach
                    </tr>
                </thead>
            @endif
            <tbody></tbody>
        </table>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const tableEl = document.getElementById('{{ $id }}');
    if (!tableEl) return;

    @if ($url)
    new DataTable('#{{ $id }}', {
        processing: true,
        serverSide: true,
        ajax: '{{ $url }}',
        columns: @json($columns),
        order: @json($order),
        language: {
            url: '//cdn.datatables.net/plug-ins/2.0.3/i18n/id.json',
        },
        responsive: true,
    });
    @else
    new DataTable('#{{ $id }}', {
        order: @json($order),
        language: {
            url: '//cdn.datatables.net/plug-ins/2.0.3/i18n/id.json',
        },
        responsive: true,
    });
    @endif
});
</script>
@endpush
