@props([
    'label'       => '',
    'name'        => '',
    'value'       => '',
    'placeholder' => '',
    'rows'        => 4,
    'required'    => false,
    'hint'        => null,
])

<div class="mb-3">
    @if ($label)
        <label for="{{ $name }}" class="form-label fw-semibold">
            {{ $label }}
            @if ($required)
                <span class="text-danger">*</span>
            @endif
        </label>
    @endif

    <textarea
        id="{{ $name }}"
        name="{{ $name }}"
        rows="{{ $rows }}"
        placeholder="{{ $placeholder }}"
        {{ $required ? 'required' : '' }}
        {{ $attributes->merge(['class' => 'form-control' . ($errors->has($name) ? ' is-invalid' : '')]) }}
    >{{ old($name, $value) }}</textarea>

    @if ($hint && !$errors->has($name))
        <div class="form-text text-muted">{{ $hint }}</div>
    @endif

    @error($name)
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
