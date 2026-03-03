@props([
    'name',
    'label' => null,
    'error' => null,
    'hint' => null,
    'required' => false,
    'disabled' => false,
    'placeholder' => null,
    'options' => [],
])

@php
$errorClass = $error ? 'border-danger-300 focus:border-danger-500 focus:ring-danger-500' : 'border-secondary-300 focus:border-primary-500 focus:ring-primary-500';
$disabledClass = $disabled ? 'bg-secondary-50 cursor-not-allowed' : 'bg-white';
@endphp

<div class="w-full">
    @if($label)
        <label for="{{ $name }}" class="block text-sm font-medium text-secondary-700 mb-1">
            {{ $label }}
            @if($required)
                <span class="text-danger-500">*</span>
            @endif
        </label>
    @endif

    <select
        name="{{ $name }}"
        id="{{ $name }}"
        {{ $disabled ? 'disabled' : '' }}
        {{ $required ? 'required' : '' }}
        {{ $attributes->merge([
            'class' => "block w-full rounded-lg shadow-sm transition-colors duration-150
                       focus:outline-none focus:ring-2 focus:ring-offset-0
                       text-secondary-900
                       {$errorClass} {$disabledClass}"
        ]) }}
    >
        @if($placeholder)
            <option value="">{{ $placeholder }}</option>
        @endif

        @if(count($options) > 0)
            @foreach($options as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        @else
            {{ $slot }}
        @endif
    </select>

    @if($hint && !$error)
        <p class="mt-1 text-sm text-secondary-500">{{ $hint }}</p>
    @endif

    @if($error)
        <p class="mt-1 text-sm text-danger-600">{{ $error }}</p>
    @endif
</div>
