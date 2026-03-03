@props([
    'variant' => 'info',
    'dismissible' => true,
    'icon' => true,
])

@php
$variants = [
    'success' => [
        'container' => 'bg-success-50 border-success-200 text-success-800',
        'icon' => 'check-circle',
        'iconColor' => 'text-success-500',
        'button' => 'text-success-500 hover:bg-success-100',
    ],
    'warning' => [
        'container' => 'bg-warning-50 border-warning-200 text-warning-800',
        'icon' => 'exclamation-triangle',
        'iconColor' => 'text-warning-500',
        'button' => 'text-warning-500 hover:bg-warning-100',
    ],
    'danger' => [
        'container' => 'bg-danger-50 border-danger-200 text-danger-800',
        'icon' => 'exclamation-circle',
        'iconColor' => 'text-danger-500',
        'button' => 'text-danger-500 hover:bg-danger-100',
    ],
    'info' => [
        'container' => 'bg-info-50 border-info-200 text-info-800',
        'icon' => 'information-circle',
        'iconColor' => 'text-info-500',
        'button' => 'text-info-500 hover:bg-info-100',
    ],
];

$config = $variants[$variant];
@endphp

<div
    x-data="{ show: true }"
    x-show="show"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 transform scale-95"
    x-transition:enter-end="opacity-100 transform scale-100"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100 transform scale-100"
    x-transition:leave-end="opacity-0 transform scale-95"
    {{ $attributes->merge([
        'class' => "relative flex items-start p-4 border rounded-lg {$config['container']}"
    ]) }}
>
    @if($icon)
        <div class="flex-shrink-0">
            <x-icon :name="$config['icon']" class="h-5 w-5 {{ $config['iconColor'] }}" />
        </div>
    @endif

    <div class="flex-1 {{ $icon ? 'ml-3' : '' }} {{ $dismissible ? 'mr-7' : '' }}">
        {{ $slot }}
    </div>

    @if($dismissible)
        <button
            @click="show = false"
            class="absolute top-4 right-4 inline-flex rounded-md p-1.5 focus:outline-none focus:ring-2 focus:ring-offset-2 transition-colors {{ $config['button'] }}"
        >
            <span class="sr-only">Dismiss</span>
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    @endif
</div>
