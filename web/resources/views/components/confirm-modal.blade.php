{{--
    Global SweetAlert2 confirm handler untuk delete/aksi berbahaya.

    Cara pakai di blade:
    <form method="POST" action="{{ route('...', $item) }}" class="d-inline">
        @csrf @method('DELETE')
        <button type="button" class="btn btn-danger btn-sm"
                data-confirm
                data-title="Hapus Kegiatan?"
                data-text="Data tidak bisa dikembalikan."
                data-confirm-text="Ya, Hapus"
                data-cancel-text="Batal">
            <i class="fas fa-trash"></i>
        </button>
    </form>

    Script ini cukup di-include sekali via layout (letakkan di @stack('scripts') master layout).
--}}

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('[data-confirm]');
        if (!btn) return;

        e.preventDefault();

        const form  = btn.closest('form');
        const title = btn.dataset.title       || 'Apakah Anda yakin?';
        const text  = btn.dataset.text        || 'Tindakan ini tidak dapat dibatalkan.';
        const confirmText = btn.dataset.confirmText || 'Ya, Lanjutkan';
        const cancelText  = btn.dataset.cancelText  || 'Batal';

        Swal.fire({
            title,
            text,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor:  '#6c757d',
            confirmButtonText: confirmText,
            cancelButtonText:  cancelText,
        }).then(function (result) {
            if (result.isConfirmed && form) {
                form.submit();
            }
        });
    });
});
</script>
@endpush
