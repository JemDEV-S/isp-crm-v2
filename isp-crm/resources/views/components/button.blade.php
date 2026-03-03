@props([
    'variant' => 'primary',
    'size' => 'md',
    'type' => 'button',
    'disabled' => false,
    'loading' => false,
    'icon' => null,
    'iconPosition' => 'left',
])

@php
$variants = [
    'primary' => 'bg-primary-600 hover:bg-primary-700 text-white focus:ring-primary-500 shadow-sm',
    'secondary' => 'bg-secondary-200 hover:bg-secondary-300 text-secondary-800 focus:ring-secondary-500',
    'success' => 'bg-success-600 hover:bg-success-700 text-white focus:ring-success-500 shadow-sm',
    'danger' => 'bg-danger-600 hover:bg-danger-700 text-white focus:ring-danger-500 shadow-sm',
    'warning' => 'bg-warning-500 hover:bg-warning-600 text-white focus:ring-warning-500 shadow-sm',
    'outline' => 'border-2 border-secondary-300 hover:bg-secondary-50 text-secondary-700 focus:ring-secondary-500',
    'ghost' => 'hover:bg-secondary-100 text-secondary-700 focus:ring-secondary-500',
    'link' => 'text-primary-600 hover:text-primary-700 underline-offset-4 hover:underline',
];

$sizes = [
    'xs' => 'px-2 py-1 text-xs gap-1',
    'sm' => 'px-3 py-1.5 text-sm gap-1.5',
    'md' => 'px-4 py-2 text-sm gap-2',
    'lg' => 'px-5 py-2.5 text-base gap-2',
    'xl' => 'px-6 py-3 text-lg gap-2.5',
];

$classes = $variants[$variant] . ' ' . $sizes[$size];
@endphp

<button
    type="{{ $type }}"
    {{ $disabled || $loading ? 'disabled' : '' }}
    {{ $attributes->merge([
        'class' => "inline-flex items-center justify-center font-medium rounded-lg
                   focus:outline-none focus:ring-2 focus:ring-offset-2
                   disabled:opacity-50 disabled:cursor-not-allowed
                   transition-all duration-150 {$classes}"
    ]) }}
>
    @if($loading)
        <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
        </svg>
    @elseif($icon && $iconPosition === 'left')
        <x-icon :name="$icon" class="h-4 w-4" />
    @endif

    {{ $slot }}

    @if($icon && $iconPosition === 'right' && !$loading)
        <x-icon :name="$icon" class="h-4 w-4" />
    @endif
</button>
