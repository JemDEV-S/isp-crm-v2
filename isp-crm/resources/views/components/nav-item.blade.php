@props([
    'href',
    'active' => false,
    'icon' => null,
])

@php
$classes = $active
    ? 'bg-primary-50 border-primary-500 text-primary-700'
    : 'border-transparent text-secondary-600 hover:bg-secondary-50 hover:text-secondary-900';
@endphp

<a
    href="{{ $href }}"
    {{ $attributes->merge([
        'class' => "group flex items-center px-3 py-2 text-sm font-medium rounded-lg border-l-4 transition-all duration-150 {$classes}"
    ]) }}
>
    @if($icon)
        <x-icon
            :name="$icon"
            class="mr-3 h-5 w-5 flex-shrink-0 transition-colors"
            :class="$active ? 'text-primary-600' : 'text-secondary-400 group-hover:text-secondary-600'"
            x-show="sidebarOpen"
        />
    @endif

    <span
        class="flex-1"
        x-show="sidebarOpen"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
    >
        {{ $slot }}
    </span>
</a>
