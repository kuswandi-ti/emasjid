@props([
    'label'    => '',
    'name'     => '',
    'accept'   => 'image/*',
    'preview'  => null,    // URL gambar existing untuk preview
    'required' => false,
    'hint'     => null,
])
{{--
    Usage:
    <x-form.file
        label="Foto Masjid"
        name="photo"
        accept="image/jpeg,image/png"
        :preview="$mosque->photo_url"
        hint="Maks 2MB, format JPG/PNG"
    />
--}}

<div class="mb-3">
    @if ($label)
        <label for="{{ $name }}" class="form-label fw-semibold">
            {{ $label }}
            @if ($required)
                <span class="text-danger">*</span>
            @endif
        </label>
    @endif

    @if ($preview)
        <div class="mb-2">
            <img id="preview-{{ $name }}" src="{{ $preview }}"
                 alt="Preview" class="img-thumbnail" style="max-height:150px">
        </div>
    @else
        <img id="preview-{{ $name }}" src="" alt="Preview"
             class="img-thumbnail mb-2 d-none" style="max-height:150px">
    @endif

    <input
        type="file"
        id="{{ $name }}"
        name="{{ $name }}"
        accept="{{ $accept }}"
        {{ $required ? 'required' : '' }}
        {{ $attributes->merge(['class' => 'form-control' . ($errors->has($name) ? ' is-invalid' : '')]) }}
        onchange="previewImage(this, 'preview-{{ $name }}')"
    >

    @if ($hint && !$errors->has($name))
        <div class="form-text text-muted">{{ $hint }}</div>
    @endif

    @error($name)
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

@once
@push('scripts')
<script>
function previewImage(input, previewId) {
    const preview = document.getElementById(previewId);
    if (!preview) return;
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            preview.src = e.target.result;
            preview.classList.remove('d-none');
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
@endpush
@endonce
