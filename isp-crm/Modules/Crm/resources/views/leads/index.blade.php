@extends('layouts.app')

@section('title', 'Prospectos (Leads)')

@section('breadcrumb')
    <span class="text-secondary-500">CRM</span>
    <svg class="w-4 h-4 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
    </svg>
    <span class="text-secondary-900 font-medium">Prospectos</span>
@endsection

@section('content')
    <div class="space-y-6">
        <!-- Header con Título y Botón Crear -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-secondary-900">Prospectos (Leads)</h1>
                <p class="mt-1 text-sm text-secondary-500">Gestión de prospectos comerciales</p>
            </div>
            @can('crm.lead.create')
                <a href="{{ route('crm.leads.create') }}">
                    <x-button icon="plus">
                        Nuevo Prospecto
                    </x-button>
                </a>
            @endcan
        </div>

        <!-- Estadísticas -->
        @if(isset($stats))
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <x-stat-card
                title="Total Leads"
                :value="$stats['total'] ?? 0"
                icon="users"
                color="primary"
            />
            <x-stat-card
                title="Nuevos"
                :value="$stats['new'] ?? 0"
                icon="user-plus"
                color="info"
            />
            <x-stat-card
                title="En Proceso"
                :value="$stats['in_process'] ?? 0"
                icon="clock"
                color="warning"
            />
            <x-stat-card
                title="Convertidos"
                :value="$stats['converted'] ?? 0"
                icon="check-circle"
                color="success"
            />
        </div>
        @endif

        <!-- Filtros -->
        <x-card>
            <form method="GET" action="{{ route('crm.leads.index') }}" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <x-input
                        name="search"
                        label="Buscar"
                        placeholder="Nombre, teléfono, email..."
                        :value="request('search')"
                        icon="search"
                    />

                    <x-select name="status" label="Estado" placeholder="Todos">
                        <option value="">Todos los estados</option>
                        <option value="new" {{ request('status') === 'new' ? 'selected' : '' }}>Nuevo</option>
                        <option value="contacted" {{ request('status') === 'contacted' ? 'selected' : '' }}>Contactado</option>
                        <option value="qualified" {{ request('status') === 'qualified' ? 'selected' : '' }}>Calificado</option>
                        <option value="proposal_sent" {{ request('status') === 'proposal_sent' ? 'selected' : '' }}>Propuesta Enviada</option>
                        <option value="negotiating" {{ request('status') === 'negotiating' ? 'selected' : '' }}>Negociando</option>
                        <option value="won" {{ request('status') === 'won' ? 'selected' : '' }}>Ganado</option>
                        <option value="lost" {{ request('status') === 'lost' ? 'selected' : '' }}>Perdido</option>
                    </x-select>

                    <x-select name="source" label="Fuente" placeholder="Todas">
                        <option value="">Todas las fuentes</option>
                        <option value="walk_in" {{ request('source') === 'walk_in' ? 'selected' : '' }}>Visita Directa</option>
                        <option value="phone" {{ request('source') === 'phone' ? 'selected' : '' }}>Teléfono</option>
                        <option value="website" {{ request('source') === 'website' ? 'selected' : '' }}>Sitio Web</option>
                        <option value="referral" {{ request('source') === 'referral' ? 'selected' : '' }}>Referido</option>
                        <option value="social_media" {{ request('source') === 'social_media' ? 'selected' : '' }}>Redes Sociales</option>
                        <option value="campaign" {{ request('source') === 'campaign' ? 'selected' : '' }}>Campaña</option>
                    </x-select>

                    @if(isset($zones))
                    <x-select name="zone_id" label="Zona" placeholder="Todas">
                        <option value="">Todas las zonas</option>
                        @foreach($zones as $zone)
                            <option value="{{ $zone->id }}" {{ request('zone_id') == $zone->id ? 'selected' : '' }}>
                                {{ $zone->name }}
                            </option>
                        @endforeach
                    </x-select>
                    @endif
                </div>

                <div class="flex justify-end gap-2">
                    <a href="{{ route('crm.leads.index') }}">
                        <x-button variant="ghost" type="button">Limpiar</x-button>
                    </a>
                    <x-button type="submit" variant="secondary" icon="search">Filtrar</x-button>
                </div>
            </form>
        </x-card>

        <!-- Tabla de Datos -->
        <x-card :padding="false">
            <x-table>
                <x-slot name="header">
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-secondary-700 uppercase tracking-wider">
                        Prospecto
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-secondary-700 uppercase tracking-wider">
                        Contacto
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-secondary-700 uppercase tracking-wider">
                        Fuente
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-secondary-700 uppercase tracking-wider">
                        Estado
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-secondary-700 uppercase tracking-wider">
                        Asignado a
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-secondary-700 uppercase tracking-wider">
                        Fecha
                    </th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-semibold text-secondary-700 uppercase tracking-wider">
                        Acciones
                    </th>
                </x-slot>

                @forelse($leads as $lead)
                    <tr class="hover:bg-secondary-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="w-10 h-10 rounded-full bg-primary-100 flex items-center justify-center">
                                    <x-icon name="user" class="w-5 h-5 text-primary-600" />
                                </div>
                                <div class="ml-3">
                                    <div class="text-sm font-medium text-secondary-900">{{ $lead->name }}</div>
                                    @if($lead->document_number)
                                        <div class="text-xs text-secondary-500">{{ $lead->document_type?->label() ?? 'Documento' }}: {{ $lead->document_number }}</div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-secondary-900">{{ $lead->phone }}</div>
                            @if($lead->email)
                                <div class="text-xs text-secondary-500">{{ $lead->email }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <x-badge variant="default" size="sm">
                                {{ $lead->source?->label() ?? 'Sin fuente' }}
                            </x-badge>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @php
                                $statusColors = [
                                    'new' => 'info',
                                    'contacted' => 'primary',
                                    'qualified' => 'warning',
                                    'proposal_sent' => 'warning',
                                    'negotiating' => 'warning',
                                    'won' => 'success',
                                    'lost' => 'danger',
                                ];
                            @endphp
                            <x-badge :variant="$statusColors[$lead->status->value] ?? 'default'" dot>
                                {{ $lead->status->label() }}
                            </x-badge>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-secondary-500">
                            {{ $lead->assignedUser->name ?? 'Sin asignar' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-secondary-500">
                            {{ $lead->created_at->format('d/m/Y') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex justify-end items-center gap-2">
                                <a href="{{ route('crm.leads.show', $lead) }}" class="text-secondary-600 hover:text-secondary-900" title="Ver detalle">
                                    <x-icon name="eye" class="w-5 h-5" />
                                </a>
                                @can('crm.lead.update')
                                    <a href="{{ route('crm.leads.edit', $lead) }}" class="text-primary-600 hover:text-primary-900" title="Editar">
                                        <x-icon name="pencil" class="w-5 h-5" />
                                    </a>
                                @endcan
                                @can('crm.lead.delete')
                                    @if(!$lead->converted_at)
                                        <form action="{{ route('crm.leads.destroy', $lead) }}" method="POST" class="inline"
                                              onsubmit="return confirm('¿Estás seguro de eliminar este prospecto?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-danger-600 hover:text-danger-900" title="Eliminar">
                                                <x-icon name="trash" class="w-5 h-5" />
                                            </button>
                                        </form>
                                    @endif
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center">
                                <x-icon name="users" class="w-12 h-12 text-secondary-300" />
                                <h3 class="mt-2 text-sm font-medium text-secondary-900">No hay prospectos</h3>
                                <p class="mt-1 text-sm text-secondary-500">Comienza creando un nuevo prospecto.</p>
                                @can('crm.lead.create')
                                    <div class="mt-4">
                                        <a href="{{ route('crm.leads.create') }}">
                                            <x-button icon="plus">Nuevo Prospecto</x-button>
                                        </a>
                                    </div>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @endforelse
            </x-table>

            <!-- Paginación -->
            @if($leads->hasPages())
                <div class="px-6 py-4 border-t border-secondary-200">
                    {{ $leads->withQueryString()->links() }}
                </div>
            @endif
        </x-card>
    </div>
@endsection
