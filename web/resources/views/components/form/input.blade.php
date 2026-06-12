@props([
    'label'       => '',
    'name'        => '',
    'type'        => 'text',
    'value'       => '',
    'placeholder' => '',
    'required'    => false,
    'hint'        => null,
])
{{--
    Usage:
    <x-form.input
        label="Nama Masjid"
        name="name"
        :value="old('name', $mosque->name)"
        placeholder="Masukkan nama masjid"
        required
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

    <input
        type="{{ $type }}"
        id="{{ $name }}"
        name="{{ $name }}"
        value="{{ old($name, $value) }}"
        placeholder="{{ $placeholder }}"
        {{ $required ? 'required' : '' }}
        {{ $attributes->merge(['class' => 'form-control' . ($errors->has($name) ? ' is-invalid' : '')]) }}
    >

    @if ($hint && !$errors->has($name))
        <div class="form-text text-muted">{{ $hint }}</div>
    @endif

    @error($name)
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
