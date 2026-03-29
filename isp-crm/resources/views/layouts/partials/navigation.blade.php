{{-- Dashboard --}}
<x-nav-item
    href="{{ route('dashboard') }}"
    :active="request()->routeIs('dashboard')"
    icon="dashboard"
>
    Dashboard
</x-nav-item>

{{-- CRM --}}
@can('crm.lead.view')
<x-nav-group label="CRM" icon="users">
    @can('crm.lead.view')
    <x-nav-item
        href="{{ route('crm.leads.index') }}"
        :active="request()->routeIs('crm.leads.*')"
    >
        Prospectos
    </x-nav-item>
    @endcan

    @can('crm.customer.view')
    <x-nav-item
        href="{{ route('crm.customers.index') }}"
        :active="request()->routeIs('crm.customers.*')"
    >
        Clientes
    </x-nav-item>
    @endcan
</x-nav-group>
@endcan

{{-- Subscriptions --}}
@can('subscription.contract.view')
<x-nav-group label="Servicios" icon="signal">
    <x-nav-item
        href="{{ route('subscriptions.index') }}"
        :active="request()->routeIs('subscriptions.index')"
    >
        Contratos
    </x-nav-item>

    {{-- <x-nav-item
        href="{{ route('subscriptions.active') }}"
        :active="request()->routeIs('subscriptions.active')"
    >
        Servicios Activos
    </x-nav-item>

    <x-nav-item
        href="{{ route('subscriptions.suspended') }}"
        :active="request()->routeIs('subscriptions.suspended')"
    >
        Suspendidos
    </x-nav-item> --}}
</x-nav-group>
@endcan

{{-- Field Operations --}}
{{-- @can('fieldops.workorder.view')
<x-nav-group label="Operaciones" icon="clipboard">
    <x-nav-item
        href="{{ route('fieldops.workorders.index') }}"
        :active="request()->routeIs('fieldops.workorders.*')"
    >
        Órdenes de Trabajo
    </x-nav-item>

    @can('fieldops.workorder.view.own')
    <x-nav-item
        href="{{ route('fieldops.my-tasks') }}"
        :active="request()->routeIs('fieldops.my-tasks')"
    >
        Mis Tareas
    </x-nav-item>
    @endcan

    <x-nav-item
        href="{{ route('fieldops.calendar') }}"
        :active="request()->routeIs('fieldops.calendar')"
    >
        Calendario
    </x-nav-item>
</x-nav-group>
@endcan --}}

{{-- Finance --}}
{{-- @can('finance.invoice.view')
<x-nav-group label="Finanzas" icon="currency">
    <x-nav-item
        href="{{ route('finance.invoices.index') }}"
        :active="request()->routeIs('finance.invoices.*')"
    >
        Facturas
    </x-nav-item>

    @can('finance.payment.view')
    <x-nav-item
        href="{{ route('finance.payments.index') }}"
        :active="request()->routeIs('finance.payments.*')"
    >
        Pagos
    </x-nav-item>
    @endcan

    <x-nav-item
        href="{{ route('finance.overdue') }}"
        :active="request()->routeIs('finance.overdue')"
    >
        Cobranza
    </x-nav-item>
</x-nav-group>
@endcan --}}

{{-- Network --}}
@can('network.device.view')
<x-nav-group label="Red" icon="network">
    <x-nav-item
        href="{{ route('network.topology') }}"
        :active="request()->routeIs('network.topology')"
    >
        Topologia
    </x-nav-item>

    <x-nav-item
        href="{{ route('network.nodes.index') }}"
        :active="request()->routeIs('network.nodes.*')"
    >
        Nodos
    </x-nav-item>

    <x-nav-item
        href="{{ route('network.devices.index') }}"
        :active="request()->routeIs('network.devices.*')"
    >
        Dispositivos
    </x-nav-item>

    <x-nav-item
        href="{{ route('network.ip-pools.index') }}"
        :active="request()->routeIs('network.ip-pools.*')"
    >
        IPs
    </x-nav-item>

    <x-nav-item
        href="{{ route('network.nap-boxes.index') }}"
        :active="request()->routeIs('network.nap-boxes.*')"
    >
        NAPs
    </x-nav-item>
</x-nav-group>
@endcan

{{-- Inventory --}}
@can('inventory.stock.view')
<x-nav-group label="Inventario" icon="package">
    <x-nav-item
        href="{{ route('inventory.products.index') }}"
        :active="request()->routeIs('inventory.products.*')"
    >
        Productos
    </x-nav-item>

    <x-nav-item
        href="{{ route('inventory.warehouses.index') }}"
        :active="request()->routeIs('inventory.warehouses.*')"
    >
        Almacenes
    </x-nav-item>

    <x-nav-item
        href="{{ route('inventory.movements.index') }}"
        :active="request()->routeIs('inventory.movements.*')"
    >
        Movimientos
    </x-nav-item>

    {{-- <x-nav-item
        href="{{ route('inventory.serials.index') }}"
        :active="request()->routeIs('inventory.serials.*')"
    >
        Equipos Serializados
    </x-nav-item> --}}
</x-nav-group>
@endcan

{{-- Catalog --}}
@can('catalog.plan.view')
<x-nav-group label="Catálogo" icon="tag">
    <x-nav-item
        href="{{ route('catalog.plans.index') }}"
        :active="request()->routeIs('catalog.plans.*')"
    >
        Planes
    </x-nav-item>

    <x-nav-item
        href="{{ route('catalog.promotions.index') }}"
        :active="request()->routeIs('catalog.promotions.*')"
    >
        Promociones
    </x-nav-item>

    <x-nav-item
        href="{{ route('catalog.addons.index') }}"
        :active="request()->routeIs('catalog.addons.*')"
    >
        Add-ons
    </x-nav-item>
</x-nav-group>
@endcan

{{-- Reports --}}
{{-- <x-nav-group label="Reportes" icon="chart">
    <x-nav-item
        href="{{ route('reports.sales') }}"
        :active="request()->routeIs('reports.sales')"
    >
        Ventas
    </x-nav-item>

    <x-nav-item
        href="{{ route('reports.operations') }}"
        :active="request()->routeIs('reports.operations')"
    >
        Operaciones
    </x-nav-item>

    <x-nav-item
        href="{{ route('reports.financial') }}"
        :active="request()->routeIs('reports.financial')"
    >
        Financiero
    </x-nav-item>
</x-nav-group> --}}

{{-- Settings --}}
@can('admin')
<div class="pt-4 mt-4 border-t border-secondary-200">
    <x-nav-group label="Administración" icon="settings">
        <x-nav-item
            href="{{ route('accesscontrol.users.index') }}"
            :active="request()->routeIs('admin.users.*')"
        >
            Usuarios
        </x-nav-item>

        <x-nav-item
            href="{{ route('accesscontrol.roles.index') }}"
            :active="request()->routeIs('admin.roles.*')"
        >
            Roles y Permisos
        </x-nav-item>

        <x-nav-item
            href="{{ route('accesscontrol.zones.index') }}"
            :active="request()->routeIs('admin.zones.*')"
        >
            Zonas
        </x-nav-item>

        <x-nav-item
            href="#"
            :active="request()->routeIs('admin.settings')"
        >
            Configuración
        </x-nav-item>
    </x-nav-group>
</div>
@endcan
