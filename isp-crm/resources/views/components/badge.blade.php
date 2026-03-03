@props([
    'variant' => 'default',
    'size' => 'md',
    'dot' => false,
    'icon' => null,
])

@php
$variants = [
    'default' => 'bg-secondary-100 text-secondary-800',
    'primary' => 'bg-primary-100 text-primary-800',
    'success' => 'bg-success-100 text-success-800',
    'warning' => 'bg-warning-100 text-warning-800',
    'danger' => 'bg-danger-100 text-danger-800',
    'info' => 'bg-info-100 text-info-800',

    // Status variants
    'active' => 'bg-success-100 text-success-800',
    'inactive' => 'bg-secondary-100 text-secondary-800',
    'pending' => 'bg-warning-100 text-warning-800',
    'cancelled' => 'bg-danger-100 text-danger-800',
];

$sizes = [
    'sm' => 'px-2 py-0.5 text-xs gap-1',
    'md' => 'px-2.5 py-1 text-xs gap-1.5',
    'lg' => 'px-3 py-1 text-sm gap-1.5',
];

$dotColors = [
    'default' => 'bg-secondary-600',
    'primary' => 'bg-primary-600',
    'success' => 'bg-success-600',
    'warning' => 'bg-warning-600',
    'danger' => 'bg-danger-600',
    'info' => 'bg-info-600',
    'active' => 'bg-success-600',
    'inactive' => 'bg-secondary-600',
    'pending' => 'bg-warning-600',
    'cancelled' => 'bg-danger-600',
];
@endphp

<span {{ $attributes->merge([
    'class' => "inline-flex items-center font-medium rounded-full {$variants[$variant]} {$sizes[$size]}"
]) }}>
    @if($dot)
        <span class="w-1.5 h-1.5 rounded-full {{ $dotColors[$variant] }}"></span>
    @endif

    @if($icon)
        <x-icon :name="$icon" class="h-3 w-3" />
    @endif

    {{ $slot }}
</span>
