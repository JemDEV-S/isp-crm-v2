@extends('layouts.app')

@section('title', 'Editar Almacén')

@section('breadcrumb')
    <span class="text-secondary-500">Inventario</span>
    <svg class="w-4 h-4 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
    <a href="{{ route('inventory.warehouses.index') }}" class="text-secondary-500 hover:text-secondary-700">Almacenes</a>
    <svg class="w-4 h-4 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
    <span class="text-secondary-900 font-medium">Editar Almacén</span>
@endsection

@section('content')
    <div class="max-w-3xl mx-auto">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-secondary-900">Editar Almacén</h1>
            <p class="mt-1 text-sm text-secondary-500">Modifique la información del almacén</p>
        </div>

        <form action="{{ route('inventory.warehouses.update', $warehouse) }}" method="POST">
            @csrf
            @method('PUT')
            <x-card title="Información Básica" class="mb-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <x-input name="code" label="Código" :value="old('code', $warehouse->code)" :error="$errors->first('code')" required />
                    <x-input name="name" label="Nombre" :value="old('name', $warehouse->name)" :error="$errors->first('name')" required />
                    <div class="md:col-span-2">
                        <x-select name="type" label="Tipo" :error="$errors->first('type')" required>
                            @foreach($types as $type)
                                <option value="{{ $type->value }}" {{ old('type', $warehouse->type->value) == $type->value ? 'selected' : '' }}>{{ $type->label() }}</option>
                            @endforeach
                        </x-select>
                    </div>
                    <div class="md:col-span-2">
                        <label for="address" class="block text-sm font-medium text-secondary-700 mb-1">Dirección</label>
                        <textarea name="address" id="address" rows="2" class="block w-full rounded-lg shadow-sm border-secondary-300 focus:border-primary-500 focus:ring-primary-500">{{ old('address', $warehouse->address) }}</textarea>
                    </div>
                </div>
            </x-card>

            <x-card title="Asignaciones" class="mb-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <x-select name="zone_id" label="Zona">
                        <option value="">Sin zona específica</option>
                        @foreach($zones as $zone)
                            <option value="{{ $zone->id }}" {{ old('zone_id', $warehouse->zone_id) == $zone->id ? 'selected' : '' }}>{{ $zone->name }}</option>
                        @endforeach
                    </x-select>
                    <x-select name="user_id" label="Técnico Asignado">
                        <option value="">Sin técnico</option>
                        @foreach($technicians as $tech)
                            <option value="{{ $tech->id }}" {{ old('user_id', $warehouse->user_id) == $tech->id ? 'selected' : '' }}>{{ $tech->name }}</option>
                        @endforeach
                    </x-select>
                    <x-input name="contact_name" label="Nombre de Contacto" :value="old('contact_name', $warehouse->contact_name)" />
                    <x-input name="contact_phone" label="Teléfono de Contacto" :value="old('contact_phone', $warehouse->contact_phone)" />
                </div>
            </x-card>

            <x-card title="Estado" class="mb-6">
                <label class="inline-flex items-center">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $warehouse->is_active) ? 'checked' : '' }} class="rounded border-secondary-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    <span class="ml-2 text-sm text-secondary-700">Almacén activo</span>
                </label>
            </x-card>

            <div class="flex justify-end gap-3">
                <a href="{{ route('inventory.warehouses.show', $warehouse) }}"><x-button variant="ghost" type="button">Cancelar</x-button></a>
                <x-button type="submit" icon="check">Guardar Cambios</x-button>
            </div>
        </form>
    </div>
@endsection
