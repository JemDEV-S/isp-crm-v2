@extends('layouts.app')

@section('title', $device->name)

@section('breadcrumb')
    <span class="text-secondary-500">Red</span>
    <x-icon name="chevron-right" class="w-4 h-4 text-secondary-400" />
    <a href="{{ route('network.devices.index') }}" class="text-secondary-500 hover:text-secondary-700">Dispositivos</a>
    <x-icon name="chevron-right" class="w-4 h-4 text-secondary-400" />
    <span class="text-secondary-900 font-medium">{{ $device->name }}</span>
@endsection

@section('content')
    <div class="space-y-6">
        <div class="bg-white p-6 rounded-lg shadow-sm border border-secondary-200">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div class="flex items-center gap-4">
                    <div class="p-3 rounded-full {{ $device->status === 'active' ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-600' }}">
                        <x-icon name="server" class="w-8 h-8" />
                    </div>
                    <div>
                        <h1 class="text-xl font-bold text-secondary-900">{{ $device->name }}</h1>
                        <div class="flex items-center gap-3 text-sm text-secondary-500 mt-1">
                            <span class="font-mono bg-gray-100 px-2 py-0.5 rounded">{{ $device->ip_address }}</span>
                            <span>•</span>
                            <span>{{ $device->brand }} {{ $device->model }}</span>
                            <span>•</span>
                            <a href="{{ route('network.nodes.show', $device->node_id) }}" class="hover:text-primary-600 hover:underline">
                                {{ $device->node->name }}
                            </a>
                        </div>
                    </div>
                </div>

                <div class="flex gap-2">
                    <x-button variant="outline" size="sm" icon="refresh">Ping Check</x-button>
                    <a href="{{ route('network.devices.edit', $device) }}">
                        <x-button variant="secondary" size="sm" icon="pencil">Configurar</x-button>
                    </a>
                </div>
            </div>
        </div>

        <x-card title="Interfaces & Puertos">
            <x-table>
                <x-slot name="head">
                    <x-table.heading>Interface</x-table.heading>
                    <x-table.heading>Tipo</x-table.heading>
                    <x-table.heading>Conexión</x-table.heading>
                    <x-table.heading>Estado</x-table.heading>
                    <x-table.heading class="text-right">Acciones</x-table.heading>
                </x-slot>

                <x-slot name="body">
                    @forelse($device->ports as $port)
                        <x-table.row>
                            <x-table.cell class="font-medium">{{ $port->name }}</x-table.cell>
                            <x-table.cell>{{ $port->type }}</x-table.cell>
                            <x-table.cell>
                                @if($port->connected_device_id)
                                    <span class="text-xs bg-blue-50 text-blue-700 px-2 py-1 rounded-full">
                                        Link: {{ $port->connectedDevice->name }}
                                    </span>
                                @else
                                    <span class="text-secondary-400">-</span>
                                @endif
                            </x-table.cell>
                            <x-table.cell>
                                <span class="inline-flex w-2.5 h-2.5 rounded-full {{ $port->status === 'up' ? 'bg-green-500' : 'bg-gray-300' }}" title="{{ $port->status }}"></span>
                            </x-table.cell>
                            <x-table.cell class="text-right">
                                <button class="text-secondary-400 hover:text-secondary-600">
                                    <x-icon name="cog" class="w-4 h-4" />
                                </button>
                            </x-table.cell>
                        </x-table.row>
                    @empty
                        <x-table.empty-state colspan="5" message="No se han sincronizado puertos." />
                    @endforelse
                </x-slot>
            </x-table>
        </x-card>
    </div>
@endsection
