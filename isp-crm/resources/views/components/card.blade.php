@props([
    'title' => null,
    'subtitle' => null,
    'padding' => true,
    'footer' => null,
])

<div {{ $attributes->merge(['class' => 'bg-white rounded-lg shadow-soft border border-secondary-200 overflow-hidden']) }}>
    @if($title || isset($header))
        <div class="px-6 py-4 border-b border-secondary-200">
            @isset($header)
                {{ $header }}
            @else
                <div class="flex items-center justify-between">
                    <div>
                        @if($title)
                            <h3 class="text-lg font-semibold text-secondary-900">{{ $title }}</h3>
                        @endif
                        @if($subtitle)
                            <p class="mt-1 text-sm text-secondary-500">{{ $subtitle }}</p>
                        @endif
                    </div>
                    @isset($actions)
                        <div class="flex items-center space-x-2">
                            {{ $actions }}
                        </div>
                    @endisset
                </div>
            @endisset
        </div>
    @endif

    <div class="{{ $padding ? 'p-6' : '' }}">
        {{ $slot }}
    </div>

    @if($footer || isset($footerSlot))
        <div class="px-6 py-4 bg-secondary-50 border-t border-secondary-200">
            {{ $footer ?? $footerSlot }}
        </div>
    @endif
</div>
