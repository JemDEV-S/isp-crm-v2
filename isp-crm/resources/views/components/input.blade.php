@props([
    'type' => 'text',
    'name',
    'label' => null,
    'error' => null,
    'hint' => null,
    'required' => false,
    'disabled' => false,
    'icon' => null,
    'iconPosition' => 'left',
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

    <div class="relative">
        @if($icon && $iconPosition === 'left')
            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                <x-icon :name="$icon" class="h-5 w-5 text-secondary-400" />
            </div>
        @endif

        <input
            type="{{ $type }}"
            name="{{ $name }}"
            id="{{ $name }}"
            {{ $disabled ? 'disabled' : '' }}
            {{ $required ? 'required' : '' }}
            {{ $attributes->merge([
                'class' => "block w-full rounded-lg shadow-sm transition-colors duration-150
                           focus:outline-none focus:ring-2 focus:ring-offset-0
                           text-secondary-900 placeholder-secondary-400
                           {$errorClass} {$disabledClass} " .
                           ($icon && $iconPosition === 'left' ? 'pl-10' : '') .
                           ($icon && $iconPosition === 'right' ? 'pr-10' : '')
            ]) }}
        >

        @if($icon && $iconPosition === 'right')
            <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                <x-icon :name="$icon" class="h-5 w-5 text-secondary-400" />
            </div>
        @endif
    </div>

    @if($hint && !$error)
        <p class="mt-1 text-sm text-secondary-500">{{ $hint }}</p>
    @endif

    @if($error)
        <p class="mt-1 text-sm text-danger-600">{{ $error }}</p>
    @endif
</div>
