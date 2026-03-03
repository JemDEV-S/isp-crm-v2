@props([
    'label',
    'icon' => null,
    'open' => false,
])

<div x-data="{ open: @js($open) }">
    <button
        @click="open = !open"
        class="w-full group flex items-center px-3 py-2 text-sm font-medium text-secondary-600 rounded-lg hover:bg-secondary-50 hover:text-secondary-900 transition-all duration-150"
        x-bind:class="{ 'bg-secondary-50 text-secondary-900': open }"
    >
        @if($icon)
            <x-icon
                :name="$icon"
                class="mr-3 h-5 w-5 flex-shrink-0 text-secondary-400 group-hover:text-secondary-600 transition-colors"
            />
        @endif

        <span class="flex-1 text-left">{{ $label }}</span>

        <svg
            class="ml-auto h-4 w-4 text-secondary-400 transition-transform duration-200"
            :class="{ 'rotate-90': open }"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
        >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
        </svg>
    </button>

    <div
        x-show="open"
        x-collapse
        class="ml-4 mt-1 space-y-1"
    >
        {{ $slot }}
    </div>
</div>
