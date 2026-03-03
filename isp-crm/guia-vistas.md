# Guía de Desarrollo de Vistas - NORETEL ISP CRM

## Índice
1. [Introducción](#introducción)
2. [Estructura de Archivos](#estructura-de-archivos)
3. [Layout Principal](#layout-principal)
4. [Componentes Reutilizables](#componentes-reutilizables)
5. [Patrones de Vistas CRUD](#patrones-de-vistas-crud)
6. [Ejemplos Prácticos](#ejemplos-prácticos)
7. [Mejores Prácticas](#mejores-prácticas)

---

## Introducción

Este documento describe la estructura, patrones y componentes reutilizables para crear vistas consistentes en el sistema NORETEL ISP CRM. Todas las vistas siguen un diseño modular basado en TailwindCSS y Alpine.js.

### Stack Tecnológico
- **Laravel Blade**: Motor de plantillas
- **TailwindCSS**: Framework CSS utility-first
- **Alpine.js**: Framework JavaScript reactivo
- **Componentes Blade**: Sistema de componentes reutilizables

---

## Estructura de Archivos

### Ubicación de Vistas por Módulo

```
Modules/
└── [NombreModulo]/
    └── resources/
        └── views/
            ├── [entidad]/
            │   ├── index.blade.php    # Listado
            │   ├── create.blade.php   # Crear
            │   ├── edit.blade.php     # Editar
            │   └── show.blade.php     # Detalle
            └── layouts/
                └── master.blade.php   # Layout específico (opcional)
```

### Componentes Globales

```
resources/
└── views/
    ├── layouts/
    │   ├── app.blade.php              # Layout principal
    │   └── partials/
    │       └── navigation.blade.php   # Navegación
    └── components/
        ├── button.blade.php           # Botones
        ├── input.blade.php            # Inputs
        ├── select.blade.php           # Selects
        ├── card.blade.php             # Tarjetas
        ├── table.blade.php            # Tablas
        ├── badge.blade.php            # Badges
        ├── alert.blade.php            # Alertas
        ├── modal.blade.php            # Modales
        ├── icon.blade.php             # Iconos SVG
        └── dropdown.blade.php         # Dropdowns
```

---

## Layout Principal

### Estructura del Layout Base (`layouts/app.blade.php`)

Todas las vistas deben extender el layout principal:

```blade
@extends('layouts.app')

@section('title', 'Título de la Página')

@section('breadcrumb')
    <span class="text-secondary-500">Módulo</span>
    <svg class="w-4 h-4 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
    </svg>
    <span class="text-secondary-900 font-medium">Sección</span>
@endsection

@section('content')
    <!-- Contenido de la página -->
@endsection
```

### Secciones Disponibles

| Sección | Descripción | Requerido |
|---------|-------------|-----------|
| `@section('title')` | Título de la página (aparece en tab del navegador) | Sí |
| `@section('breadcrumb')` | Migas de pan en el header | Sí |
| `@section('content')` | Contenido principal | Sí |
| `@section('quick-actions')` | Acciones rápidas en el header | No |
| `@stack('styles')` | Estilos CSS adicionales | No |
| `@stack('scripts')` | Scripts JS adicionales | No |
| `@stack('modals')` | Modales de la página | No |

---

## Componentes Reutilizables

### 1. Card (`x-card`)

Contenedor para agrupar contenido relacionado.

#### Props
```php
[
    'title' => null,        // Título del card
    'subtitle' => null,     // Subtítulo
    'padding' => true,      // Aplicar padding interno
    'footer' => null,       // Contenido del footer
]
```

#### Ejemplos de Uso

**Card Simple:**
```blade
<x-card>
    <p>Contenido del card</p>
</x-card>
```

**Card con Título:**
```blade
<x-card title="Información Personal">
    <div class="grid grid-cols-2 gap-4">
        <!-- Contenido -->
    </div>
</x-card>
```

**Card con Header y Actions Personalizados:**
```blade
<x-card>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h3>Título personalizado</h3>
            <x-button size="sm">Acción</x-button>
        </div>
    </x-slot>

    <!-- Contenido -->
</x-card>
```

**Card sin Padding (para tablas):**
```blade
<x-card :padding="false">
    <x-table>
        <!-- Tabla -->
    </x-table>
</x-card>
```

---

### 2. Button (`x-button`)

Botón con diferentes variantes y tamaños.

#### Props
```php
[
    'variant' => 'primary',     // primary, secondary, success, danger, warning, outline, ghost, link
    'size' => 'md',             // xs, sm, md, lg, xl
    'type' => 'button',         // button, submit, reset
    'disabled' => false,
    'loading' => false,         // Muestra spinner
    'icon' => null,             // Nombre del icono
    'iconPosition' => 'left',   // left, right
]
```

#### Ejemplos de Uso

```blade
<!-- Botón básico -->
<x-button>Guardar</x-button>

<!-- Botón con icono -->
<x-button icon="plus">Nuevo Usuario</x-button>

<!-- Botón de acción -->
<x-button type="submit" variant="success" icon="check">Guardar Cambios</x-button>

<!-- Botón secundario -->
<x-button variant="secondary" icon="search">Filtrar</x-button>

<!-- Botón ghost (sin fondo) -->
<x-button variant="ghost">Cancelar</x-button>

<!-- Botón peligroso -->
<x-button variant="danger" icon="trash">Eliminar</x-button>

<!-- Botón pequeño -->
<x-button size="sm" icon="eye">Ver</x-button>

<!-- Botón con loading -->
<x-button :loading="true">Procesando...</x-button>
```

---

### 3. Input (`x-input`)

Campo de entrada de texto con soporte para validación.

#### Props
```php
[
    'type' => 'text',           // text, email, password, number, tel, url, etc.
    'name' => null,             // Requerido
    'label' => null,
    'error' => null,            // Mensaje de error
    'hint' => null,             // Texto de ayuda
    'required' => false,
    'disabled' => false,
    'icon' => null,             // Icono
    'iconPosition' => 'left',   // left, right
]
```

#### Ejemplos de Uso

```blade
<!-- Input simple -->
<x-input
    name="name"
    label="Nombre completo"
    :value="old('name')"
    required
/>

<!-- Input con icono -->
<x-input
    name="search"
    label="Buscar"
    placeholder="Buscar..."
    icon="search"
    :value="old('search')"
/>

<!-- Input con error -->
<x-input
    name="email"
    type="email"
    label="Correo electrónico"
    :value="old('email')"
    :error="$errors->first('email')"
    required
/>

<!-- Input con hint -->
<x-input
    name="password"
    type="password"
    label="Contraseña"
    hint="Mínimo 8 caracteres"
/>

<!-- Input deshabilitado -->
<x-input
    name="code"
    label="Código"
    :value="$user->code"
    disabled
/>
```

---

### 4. Select (`x-select`)

Selector dropdown.

#### Props
```php
[
    'name' => null,             // Requerido
    'label' => null,
    'error' => null,
    'hint' => null,
    'required' => false,
    'disabled' => false,
    'placeholder' => null,
    'options' => [],            // Array de opciones [value => label]
]
```

#### Ejemplos de Uso

```blade
<!-- Select con slot para opciones -->
<x-select name="zone_id" label="Zona" placeholder="Seleccione una zona">
    @foreach($zones as $zone)
        <option value="{{ $zone->id }}" {{ old('zone_id') == $zone->id ? 'selected' : '' }}>
            {{ $zone->name }}
        </option>
    @endforeach
</x-select>

<!-- Select con array de opciones -->
<x-select
    name="status"
    label="Estado"
    :options="['active' => 'Activo', 'inactive' => 'Inactivo']"
/>

<!-- Select para filtros -->
<x-select name="is_active" label="Estado" placeholder="Todos">
    <option value="1" {{ ($filters['is_active'] ?? '') === '1' ? 'selected' : '' }}>Activos</option>
    <option value="0" {{ ($filters['is_active'] ?? '') === '0' ? 'selected' : '' }}>Inactivos</option>
</x-select>
```

---

### 5. Table (`x-table`)

Tabla responsive con estilos consistentes.

#### Props
```php
[
    'headers' => [],            // Array de headers (opcional si usa slot)
    'hoverable' => true,        // Efecto hover en filas
    'striped' => false,         // Filas alternadas
]
```

#### Ejemplos de Uso

```blade
<x-table>
    <x-slot name="header">
        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-secondary-700 uppercase tracking-wider">
            Nombre
        </th>
        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-secondary-700 uppercase tracking-wider">
            Email
        </th>
        <th scope="col" class="px-6 py-3 text-right text-xs font-semibold text-secondary-700 uppercase tracking-wider">
            Acciones
        </th>
    </x-slot>

    @forelse($users as $user)
        <tr class="hover:bg-secondary-50">
            <td class="px-6 py-4 whitespace-nowrap text-sm text-secondary-900">
                {{ $user->name }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-secondary-900">
                {{ $user->email }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                <div class="flex justify-end items-center gap-2">
                    <a href="{{ route('users.show', $user) }}">
                        <x-icon name="eye" class="w-5 h-5 text-secondary-600 hover:text-secondary-900" />
                    </a>
                    <a href="{{ route('users.edit', $user) }}">
                        <x-icon name="pencil" class="w-5 h-5 text-primary-600 hover:text-primary-900" />
                    </a>
                </div>
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="3" class="px-6 py-12 text-center">
                <div class="flex flex-col items-center">
                    <x-icon name="users" class="w-12 h-12 text-secondary-300" />
                    <h3 class="mt-2 text-sm font-medium text-secondary-900">No hay usuarios</h3>
                    <p class="mt-1 text-sm text-secondary-500">Comienza creando un nuevo usuario.</p>
                </div>
            </td>
        </tr>
    @endforelse
</x-table>
```

---

### 6. Badge (`x-badge`)

Etiqueta para mostrar estados o categorías.

#### Props
```php
[
    'variant' => 'default',     // default, primary, success, warning, danger, info, active, inactive, pending, cancelled
    'size' => 'md',             // sm, md, lg
    'dot' => false,             // Mostrar punto de color
    'icon' => null,             // Nombre del icono
]
```

#### Ejemplos de Uso

```blade
<!-- Badge simple -->
<x-badge variant="success">Activo</x-badge>

<!-- Badge con dot -->
<x-badge :variant="$user->is_active ? 'success' : 'danger'" dot>
    {{ $user->is_active ? 'Activo' : 'Inactivo' }}
</x-badge>

<!-- Badge pequeño -->
<x-badge variant="primary" size="sm">Admin</x-badge>

<!-- Badge con icono -->
<x-badge variant="warning" icon="clock">Pendiente</x-badge>

<!-- Múltiples badges -->
<div class="flex flex-wrap gap-1">
    @foreach($user->roles as $role)
        <x-badge variant="primary" size="sm">{{ $role->name }}</x-badge>
    @endforeach
</div>
```

---

### 7. Alert (`x-alert`)

Mensaje de alerta/notificación.

#### Props
```php
[
    'variant' => 'info',        // success, warning, danger, info
    'dismissible' => true,      // Puede cerrarse
    'icon' => true,             // Mostrar icono
]
```

#### Ejemplos de Uso

```blade
<!-- Alert de éxito -->
<x-alert variant="success">
    El usuario ha sido creado exitosamente.
</x-alert>

<!-- Alert de error -->
<x-alert variant="danger">
    <ul class="list-disc list-inside">
        @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
</x-alert>

<!-- Alert de advertencia -->
<x-alert variant="warning">
    Esta acción no se puede deshacer.
</x-alert>

<!-- Alert no dismissible -->
<x-alert variant="info" :dismissible="false">
    Información importante.
</x-alert>
```

**En el Layout (automático):**
```blade
<!-- Ya incluido en layouts/app.blade.php -->
@if(session('success'))
    <x-alert variant="success" class="mb-4">
        {{ session('success') }}
    </x-alert>
@endif

@if(session('error'))
    <x-alert variant="danger" class="mb-4">
        {{ session('error') }}
    </x-alert>
@endif
```

---

### 8. Icon (`x-icon`)

Iconos SVG predefinidos.

#### Props
```php
[
    'name' => null,             // Nombre del icono (requerido)
]
```

#### Iconos Disponibles

**Navegación:** `dashboard`, `users`, `signal`, `clipboard`, `currency`, `network`, `package`, `tag`, `chart`, `settings`

**Acciones:** `plus`, `edit`, `trash`, `search`, `filter`, `download`, `upload`, `pencil`, `check`

**Estado:** `check-circle`, `exclamation-circle`, `exclamation-triangle`, `information-circle`

**Varios:** `eye`, `eye-off`, `chevron-left`, `chevron-right`, `chevron-down`, `x`

#### Ejemplos de Uso

```blade
<!-- Icono básico -->
<x-icon name="users" />

<!-- Icono con tamaño personalizado -->
<x-icon name="search" class="w-4 h-4" />

<!-- Icono con color -->
<x-icon name="check-circle" class="w-6 h-6 text-success-500" />
```

---

### 9. Modal (`x-modal`)

Modal/dialog con Alpine.js.

#### Props
```php
[
    'name' => null,             // ID único del modal (requerido)
    'title' => '',
    'maxWidth' => 'md',         // sm, md, lg, xl, 2xl, 3xl, 4xl, 5xl, 6xl, full
    'show' => false,            // Mostrar por defecto
]
```

#### Ejemplos de Uso

```blade
<!-- Definir modal en @stack('modals') -->
@push('modals')
    <x-modal name="delete-confirmation" title="Confirmar Eliminación" maxWidth="sm">
        <p class="text-sm text-secondary-500">
            ¿Estás seguro de que deseas eliminar este elemento? Esta acción no se puede deshacer.
        </p>

        <x-slot name="footer">
            <x-button variant="ghost" @click="$dispatch('close-modal', 'delete-confirmation')">
                Cancelar
            </x-button>
            <x-button variant="danger" icon="trash">
                Eliminar
            </x-button>
        </x-slot>
    </x-modal>
@endpush

<!-- Botón para abrir modal -->
<x-button @click="$dispatch('open-modal', 'delete-confirmation')">
    Abrir Modal
</x-button>
```

---

### 10. Dropdown (`x-dropdown`)

Menú dropdown.

#### Props
```php
[
    'align' => 'right',         // left, right
    'width' => '48',            // Ancho en unidades
]
```

#### Ejemplos de Uso

```blade
<x-dropdown align="right" width="48">
    <x-slot name="trigger">
        <button class="flex items-center gap-2">
            Acciones
            <x-icon name="chevron-down" class="w-4 h-4" />
        </button>
    </x-slot>

    <x-slot name="content">
        <a href="#" class="block px-4 py-2 text-sm text-secondary-700 hover:bg-secondary-50">
            Editar
        </a>
        <a href="#" class="block px-4 py-2 text-sm text-secondary-700 hover:bg-secondary-50">
            Eliminar
        </a>
    </x-slot>
</x-dropdown>
```

---

## Patrones de Vistas CRUD

### Vista Index (Listado)

**Estructura Estándar:**

```blade
@extends('layouts.app')

@section('title', 'Nombre del Recurso')

@section('breadcrumb')
    <span class="text-secondary-500">Módulo</span>
    <svg class="w-4 h-4 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
    </svg>
    <span class="text-secondary-900 font-medium">Recurso</span>
@endsection

@section('content')
    <div class="space-y-6">
        <!-- Header con Título y Botón Crear -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-secondary-900">Título del Recurso</h1>
                <p class="mt-1 text-sm text-secondary-500">Descripción breve</p>
            </div>
            @can('crear-permiso')
                <a href="{{ route('recurso.create') }}">
                    <x-button icon="plus">
                        Nuevo Recurso
                    </x-button>
                </a>
            @endcan
        </div>

        <!-- Filtros (Opcional) -->
        <x-card>
            <form method="GET" action="{{ route('recurso.index') }}" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <x-input
                        name="search"
                        label="Buscar"
                        placeholder="Buscar..."
                        :value="$filters['search'] ?? ''"
                        icon="search"
                    />

                    <x-select name="status" label="Estado" placeholder="Todos">
                        <option value="1" {{ ($filters['status'] ?? '') === '1' ? 'selected' : '' }}>Activos</option>
                        <option value="0" {{ ($filters['status'] ?? '') === '0' ? 'selected' : '' }}>Inactivos</option>
                    </x-select>
                </div>

                <div class="flex justify-end gap-2">
                    <a href="{{ route('recurso.index') }}">
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
                        Columna 1
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-secondary-700 uppercase tracking-wider">
                        Columna 2
                    </th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-semibold text-secondary-700 uppercase tracking-wider">
                        Acciones
                    </th>
                </x-slot>

                @forelse($items as $item)
                    <tr class="hover:bg-secondary-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-secondary-900">{{ $item->name }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <x-badge :variant="$item->is_active ? 'success' : 'danger'" dot>
                                {{ $item->is_active ? 'Activo' : 'Inactivo' }}
                            </x-badge>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex justify-end items-center gap-2">
                                <a href="{{ route('recurso.show', $item) }}" class="text-secondary-600 hover:text-secondary-900">
                                    <x-icon name="eye" class="w-5 h-5" />
                                </a>
                                @can('editar-permiso')
                                    <a href="{{ route('recurso.edit', $item) }}" class="text-primary-600 hover:text-primary-900">
                                        <x-icon name="pencil" class="w-5 h-5" />
                                    </a>
                                @endcan
                                @can('eliminar-permiso')
                                    <form action="{{ route('recurso.destroy', $item) }}" method="POST" class="inline"
                                          onsubmit="return confirm('¿Estás seguro de eliminar este elemento?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-danger-600 hover:text-danger-900">
                                            <x-icon name="trash" class="w-5 h-5" />
                                        </button>
                                    </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center">
                                <x-icon name="inbox" class="w-12 h-12 text-secondary-300" />
                                <h3 class="mt-2 text-sm font-medium text-secondary-900">No hay elementos</h3>
                                <p class="mt-1 text-sm text-secondary-500">Comienza creando un nuevo elemento.</p>
                                @can('crear-permiso')
                                    <div class="mt-4">
                                        <a href="{{ route('recurso.create') }}">
                                            <x-button icon="plus">Nuevo Elemento</x-button>
                                        </a>
                                    </div>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @endforelse
            </x-table>

            <!-- Paginación -->
            @if($items->hasPages())
                <div class="px-6 py-4 border-t border-secondary-200">
                    {{ $items->withQueryString()->links() }}
                </div>
            @endif
        </x-card>
    </div>
@endsection
```

---

### Vista Create (Crear)

**Estructura Estándar:**

```blade
@extends('layouts.app')

@section('title', 'Crear Recurso')

@section('breadcrumb')
    <span class="text-secondary-500">Módulo</span>
    <svg class="w-4 h-4 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
    </svg>
    <a href="{{ route('recurso.index') }}" class="text-secondary-500 hover:text-secondary-700">Recursos</a>
    <svg class="w-4 h-4 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
    </svg>
    <span class="text-secondary-900 font-medium">Crear Recurso</span>
@endsection

@section('content')
    <div class="max-w-3xl mx-auto">
        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-secondary-900">Crear Recurso</h1>
            <p class="mt-1 text-sm text-secondary-500">Complete la información para crear un nuevo recurso</p>
        </div>

        <!-- Formulario -->
        <form action="{{ route('recurso.store') }}" method="POST">
            @csrf

            <!-- Sección 1: Información Básica -->
            <x-card title="Información Básica" class="mb-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <x-input
                        name="name"
                        label="Nombre"
                        :value="old('name')"
                        :error="$errors->first('name')"
                        required
                        placeholder="Ingrese el nombre"
                    />

                    <x-input
                        name="code"
                        label="Código"
                        :value="old('code')"
                        :error="$errors->first('code')"
                        placeholder="Código único"
                    />

                    <div class="md:col-span-2">
                        <label for="description" class="block text-sm font-medium text-secondary-700 mb-1">
                            Descripción
                        </label>
                        <textarea
                            name="description"
                            id="description"
                            rows="3"
                            class="block w-full rounded-lg shadow-sm border-secondary-300 focus:border-primary-500 focus:ring-primary-500"
                        >{{ old('description') }}</textarea>
                        @error('description')
                            <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </x-card>

            <!-- Sección 2: Configuración -->
            <x-card title="Configuración" class="mb-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <x-select
                        name="status"
                        label="Estado"
                        :error="$errors->first('status')"
                        required
                    >
                        <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>Activo</option>
                        <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Inactivo</option>
                    </x-select>

                    <div>
                        <label class="inline-flex items-center mt-7">
                            <input type="checkbox" name="is_featured" value="1" {{ old('is_featured') ? 'checked' : '' }}
                                   class="rounded border-secondary-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                            <span class="ml-2 text-sm text-secondary-700">Destacado</span>
                        </label>
                    </div>
                </div>
            </x-card>

            <!-- Botones de Acción -->
            <div class="flex justify-end gap-3">
                <a href="{{ route('recurso.index') }}">
                    <x-button variant="ghost" type="button">Cancelar</x-button>
                </a>
                <x-button type="submit" icon="check">Crear Recurso</x-button>
            </div>
        </form>
    </div>
@endsection
```

---

### Vista Edit (Editar)

**Estructura Estándar:**

```blade
@extends('layouts.app')

@section('title', 'Editar Recurso')

@section('breadcrumb')
    <span class="text-secondary-500">Módulo</span>
    <svg class="w-4 h-4 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
    </svg>
    <a href="{{ route('recurso.index') }}" class="text-secondary-500 hover:text-secondary-700">Recursos</a>
    <svg class="w-4 h-4 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
    </svg>
    <span class="text-secondary-900 font-medium">Editar Recurso</span>
@endsection

@section('content')
    <div class="max-w-3xl mx-auto">
        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-secondary-900">Editar Recurso</h1>
            <p class="mt-1 text-sm text-secondary-500">Modifique la información del recurso</p>
        </div>

        <!-- Formulario -->
        <form action="{{ route('recurso.update', $item) }}" method="POST">
            @csrf
            @method('PUT')

            <!-- Similar a Create pero con valores del $item -->
            <x-card title="Información Básica" class="mb-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <x-input
                        name="name"
                        label="Nombre"
                        :value="old('name', $item->name)"
                        :error="$errors->first('name')"
                        required
                    />

                    <x-input
                        name="code"
                        label="Código"
                        :value="old('code', $item->code)"
                        :error="$errors->first('code')"
                    />
                </div>
            </x-card>

            <!-- Botones de Acción -->
            <div class="flex justify-end gap-3">
                <a href="{{ route('recurso.index') }}">
                    <x-button variant="ghost" type="button">Cancelar</x-button>
                </a>
                <x-button type="submit" icon="check">Guardar Cambios</x-button>
            </div>
        </form>
    </div>
@endsection
```

---

### Vista Show (Detalle)

**Estructura Estándar:**

```blade
@extends('layouts.app')

@section('title', 'Detalle de Recurso')

@section('breadcrumb')
    <span class="text-secondary-500">Módulo</span>
    <svg class="w-4 h-4 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
    </svg>
    <a href="{{ route('recurso.index') }}" class="text-secondary-500 hover:text-secondary-700">Recursos</a>
    <svg class="w-4 h-4 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
    </svg>
    <span class="text-secondary-900 font-medium">{{ $item->name }}</span>
@endsection

@section('content')
    <div class="space-y-6">
        <!-- Header con Título y Acciones -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-secondary-900">{{ $item->name }}</h1>
                <div class="flex items-center gap-2 mt-1">
                    <x-badge :variant="$item->is_active ? 'success' : 'danger'" dot>
                        {{ $item->is_active ? 'Activo' : 'Inactivo' }}
                    </x-badge>
                </div>
            </div>
            <div class="flex gap-2">
                @can('editar-permiso')
                    <a href="{{ route('recurso.edit', $item) }}">
                        <x-button variant="secondary" icon="pencil">Editar</x-button>
                    </a>
                @endcan
            </div>
        </div>

        <!-- Layout de 2 columnas -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Columna Principal (2/3) -->
            <div class="lg:col-span-2 space-y-6">
                <x-card title="Información General">
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4">
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Nombre</dt>
                            <dd class="mt-1 text-sm text-secondary-900">{{ $item->name }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Código</dt>
                            <dd class="mt-1 text-sm text-secondary-900">{{ $item->code }}</dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-sm font-medium text-secondary-500">Descripción</dt>
                            <dd class="mt-1 text-sm text-secondary-900">{{ $item->description ?? '-' }}</dd>
                        </div>
                    </dl>
                </x-card>
            </div>

            <!-- Sidebar (1/3) -->
            <div class="space-y-6">
                <x-card title="Información del Sistema">
                    <dl class="space-y-4">
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Fecha de creación</dt>
                            <dd class="mt-1 text-sm text-secondary-900">
                                {{ $item->created_at->format('d/m/Y H:i') }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-secondary-500">Última actualización</dt>
                            <dd class="mt-1 text-sm text-secondary-900">
                                {{ $item->updated_at->format('d/m/Y H:i') }}
                            </dd>
                        </div>
                    </dl>
                </x-card>

                <x-card title="Acciones">
                    <div class="space-y-2">
                        @can('editar-permiso')
                            <a href="{{ route('recurso.edit', $item) }}" class="block">
                                <x-button variant="outline" class="w-full" icon="pencil">Editar</x-button>
                            </a>
                        @endcan
                        @can('eliminar-permiso')
                            <form action="{{ route('recurso.destroy', $item) }}" method="POST"
                                  onsubmit="return confirm('¿Estás seguro de eliminar este elemento?')">
                                @csrf
                                @method('DELETE')
                                <x-button type="submit" variant="danger" class="w-full" icon="trash">
                                    Eliminar
                                </x-button>
                            </form>
                        @endcan
                    </div>
                </x-card>
            </div>
        </div>
    </div>
@endsection
```

---

## Ejemplos Prácticos

### Ejemplo 1: Vista Index con Grid (como Roles)

```blade
<!-- En lugar de tabla, usar grid de cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    @forelse($items as $item)
        <x-card class="hover:shadow-md transition-shadow">
            <div class="flex items-start justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-lg bg-primary-100 flex items-center justify-center">
                        <x-icon name="package" class="w-6 h-6 text-primary-600" />
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-secondary-900">{{ $item->name }}</h3>
                        <p class="text-sm text-secondary-500">{{ $item->code }}</p>
                    </div>
                </div>
                <x-badge :variant="$item->is_active ? 'success' : 'danger'" size="sm" dot>
                    {{ $item->is_active ? 'Activo' : 'Inactivo' }}
                </x-badge>
            </div>

            @if($item->description)
                <p class="mt-3 text-sm text-secondary-600">{{ $item->description }}</p>
            @endif

            <div class="mt-4 pt-4 border-t border-secondary-200 flex justify-end gap-2">
                <a href="{{ route('items.show', $item) }}">
                    <x-button variant="ghost" size="sm" icon="eye">Ver</x-button>
                </a>
                <a href="{{ route('items.edit', $item) }}">
                    <x-button variant="secondary" size="sm" icon="pencil">Editar</x-button>
                </a>
            </div>
        </x-card>
    @empty
        <div class="col-span-full">
            <x-card class="text-center py-12">
                <x-icon name="inbox" class="w-12 h-12 text-secondary-300 mx-auto" />
                <h3 class="mt-2 text-sm font-medium text-secondary-900">No hay elementos</h3>
                <p class="mt-1 text-sm text-secondary-500">Comienza creando uno nuevo.</p>
            </x-card>
        </div>
    @endforelse
</div>
```

---

### Ejemplo 2: Formulario con Múltiples Secciones

```blade
<form action="{{ route('recurso.store') }}" method="POST">
    @csrf

    <!-- Sección 1 -->
    <x-card title="Información Personal" class="mb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <x-input name="first_name" label="Nombre" :value="old('first_name')" required />
            <x-input name="last_name" label="Apellido" :value="old('last_name')" required />
            <x-input type="email" name="email" label="Email" :value="old('email')" required />
            <x-input name="phone" label="Teléfono" :value="old('phone')" />
        </div>
    </x-card>

    <!-- Sección 2 -->
    <x-card title="Dirección" class="mb-6">
        <div class="space-y-4">
            <x-input name="address" label="Dirección" :value="old('address')" />
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <x-input name="city" label="Ciudad" :value="old('city')" />
                <x-input name="state" label="Estado" :value="old('state')" />
                <x-input name="zip" label="Código Postal" :value="old('zip')" />
            </div>
        </div>
    </x-card>

    <!-- Sección 3: Checkboxes -->
    <x-card title="Preferencias">
        <div class="space-y-3">
            <label class="flex items-center">
                <input type="checkbox" name="newsletter" value="1" {{ old('newsletter') ? 'checked' : '' }}
                       class="rounded border-secondary-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                <span class="ml-2 text-sm text-secondary-700">Recibir newsletter</span>
            </label>
            <label class="flex items-center">
                <input type="checkbox" name="notifications" value="1" {{ old('notifications') ? 'checked' : '' }}
                       class="rounded border-secondary-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                <span class="ml-2 text-sm text-secondary-700">Recibir notificaciones</span>
            </label>
        </div>
    </x-card>

    <!-- Botones -->
    <div class="mt-6 flex justify-end gap-3">
        <a href="{{ route('recurso.index') }}">
            <x-button variant="ghost" type="button">Cancelar</x-button>
        </a>
        <x-button type="submit" icon="check">Guardar</x-button>
    </div>
</form>
```

---

### Ejemplo 3: Vista con Tabs (Alpine.js)

```blade
<div x-data="{ activeTab: 'general' }">
    <!-- Tab Headers -->
    <div class="border-b border-secondary-200 mb-6">
        <nav class="-mb-px flex space-x-8">
            <button @click="activeTab = 'general'"
                    :class="activeTab === 'general' ? 'border-primary-500 text-primary-600' : 'border-transparent text-secondary-500 hover:text-secondary-700 hover:border-secondary-300'"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                General
            </button>
            <button @click="activeTab = 'advanced'"
                    :class="activeTab === 'advanced' ? 'border-primary-500 text-primary-600' : 'border-transparent text-secondary-500 hover:text-secondary-700 hover:border-secondary-300'"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                Avanzado
            </button>
            <button @click="activeTab = 'settings'"
                    :class="activeTab === 'settings' ? 'border-primary-500 text-primary-600' : 'border-transparent text-secondary-500 hover:text-secondary-700 hover:border-secondary-300'"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                Configuración
            </button>
        </nav>
    </div>

    <!-- Tab Content -->
    <div x-show="activeTab === 'general'">
        <x-card title="Información General">
            <!-- Contenido del tab general -->
        </x-card>
    </div>

    <div x-show="activeTab === 'advanced'" style="display: none;">
        <x-card title="Configuración Avanzada">
            <!-- Contenido del tab avanzado -->
        </x-card>
    </div>

    <div x-show="activeTab === 'settings'" style="display: none;">
        <x-card title="Configuración">
            <!-- Contenido del tab settings -->
        </x-card>
    </div>
</div>
```

---

## Mejores Prácticas

### 1. Consistencia Visual

- **Siempre** usar los componentes predefinidos (`x-card`, `x-button`, etc.)
- Mantener el mismo espaciado: `space-y-6` entre secciones principales
- Usar la paleta de colores definida: `primary`, `secondary`, `success`, `danger`, `warning`, `info`

### 2. Responsividad

```blade
<!-- Grid responsive -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
    <!-- Contenido -->
</div>

<!-- Flex responsive -->
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <!-- Contenido -->
</div>
```

### 3. Validación de Formularios

```blade
<!-- Siempre pasar errores a los inputs -->
<x-input
    name="email"
    label="Email"
    :value="old('email')"
    :error="$errors->first('email')"
    required
/>

<!-- Mostrar errores generales -->
@if($errors->any())
    <x-alert variant="danger" class="mb-4">
        <ul class="list-disc list-inside">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </x-alert>
@endif
```

### 4. Autorización

```blade
<!-- Usar @can para controlar acceso -->
@can('create', App\Models\User::class)
    <a href="{{ route('users.create') }}">
        <x-button icon="plus">Nuevo Usuario</x-button>
    </a>
@endcan

<!-- En tablas -->
@can('update', $item)
    <a href="{{ route('items.edit', $item) }}">
        <x-icon name="pencil" class="w-5 h-5" />
    </a>
@endcan
```

### 5. Estados Vacíos

Siempre mostrar un estado vacío informativo:

```blade
@forelse($items as $item)
    <!-- Contenido -->
@empty
    <div class="text-center py-12">
        <x-icon name="inbox" class="w-12 h-12 text-secondary-300 mx-auto" />
        <h3 class="mt-2 text-sm font-medium text-secondary-900">No hay elementos</h3>
        <p class="mt-1 text-sm text-secondary-500">Comienza creando uno nuevo.</p>
        @can('create')
            <div class="mt-4">
                <a href="{{ route('items.create') }}">
                    <x-button icon="plus">Nuevo Elemento</x-button>
                </a>
            </div>
        @endcan
    </div>
@endforelse
```

### 6. Paginación

```blade
<!-- Al final de la tabla -->
@if($items->hasPages())
    <div class="px-6 py-4 border-t border-secondary-200">
        {{ $items->withQueryString()->links() }}
    </div>
@endif
```

### 7. Confirmaciones

```blade
<!-- Para eliminaciones -->
<form action="{{ route('items.destroy', $item) }}" method="POST" class="inline"
      onsubmit="return confirm('¿Estás seguro de eliminar este elemento?')">
    @csrf
    @method('DELETE')
    <button type="submit" class="text-danger-600 hover:text-danger-900">
        <x-icon name="trash" class="w-5 h-5" />
    </button>
</form>
```

### 8. Breadcrumbs

```blade
@section('breadcrumb')
    <span class="text-secondary-500">Módulo</span>
    <svg class="w-4 h-4 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
    </svg>
    <a href="{{ route('items.index') }}" class="text-secondary-500 hover:text-secondary-700">Items</a>
    <svg class="w-4 h-4 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
    </svg>
    <span class="text-secondary-900 font-medium">{{ $item->name }}</span>
@endsection
```

### 9. Mensajes Flash

En el controlador:
```php
return redirect()->route('items.index')
    ->with('success', 'El elemento ha sido creado exitosamente.');

return redirect()->back()
    ->with('error', 'Ocurrió un error al procesar la solicitud.');
```

Los mensajes se muestran automáticamente en `layouts/app.blade.php`.

### 10. Clases de Utilidad Comunes

```blade
<!-- Espaciado -->
space-y-6        <!-- Espacio vertical entre elementos -->
gap-4            <!-- Espacio en grid/flex -->
mb-6             <!-- Margen inferior -->

<!-- Texto -->
text-2xl font-bold text-secondary-900        <!-- Título principal -->
text-sm text-secondary-500                    <!-- Texto descriptivo -->
text-xs font-semibold uppercase               <!-- Labels de tabla -->

<!-- Colores de texto según variante -->
text-primary-600 hover:text-primary-900
text-secondary-600 hover:text-secondary-900
text-danger-600 hover:text-danger-900
text-success-600 hover:text-success-900

<!-- Tamaños de iconos -->
w-4 h-4          <!-- Pequeño (en botones) -->
w-5 h-5          <!-- Mediano (acciones) -->
w-6 h-6          <!-- Grande -->
w-12 h-12        <!-- Extra grande (estados vacíos) -->
```

---

## Checklist de Desarrollo

Antes de finalizar una vista, verificar:

- [ ] Extiende `layouts.app`
- [ ] Define `@section('title')`
- [ ] Define `@section('breadcrumb')` con navegación correcta
- [ ] Usa componentes reutilizables (`x-card`, `x-button`, etc.)
- [ ] Maneja validación de errores en formularios
- [ ] Implementa autorización con `@can`
- [ ] Muestra estado vacío informativo
- [ ] Incluye paginación si aplica
- [ ] Tiene confirmación en acciones destructivas
- [ ] Es responsive (mobile, tablet, desktop)
- [ ] Sigue la paleta de colores del sistema
- [ ] Usa iconos de `x-icon` predefinidos

---
