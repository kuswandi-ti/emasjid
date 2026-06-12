@props([
    'label'       => '',
    'name'        => '',
    'options'     => [],   // ['value' => 'label'] or [['value' => '', 'label' => '']]
    'selected'    => '',
    'placeholder' => '— Pilih —',
    'required'    => false,
    'hint'        => null,
])
{{--
    Usage:
    <x-form.select
        label="Status"
        name="status"
        :options="['active' => 'Aktif', 'inactive' => 'Nonaktif']"
        :selected="old('status', $mosque->status)"
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

    <select
        id="{{ $name }}"
        name="{{ $name }}"
        {{ $required ? 'required' : '' }}
        {{ $attributes->merge(['class' => 'form-select' . ($errors->has($name) ? ' is-invalid' : '')]) }}
    >
        @if ($placeholder)
            <option value="">{{ $placeholder }}</option>
        @endif

        @foreach ($options as $optValue => $optLabel)
            @if (is_array($optLabel))
                {{-- Support array of ['value' => '', 'label' => ''] --}}
                <option value="{{ $optLabel['value'] }}"
                    {{ old($name, $selected) == $optLabel['value'] ? 'selected' : '' }}>
                    {{ $optLabel['label'] }}
                </option>
            @else
                <option value="{{ $optValue }}"
                    {{ old($name, $selected) == $optValue ? 'selected' : '' }}>
                    {{ $optLabel }}
                </option>
            @endif
        @endforeach
    </select>

    @if ($hint && !$errors->has($name))
        <div class="form-text text-muted">{{ $hint }}</div>
    @endif

    @error($name)
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
