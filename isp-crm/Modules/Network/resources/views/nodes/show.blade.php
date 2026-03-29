@extends('layouts.app')

@section('title', $node->name)

@section('breadcrumb')
    <span class="text-secondary-500">Red</span>
    <x-icon name="chevron-right" class="w-4 h-4 text-secondary-400" />
    <a href="{{ route('network.nodes.index') }}" class="text-secondary-500 hover:text-secondary-700">Nodos</a>
    <x-icon name="chevron-right" class="w-4 h-4 text-secondary-400" />
    <span class="text-secondary-900 font-medium">{{ $node->code }}</span>
@endsection

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-secondary-900">{{ $node->name }}</h1>
                <div class="flex items-center gap-2 mt-1">
                    <x-badge :variant="$node->status->value === 'active' ? 'success' : ($node->status->value === 'maintenance' ? 'warning' : 'danger')">
                        {{ $node->type->label() }} - {{ $node->status->label() }}
                    </x-badge>
                    <span class="text-sm text-secondary-500 flex items-center gap-1">
                        <x-icon name="location-marker" class="w-4 h-4" /> {{ $node->address }}
                    </span>
                </div>
            </div>
            <div class="flex gap-2">
                @can('network.node.update')
                    <a href="{{ route('network.nodes.edit', $node) }}">
                        <x-button variant="secondary" icon="pencil">Editar Nodo</x-button>
                    </a>
                @endcan
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">

                <x-card title="Dispositivos de Red">
                    <x-slot name="header_action">
                        <a href="{{ route('network.devices.create', ['node_id' => $node->id]) }}" class="text-sm text-primary-600 hover:underline">
                            + Agregar Equipo
                        </a>
                    </x-slot>

                    <x-table>
                        <x-slot name="head">
                            <x-table.heading>Equipo</x-table.heading>
                            <x-table.heading>IP Gestión</x-table.heading>
                            <x-table.heading>Estado</x-table.heading>
                            <x-table.heading></x-table.heading>
                        </x-slot>
                        <x-slot name="body">
                            @forelse($node->devices as $device)
                                <x-table.row>
                                    <x-table.cell>
                                        <div class="font-medium">{{ $device->name }}</div>
                                        <div class="text-xs text-secondary-500">{{ $device->brand }} {{ $device->model }}</div>
                                    </x-table.cell>
                                    <x-table.cell class="font-mono text-sm">{{ $device->ip_address }}</x-table.cell>
                                    <x-table.cell>
                                        <x-badge :variant="$device->status === 'active' ? 'success' : 'danger'" dot>
                                            {{ $device->status }}
                                        </x-badge>
                                    </x-table.cell>
                                    <x-table.cell class="text-right">
                                        <a href="{{ route('network.devices.show', $device) }}" class="text-secondary-400 hover:text-primary-600">
                                            <x-icon name="eye" class="w-5 h-5" />
                                        </a>
                                    </x-table.cell>
                                </x-table.row>
                            @empty
                                <x-table.empty-state colspan="4" message="No hay dispositivos instalados." />
                            @endforelse
                        </x-slot>
                    </x-table>
                </x-card>

                <x-card title="Distribución Óptica (NAP)">
                    <x-slot name="header_action">
                        <a href="{{ route('network.nap-boxes.create', ['node_id' => $node->id]) }}" class="text-sm text-primary-600 hover:underline">
                            + Agregar NAP
                        </a>
                    </x-slot>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @forelse($node->napBoxes as $nap)
                            <div class="border rounded-lg p-4 hover:bg-gray-50 transition">
                                <div class="flex justify-between items-start mb-2">
                                    <div>
                                        <h4 class="font-bold text-secondary-900">{{ $nap->code }}</h4>
                                        <p class="text-xs text-secondary-500">{{ $nap->address }}</p>
                                    </div>
                                    <a href="{{ route('network.nap-boxes.show', $nap) }}">
                                        <x-icon name="external-link" class="w-4 h-4 text-secondary-400" />
                                    </a>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2.5 mt-2">
                                    @php $percent = ($nap->occupied_ports / $nap->total_ports) * 100; @endphp
                                    <div class="bg-{{ $percent > 90 ? 'red' : ($percent > 50 ? 'yellow' : 'green') }}-600 h-2.5 rounded-full"
                                         style="width: {{ $percent }}%"></div>
                                </div>
                                <div class="text-xs text-right mt-1 text-secondary-500">
                                    {{ $nap->occupied_ports }}/{{ $nap->total_ports }} Ocupados
                                </div>
                            </div>
                        @empty
                            <div class="col-span-2 text-center py-4 text-secondary-500 text-sm">
                                No hay cajas NAP registradas en este nodo.
                            </div>
                        @endforelse
                    </div>
                </x-card>
            </div>

            <div class="space-y-6">
                @if($node->latitude && $node->longitude)
                    <x-card title="Ubicacion">
                        <x-geo-point-picker
                            latitude-name="latitude"
                            longitude-name="longitude"
                            :latitude-value="$node->latitude"
                            :longitude-value="$node->longitude"
                            help="Vista de la ubicacion registrada para este nodo."
                            height="18rem"
                            :readonly="true"
                            :show-inputs="false"
                        />
                        <div class="mt-3 text-xs text-center text-secondary-600">
                            Lat: {{ $node->latitude }} | Lon: {{ $node->longitude }}
                        </div>
                    </x-card>
                @endif

                <x-card title="Detalles Técnicos">
                    <dl class="space-y-3 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-secondary-500">Altitud</dt>
                            <dd class="font-medium">{{ $node->altitude ?? '-' }} msnm</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-secondary-500">Puesta en marcha</dt>
                            <dd class="font-medium">{{ $node->commissioned_at?->format('d/m/Y') ?? '-' }}</dd>
                        </div>
                    </dl>
                    @if($node->description)
                        <div class="mt-4 pt-4 border-t text-sm text-secondary-600">
                            {{ $node->description }}
                        </div>
                    @endif
                </x-card>
            </div>
        </div>
    </div>
@endsection
