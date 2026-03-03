@props([
    'title',
    'value',
    'icon' => null,
    'variant' => 'primary',
    'trend' => null,
    'trendUp' => null,
])

@php
$variants = [
    'primary' => 'bg-primary-500',
    'success' => 'bg-success-500',
    'warning' => 'bg-warning-500',
    'danger' => 'bg-danger-500',
    'info' => 'bg-info-500',
    'secondary' => 'bg-secondary-500',
];

$iconBg = $variants[$variant];
@endphp

<div {{ $attributes->merge(['class' => 'bg-white rounded-lg shadow-soft border border-secondary-200 p-6']) }}>
    <div class="flex items-center justify-between">
        <div class="flex-1">
            <p class="text-sm font-medium text-secondary-600 mb-1">{{ $title }}</p>
            <p class="text-3xl font-bold text-secondary-900">{{ $value }}</p>

            @if($trend !== null)
                <div class="flex items-center mt-2">
                    @if($trendUp)
                        <svg class="w-4 h-4 text-success-500 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                        </svg>
                    @elseif($trendUp === false)
                        <svg class="w-4 h-4 text-danger-500 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                        </svg>
                    @endif
                    <span class="text-sm font-medium {{ $trendUp ? 'text-success-600' : ($trendUp === false ? 'text-danger-600' : 'text-secondary-600') }}">
                        {{ $trend }}
                    </span>
                </div>
            @endif
        </div>

        @if($icon)
            <div class="flex-shrink-0">
                <div class="w-12 h-12 rounded-lg {{ $iconBg }} bg-opacity-10 flex items-center justify-center">
                    <x-icon :name="$icon" class="h-6 w-6 {{ str_replace('bg-', 'text-', $iconBg) }}" />
                </div>
            </div>
        @endif
    </div>

    @isset($footer)
        <div class="mt-4 pt-4 border-t border-secondary-200">
            {{ $footer }}
        </div>
    @endisset
</div>
