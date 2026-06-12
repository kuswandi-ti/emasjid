@extends('owner.layouts.app')

@section('title', 'Kelola Akun Admin Platform')

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
@endpush

@section('content')
<div class="row mb-4">
    <div class="col">
        <h2>Kelola Akun Admin Platform</h2>
        <p class="text-muted">Kelola akun pengguna dengan akses Owner Panel</p>
    </div>
    <div class="col-auto d-flex align-items-center">
        <a href="{{ route('owner.users.create') }}" class="btn btn-primary">
            <i class="bi bi-person-plus me-1"></i> Tambah Akun
        </a>
    </div>
</div>

{{-- Flash Messages --}}
@if (session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle me-1"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
    </div>
@endif

@if (session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle me-1"></i> {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
    </div>
@endif

{{-- Users DataTable --}}
<div class="card">
    <div class="card-body" id="users-table-wrapper">
        <div class="table-responsive">
            <table id="usersTable" class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Nama</th>
                        <th>Email</th>
                        <th>Tanggal Dibuat</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    {{-- Rows populated via DataTables AJAX server-side --}}
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $(document).ready(function () {
        var $wrapper = $('#users-table-wrapper');
        var $spinner = $('<div id="dt-loading" class="text-center py-4">' +
            '<div class="spinner-border text-primary" role="status" style="width:2rem;height:2rem;">' +
            '<span class="visually-hidden">Loading…</span></div>' +
            '<p class="mt-2 text-muted small">Memuat data…</p></div>');
        $wrapper.prepend($spinner);

        $('#usersTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('owner.users.index') }}",
            columns: [
                { data: 'name', name: 'name' },
                { data: 'email', name: 'email' },
                {
                    data: 'created_at',
                    name: 'created_at',
                    render: function (data) {
                        if (!data) return '-';
                        var d = new Date(data);
                        return d.toLocaleDateString('id-ID', {
                            day: '2-digit', month: 'short', year: 'numeric'
                        });
                    }
                },
                { data: 'action', name: 'action', orderable: false, searchable: false },
            ],
            language: {
                processing:  'Memuat…',
                search:      'Cari:',
                searchPlaceholder: 'Cari nama atau email…',
                zeroRecords: 'Belum ada akun Admin Platform lain.',
                emptyTable:  'Belum ada akun Admin Platform lain.',
                info:        'Menampilkan _START_ - _END_ dari _TOTAL_ akun',
                infoEmpty:   'Tidak ada akun yang ditampilkan',
                infoFiltered:'(difilter dari _MAX_ total akun)',
                lengthMenu:  'Tampilkan _MENU_ akun',
                paginate: {
                    first:    '«',
                    previous: '‹',
                    next:     '›',
                    last:     '»',
                },
            },
            columnDefs: [
                { orderable: false, targets: -1 }
            ],
            order: [[2, 'desc']],
            initComplete: function () {
                $spinner.remove();
            }
        });

        {{-- SweetAlert confirm for delete buttons (delegated event) --}}
        $(document).on('click', '.btn-delete-user', function (e) {
            e.preventDefault();
            var form = $(this).closest('form');
            var name = $(this).data('name');

            Swal.fire({
                title: 'Hapus Akun?',
                html: 'Akun <strong>' + name + '</strong> akan dihapus secara permanen.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Hapus',
                cancelButtonText: 'Batal',
            }).then(function (result) {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    });
</script>
@endpush
