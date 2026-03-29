@props([
    'latitudeName' => 'latitude',
    'longitudeName' => 'longitude',
    'latitudeLabel' => 'Latitud',
    'longitudeLabel' => 'Longitud',
    'latitudeValue' => null,
    'longitudeValue' => null,
    'latitudeError' => null,
    'longitudeError' => null,
    'readonly' => false,
    'showInputs' => true,
    'height' => '20rem',
    'zoom' => 16,
    'defaultLat' => -12.046374,
    'defaultLng' => -77.042793,
    'help' => 'Haz clic sobre el mapa para fijar latitud y longitud.',
    'showLocateButton' => true,
    'showRecenterButton' => true,
    'xModelLat' => null,
    'xModelLng' => null,
])

<div
    {{ $attributes->class('space-y-4') }}
    x-data="geoPointPicker({
        defaultLat: @js($defaultLat),
        defaultLng: @js($defaultLng),
        zoom: @js($zoom),
        readonly: @js($readonly),
    })"
    x-init="init()"
>
    @if($showInputs)
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div class="w-full">
                <label for="{{ $latitudeName }}" class="mb-1 block text-sm font-medium text-secondary-700">
                    {{ $latitudeLabel }}
                </label>
                <input
                    type="number"
                    step="0.0000001"
                    name="{{ $latitudeName }}"
                    id="{{ $latitudeName }}"
                    value="{{ $latitudeValue }}"
                    x-ref="latInput"
                    @if($xModelLat) x-model="{{ $xModelLat }}" @endif
                    @if($readonly) readonly @endif
                    class="block w-full rounded-lg border {{ $latitudeError ? 'border-danger-300 focus:border-danger-500 focus:ring-danger-500' : 'border-secondary-300 focus:border-primary-500 focus:ring-primary-500' }} bg-white text-secondary-900 shadow-sm transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-offset-0 placeholder-secondary-400 {{ $readonly ? 'cursor-not-allowed bg-secondary-50' : '' }}"
                >
                @if($latitudeError)
                    <p class="mt-1 text-sm text-danger-600">{{ $latitudeError }}</p>
                @endif
            </div>

            <div class="w-full">
                <label for="{{ $longitudeName }}" class="mb-1 block text-sm font-medium text-secondary-700">
                    {{ $longitudeLabel }}
                </label>
                <input
                    type="number"
                    step="0.0000001"
                    name="{{ $longitudeName }}"
                    id="{{ $longitudeName }}"
                    value="{{ $longitudeValue }}"
                    x-ref="lngInput"
                    @if($xModelLng) x-model="{{ $xModelLng }}" @endif
                    @if($readonly) readonly @endif
                    class="block w-full rounded-lg border {{ $longitudeError ? 'border-danger-300 focus:border-danger-500 focus:ring-danger-500' : 'border-secondary-300 focus:border-primary-500 focus:ring-primary-500' }} bg-white text-secondary-900 shadow-sm transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-offset-0 placeholder-secondary-400 {{ $readonly ? 'cursor-not-allowed bg-secondary-50' : '' }}"
                >
                @if($longitudeError)
                    <p class="mt-1 text-sm text-danger-600">{{ $longitudeError }}</p>
                @endif
            </div>
        </div>
    @else
        <input type="hidden" name="{{ $latitudeName }}" value="{{ $latitudeValue }}" x-ref="latInput" @if($xModelLat) x-model="{{ $xModelLat }}" @endif>
        <input type="hidden" name="{{ $longitudeName }}" value="{{ $longitudeValue }}" x-ref="lngInput" @if($xModelLng) x-model="{{ $xModelLng }}" @endif>
    @endif

    <div class="overflow-hidden rounded-xl border border-secondary-200 bg-white">
        <div class="flex flex-col gap-3 border-b border-secondary-200 bg-secondary-50 px-4 py-3 md:flex-row md:items-center md:justify-between">
            <div>
                <p class="text-sm font-semibold text-secondary-900">Mapa interactivo</p>
                <p class="text-xs text-secondary-500">{{ $help }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                @if($showRecenterButton)
                    <x-button type="button" variant="ghost" size="sm" icon="refresh" @click="recenter()">Recentrar</x-button>
                @endif
                @if($showLocateButton && !$readonly)
                    <x-button type="button" variant="secondary" size="sm" icon="location-marker" @click="locateMe()">Usar mi ubicacion</x-button>
                @endif
            </div>
        </div>
        <div x-ref="map" style="height: {{ $height }};" class="w-full"></div>
    </div>
</div>
