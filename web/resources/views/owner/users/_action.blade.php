<div class="d-flex gap-1">
    <a href="{{ route('owner.users.edit', $user->id) }}" class="btn btn-sm btn-outline-warning">
        <i class="bi bi-pencil"></i> Edit
    </a>

    <form id="delete-form-{{ $user->id }}" action="{{ route('owner.users.destroy', $user->id) }}" method="POST" class="d-inline">
        @csrf
        @method('DELETE')
        <button type="button" class="btn btn-sm btn-outline-danger btn-delete-user"
                data-name="{{ addslashes($user->name) }}">
            <i class="bi bi-trash"></i> Hapus
        </button>
    </form>
</div>
