@extends('layouts.app')

@section('title', 'Direcciones IP del Pool')

@section('breadcrumb')
    <span class="text-secondary-500">Red</span>
    <x-icon name="chevron-right" class="w-4 h-4 text-secondary-400" />
    <a href="{{ route('network.ip-pools.index') }}" class="text-secondary-500 hover:text-secondary-700">
        Pools de IP
    </a>
    <x-icon name="chevron-right" class="w-4 h-4 text-secondary-400" />
    <a href="{{ route('network.ip-pools.show', $pool) }}" class="text-secondary-500 hover:text-secondary-700">
        {{ $pool->name }}
    </a>
    <x-icon name="chevron-right" class="w-4 h-4 text-secondary-400" />
    <span class="text-secondary-900 font-medium">Direcciones IP</span>
@endsection

@section('content')
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-secondary-900">Direcciones IP: {{ $pool->name }}</h1>
                <p class="mt-1 text-sm text-secondary-500 font-mono">{{ $pool->network_cidr }}</p>
            </div>
            <a href="{{ route('network.ip-pools.show', $pool) }}">
                <x-button variant="ghost" icon="arrow-left">Volver</x-button>
            </a>
        </div>

        <!-- Filtros -->
        <x-card>
            <form method="GET" action="{{ route('network.ip-pools.addresses', $pool) }}" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <x-input
                        name="search"
                        label="Buscar IP"
                        placeholder="Ej: 192.168.1"
                        :value="request('search')"
                        icon="search"
                    />

                    <x-select name="status" label="Estado" placeholder="Todos">
                        <option value="free" {{ request('status') == 'free' ? 'selected' : '' }}>Libre</option>
                        <option value="assigned" {{ request('status') == 'assigned' ? 'selected' : '' }}>Asignada</option>
                        <option value="reserved" {{ request('status') == 'reserved' ? 'selected' : '' }}>Reservada</option>
                        <option value="blacklisted" {{ request('status') == 'blacklisted' ? 'selected' : '' }}>En lista negra</option>
                    </x-select>
                </div>

                <div class="flex justify-end gap-2">
                    <a href="{{ route('network.ip-pools.addresses', $pool) }}">
                        <x-button type="button" variant="ghost">Limpiar</x-button>
                    </a>
                    <x-button type="submit" variant="secondary" icon="search">Filtrar</x-button>
                </div>
            </form>
        </x-card>

        <!-- Leyenda -->
        <x-card>
            <div class="flex flex-wrap gap-4">
                <div class="flex items-center gap-2">
                    <x-badge variant="success">Libre</x-badge>
                    <span class="text-sm text-secondary-700">Disponible para asignar</span>
                </div>
                <div class="flex items-center gap-2">
                    <x-badge variant="warning">Asignada</x-badge>
                    <span class="text-sm text-secondary-700">En uso por un cliente</span>
                </div>
                <div class="flex items-center gap-2">
                    <x-badge variant="info">Reservada</x-badge>
                    <span class="text-sm text-secondary-700">Reservada para uso específico</span>
                </div>
                <div class="flex items-center gap-2">
                    <x-badge variant="danger">Lista Negra</x-badge>
                    <span class="text-sm text-secondary-700">Bloqueada o problemática</span>
                </div>
            </div>
        </x-card>

        <!-- Tabla -->
        <x-card :padding="false">
            <x-table>
                <x-slot name="header">
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-secondary-700 uppercase tracking-wider">
                        Dirección IP
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-secondary-700 uppercase tracking-wider">
                        Estado
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-secondary-700 uppercase tracking-wider">
                        Suscripción
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-secondary-700 uppercase tracking-wider">
                        Fecha Asignación
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-secondary-700 uppercase tracking-wider">
                        Notas
                    </th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-semibold text-secondary-700 uppercase tracking-wider">
                        Acciones
                    </th>
                </x-slot>

                @forelse($addresses as $address)
                    <tr class="hover:bg-secondary-50">
                        <td class="px-6 py-4 whitespace-nowrap font-mono text-sm font-medium text-secondary-900">
                            {{ $address->address }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($address->status === 'free')
                                <x-badge variant="success">Libre</x-badge>
                            @elseif($address->status === 'assigned')
                                <x-badge variant="warning">Asignada</x-badge>
                            @elseif($address->status === 'reserved')
                                <x-badge variant="info">Reservada</x-badge>
                            @else
                                <x-badge variant="danger">Lista Negra</x-badge>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            @if($address->subscription_id)
                                <span class="text-secondary-900">#{{ $address->subscription_id }}</span>
                            @else
                                <span class="text-secondary-400">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-secondary-900">
                            {{ $address->assigned_at?->format('d/m/Y') ?? '-' }}
                        </td>
                        <td class="px-6 py-4 text-sm text-secondary-900">
                            {{ Str::limit($address->notes, 40) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex justify-end items-center gap-2">
                                @if($address->status === 'assigned')
                                    @can('network.ip.assign')
                                        <button
                                            onclick="releaseIp({{ $address->id }})"
                                            class="text-warning-600 hover:text-warning-900"
                                            title="Liberar IP"
                                        >
                                            <x-icon name="x" class="w-5 h-5" />
                                        </button>
                                    @endcan
                                @elseif($address->status === 'free')
                                    @can('network.ip.assign')
                                        <button
                                            onclick="reserveIp({{ $address->id }})"
                                            class="text-info-600 hover:text-info-900"
                                            title="Reservar IP"
                                        >
                                            <x-icon name="bookmark" class="w-5 h-5" />
                                        </button>
                                        <button
                                            onclick="blacklistIp({{ $address->id }})"
                                            class="text-danger-600 hover:text-danger-900"
                                            title="Agregar a lista negra"
                                        >
                                            <x-icon name="ban" class="w-5 h-5" />
                                        </button>
                                    @endcan
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center">
                                <x-icon name="inbox" class="w-12 h-12 text-secondary-300" />
                                <h3 class="mt-2 text-sm font-medium text-secondary-900">No hay direcciones IP</h3>
                                <p class="mt-1 text-sm text-secondary-500">Este pool aún no tiene direcciones generadas.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </x-table>

            @if($addresses->hasPages())
                <div class="px-6 py-4 border-t border-secondary-200">
                    {{ $addresses->withQueryString()->links() }}
                </div>
            @endif
        </x-card>
    </div>
@endsection

@push('scripts')
<script>
function releaseIp(ipId) {
    if (!confirm('¿Está seguro de liberar esta dirección IP?')) {
        return;
    }

    fetch(`/network/ip-addresses/${ipId}/release`, {
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
        alert('Error al liberar la IP');
        console.error(error);
    });
}

function reserveIp(ipId) {
    const notes = prompt('Ingrese notas para la reserva (opcional):');
    if (notes === null) return;

    fetch(`/network/ip-addresses/${ipId}/reserve`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ notes })
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
        alert('Error al reservar la IP');
        console.error(error);
    });
}

function blacklistIp(ipId) {
    const reason = prompt('Ingrese el motivo del bloqueo:');
    if (!reason) return;

    fetch(`/network/ip-addresses/${ipId}/blacklist`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ reason })
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
        alert('Error al agregar a lista negra');
        console.error(error);
    });
}
</script>
@endpush
