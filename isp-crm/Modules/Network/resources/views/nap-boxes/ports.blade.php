@extends('layouts.app')

@section('title', 'Puertos de Caja NAP')

@section('breadcrumb')
    <span class="text-secondary-500">Red</span>
    <x-icon name="chevron-right" class="w-4 h-4 text-secondary-400" />
    <a href="{{ route('network.nap-boxes.index') }}" class="text-secondary-500 hover:text-secondary-700">
        Cajas NAP
    </a>
    <x-icon name="chevron-right" class="w-4 h-4 text-secondary-400" />
    <a href="{{ route('network.nap-boxes.show', $napBox) }}" class="text-secondary-500 hover:text-secondary-700">
        {{ $napBox->code }}
    </a>
    <x-icon name="chevron-right" class="w-4 h-4 text-secondary-400" />
    <span class="text-secondary-900 font-medium">Puertos</span>
@endsection

@section('content')
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-secondary-900">Puertos: {{ $napBox->name }}</h1>
                <p class="mt-1 text-sm text-secondary-500">Gestiona la asignación de puertos de fibra</p>
            </div>
            <a href="{{ route('network.nap-boxes.show', $napBox) }}">
                <x-button variant="ghost" icon="arrow-left">Volver</x-button>
            </a>
        </div>

        <!-- Leyenda -->
        <x-card>
            <div class="flex flex-wrap gap-4">
                <div class="flex items-center gap-2">
                    <div class="w-6 h-6 rounded border-2 border-success-300 bg-success-50"></div>
                    <span class="text-sm text-secondary-700">Libre</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-6 h-6 rounded border-2 border-warning-300 bg-warning-50"></div>
                    <span class="text-sm text-secondary-700">Ocupado</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-6 h-6 rounded border-2 border-info-300 bg-info-50"></div>
                    <span class="text-sm text-secondary-700">Reservado</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-6 h-6 rounded border-2 border-danger-300 bg-danger-50"></div>
                    <span class="text-sm text-secondary-700">Dañado</span>
                </div>
            </div>
        </x-card>

        <!-- Tabla de Puertos -->
        <x-card :padding="false">
            <x-table>
                <x-slot name="header">
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-secondary-700 uppercase tracking-wider">
                        Puerto
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-secondary-700 uppercase tracking-wider">
                        Estado
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-secondary-700 uppercase tracking-wider">
                        Suscripción
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-secondary-700 uppercase tracking-wider">
                        Etiqueta
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-secondary-700 uppercase tracking-wider">
                        Notas
                    </th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-semibold text-secondary-700 uppercase tracking-wider">
                        Acciones
                    </th>
                </x-slot>

                @foreach($napBox->ports as $port)
                    <tr class="hover:bg-secondary-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center gap-2">
                                <div
                                    class="w-8 h-8 rounded border-2 flex items-center justify-center text-xs font-semibold
                                    @if($port->status === 'free') border-success-300 bg-success-50 text-success-700
                                    @elseif($port->status === 'occupied') border-warning-300 bg-warning-50 text-warning-700
                                    @elseif($port->status === 'reserved') border-info-300 bg-info-50 text-info-700
                                    @else border-danger-300 bg-danger-50 text-danger-700
                                    @endif"
                                >
                                    {{ $port->port_number }}
                                </div>
                                <span class="text-sm font-medium text-secondary-900">Puerto {{ $port->port_number }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($port->status === 'free')
                                <x-badge variant="success">Libre</x-badge>
                            @elseif($port->status === 'occupied')
                                <x-badge variant="warning">Ocupado</x-badge>
                            @elseif($port->status === 'reserved')
                                <x-badge variant="info">Reservado</x-badge>
                            @else
                                <x-badge variant="danger">Dañado</x-badge>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($port->subscription_id)
                                <span class="text-sm text-secondary-900">#{{ $port->subscription_id }}</span>
                            @else
                                <span class="text-sm text-secondary-400">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm text-secondary-900">{{ $port->label ?? '-' }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-secondary-900">{{ Str::limit($port->notes, 40) }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex justify-end items-center gap-2">
                                @if($port->status === 'occupied')
                                    @can('network.napbox.update')
                                        <button
                                            onclick="releasePort({{ $port->id }})"
                                            class="text-warning-600 hover:text-warning-900"
                                            title="Liberar puerto"
                                        >
                                            <x-icon name="x" class="w-5 h-5" />
                                        </button>
                                    @endcan
                                @else
                                    @can('network.napbox.update')
                                        <button
                                            onclick="updatePortStatus({{ $port->id }}, '{{ $port->status }}')"
                                            class="text-primary-600 hover:text-primary-900"
                                            title="Cambiar estado"
                                        >
                                            <x-icon name="pencil" class="w-5 h-5" />
                                        </button>
                                    @endcan
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </x-table>
        </x-card>
    </div>
@endsection

@push('scripts')
<script>
function releasePort(portId) {
    if (!confirm('¿Está seguro de liberar este puerto?')) {
        return;
    }

    fetch(`/network/nap-ports/${portId}/release`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        alert('Error al liberar el puerto');
        console.error(error);
    });
}

function updatePortStatus(portId, currentStatus) {
    const statuses = ['free', 'reserved', 'damaged'];
    const statusLabels = {
        'free': 'Libre',
        'reserved': 'Reservado',
        'damaged': 'Dañado'
    };

    let options = '';
    statuses.forEach(status => {
        const selected = status === currentStatus ? 'selected' : '';
        options += `<option value="${status}" ${selected}>${statusLabels[status]}</option>`;
    });

    const newStatus = prompt('Cambiar estado del puerto:', currentStatus);
    if (!newStatus || !statuses.includes(newStatus)) {
        return;
    }

    fetch(`/network/nap-ports/${portId}/status`, {
        method: 'PATCH',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ status: newStatus })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        alert('Error al actualizar el estado');
        console.error(error);
    });
}
</script>
@endpush
