@extends('layouts.app')

@section('title', 'Topologia de Red')

@section('breadcrumb')
    <span class="text-secondary-500">Red</span>
    <x-icon name="chevron-right" class="w-4 h-4 text-secondary-400" />
    <span class="text-secondary-900 font-medium">Topologia</span>
@endsection

@section('content')
    <div
        class="space-y-6"
        x-data="networkTopology({
            endpoints: {
                nodes: @js(route('network.nodes.json')),
                naps: @js(route('network.nap-boxes.geojson', ['active_only' => 0])),
                fiber: @js(route('network.fiber-routes.geojson', ['active_only' => 0])),
            }
        })"
        x-init="init()"
    >
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-secondary-900">Topologia de Red</h1>
                <p class="mt-1 text-sm text-secondary-500">
                    Vista operativa del despliegue en mapa. Hoy consume snapshots; queda lista para evolucionar a monitoreo en tiempo real.
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <x-button type="button" variant="ghost" icon="refresh" @click="reload()">Actualizar mapa</x-button>
                <a href="{{ route('network.nodes.index') }}">
                    <x-button type="button" variant="secondary" icon="network">Gestionar nodos</x-button>
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-5">
            <x-stat-card title="Nodos" :value="$stats['nodes']" icon="network" color="primary" />
            <x-stat-card title="Nodos activos" :value="$stats['active_nodes']" icon="check-circle" color="success" />
            <x-stat-card title="NAPs" :value="$stats['nap_boxes']" icon="tag" color="info" />
            <x-stat-card title="Puertos libres" :value="$stats['free_ports']" icon="signal" color="warning" />
            <x-stat-card title="Rutas de fibra" :value="$stats['fiber_routes']" icon="chart" color="primary" />
        </div>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-4">
            <div class="space-y-6 xl:col-span-3">
                <x-card :padding="false" class="overflow-hidden">
                    <div class="flex flex-col gap-3 border-b border-secondary-200 bg-secondary-50 px-4 py-3 md:flex-row md:items-center md:justify-between">
                        <div>
                            <p class="text-sm font-semibold text-secondary-900">Mapa operativo</p>
                            <p class="text-xs text-secondary-500">Nodos, cajas NAP y troncales de fibra para ver cobertura y capacidad de la red.</p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <x-button type="button" variant="ghost" size="sm" icon="refresh" @click="fitAll()">Ajustar vista</x-button>
                            <x-button type="button" variant="secondary" size="sm" icon="eye" @click="toggleActiveOnly()" x-text="activeOnly ? 'Ver todo' : 'Solo activos'"></x-button>
                        </div>
                    </div>
                    <div class="h-[38rem] w-full" x-ref="map"></div>
                </x-card>
            </div>

            <div class="space-y-6">
                <x-card title="Capas">
                    <div class="space-y-3 text-sm text-secondary-700">
                        <label class="flex items-center justify-between gap-3">
                            <span>Nodos</span>
                            <input type="checkbox" class="rounded border-secondary-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500" checked @change="toggleLayer('nodes', $event.target.checked)">
                        </label>
                        <label class="flex items-center justify-between gap-3">
                            <span>NAPs</span>
                            <input type="checkbox" class="rounded border-secondary-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500" checked @change="toggleLayer('naps', $event.target.checked)">
                        </label>
                        <label class="flex items-center justify-between gap-3">
                            <span>Fibra</span>
                            <input type="checkbox" class="rounded border-secondary-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500" checked @change="toggleLayer('fiber', $event.target.checked)">
                        </label>
                    </div>
                </x-card>

                <x-card title="Leyenda">
                    <div class="space-y-3 text-sm text-secondary-700">
                        <div class="flex items-center gap-3">
                            <span class="h-3 w-3 rounded-full bg-success-500"></span>
                            <span>Nodo activo</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="h-3 w-3 rounded-full bg-warning-500"></span>
                            <span>Nodo en mantenimiento</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="h-3 w-3 rounded-full bg-danger-500"></span>
                            <span>Nodo inactivo</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="h-3 w-3 rounded-full bg-info-500"></span>
                            <span>NAP operativa</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="h-1 w-8 rounded bg-primary-500"></span>
                            <span>Troncal activa</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="h-1 w-8 rounded bg-warning-500"></span>
                            <span>Troncal en mantenimiento</span>
                        </div>
                    </div>
                </x-card>

                <x-card title="Estado">
                    <template x-if="loading">
                        <p class="text-sm text-secondary-500">Actualizando capas del mapa...</p>
                    </template>
                    <template x-if="!loading && !error">
                        <div class="space-y-3 text-sm text-secondary-700">
                            <p><span class="font-medium text-secondary-900">Nodos cargados:</span> <span x-text="summary.nodes"></span></p>
                            <p><span class="font-medium text-secondary-900">NAPs cargadas:</span> <span x-text="summary.naps"></span></p>
                            <p><span class="font-medium text-secondary-900">Rutas cargadas:</span> <span x-text="summary.fiber"></span></p>
                        </div>
                    </template>
                    <template x-if="error">
                        <x-alert variant="danger" x-text="error"></x-alert>
                    </template>
                </x-card>

                <x-card title="Siguiente Paso">
                    <p class="text-sm text-secondary-600">
                        La base ya queda preparada para sumar estado online/offline, latencia y alarmas con polling o websockets sin rehacer la cartografia.
                    </p>
                </x-card>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function networkTopology(config) {
            return {
                endpoints: config.endpoints,
                map: null,
                loading: false,
                error: '',
                activeOnly: false,
                layers: {
                    nodes: null,
                    naps: null,
                    fiber: null,
                },
                summary: {
                    nodes: 0,
                    naps: 0,
                    fiber: 0,
                },

                init() {
                    this.initMap();
                    this.reload();
                },

                initMap() {
                    if (!window.L || this.map || !this.$refs.map) {
                        return;
                    }

                    this.map = window.L.map(this.$refs.map).setView([-13.5455, -71.8855], 14);

                    window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        maxZoom: 19,
                        attribution: '&copy; OpenStreetMap contributors',
                    }).addTo(this.map);
                },

                async reload() {
                    this.loading = true;
                    this.error = '';

                    try {
                        const [nodes, naps, fiber] = await Promise.all([
                            this.fetchJson(this.nodeUrl()),
                            this.fetchJson(this.napsUrl()),
                            this.fetchJson(this.fiberUrl()),
                        ]);

                        this.renderNodes(nodes);
                        this.renderNaps(naps);
                        this.renderFiber(fiber);
                        this.fitAll();
                    } catch (error) {
                        this.error = error.message || 'No se pudo cargar la topologia.';
                    } finally {
                        this.loading = false;
                    }
                },

                nodeUrl() {
                    return this.activeOnly ? `${this.endpoints.nodes}?active=1` : this.endpoints.nodes;
                },

                napsUrl() {
                    return this.activeOnly
                        ? this.endpoints.naps.replace('active_only=0', 'active_only=1')
                        : this.endpoints.naps;
                },

                fiberUrl() {
                    return this.activeOnly
                        ? this.endpoints.fiber.replace('active_only=0', 'active_only=1')
                        : this.endpoints.fiber;
                },

                async fetchJson(url) {
                    const response = await fetch(url, {
                        headers: { Accept: 'application/json' },
                    });

                    if (!response.ok) {
                        throw new Error('No se pudieron cargar los datos del mapa.');
                    }

                    return response.json();
                },

                clearLayer(name) {
                    if (this.layers[name]) {
                        this.map.removeLayer(this.layers[name]);
                    }
                },

                renderNodes(nodes) {
                    this.clearLayer('nodes');
                    this.summary.nodes = nodes.length;

                    this.layers.nodes = window.L.layerGroup(
                        nodes
                            .filter((node) => node.latitude !== null && node.longitude !== null)
                            .map((node) => {
                                const marker = window.L.circleMarker([node.latitude, node.longitude], {
                                    radius: 8,
                                    color: this.nodeColor(node.status),
                                    weight: 2,
                                    fillColor: this.nodeColor(node.status),
                                    fillOpacity: 0.85,
                                });

                                marker.bindPopup(`
                                    <div class="space-y-1">
                                        <div style="font-weight:600;">${node.code} - ${node.name}</div>
                                        <div>Tipo: ${node.type_label ?? node.type ?? '-'}</div>
                                        <div>Estado: ${node.status_label ?? node.status ?? '-'}</div>
                                        <div>Equipos: ${node.devices_count ?? 0}</div>
                                        <div>NAPs: ${node.nap_boxes_count ?? 0}</div>
                                        <div>${node.address ?? '-'}</div>
                                    </div>
                                `);

                                return marker;
                            })
                    ).addTo(this.map);
                },

                renderNaps(geoJson) {
                    this.clearLayer('naps');
                    this.summary.naps = geoJson.features?.length ?? 0;

                    this.layers.naps = window.L.geoJSON(geoJson, {
                        pointToLayer: (feature, latlng) => {
                            const status = feature.properties?.status ?? 'inactive';
                            return window.L.circleMarker(latlng, {
                                radius: 6,
                                color: status === 'active' ? '#0ea5e9' : '#f59e0b',
                                weight: 2,
                                fillColor: status === 'active' ? '#38bdf8' : '#fbbf24',
                                fillOpacity: 0.8,
                            });
                        },
                        onEachFeature: (feature, layer) => {
                            const p = feature.properties ?? {};
                            layer.bindPopup(`
                                <div class="space-y-1">
                                    <div style="font-weight:600;">${p.code ?? ''} - ${p.name ?? ''}</div>
                                    <div>Tipo: ${p.type ?? '-'}</div>
                                    <div>Estado: ${p.status ?? '-'}</div>
                                    <div>Puertos libres: ${p.free_ports ?? 0}/${p.total_ports ?? 0}</div>
                                    <div>Nodo: ${p.node_name ?? '-'}</div>
                                </div>
                            `);
                        }
                    }).addTo(this.map);
                },

                renderFiber(geoJson) {
                    this.clearLayer('fiber');
                    this.summary.fiber = geoJson.features?.length ?? 0;

                    this.layers.fiber = window.L.geoJSON(geoJson, {
                        style: (feature) => ({
                            color: feature.properties?.status === 'maintenance' ? '#f59e0b' : '#2563eb',
                            weight: 4,
                            opacity: 0.8,
                            dashArray: feature.properties?.status === 'maintenance' ? '8 6' : null,
                        }),
                        onEachFeature: (feature, layer) => {
                            const p = feature.properties ?? {};
                            layer.bindPopup(`
                                <div class="space-y-1">
                                    <div style="font-weight:600;">${p.from_node ?? '-'} → ${p.to_node ?? '-'}</div>
                                    <div>Distancia: ${p.distance_km ?? '-'} km</div>
                                    <div>Hilos: ${p.fiber_count ?? '-'}</div>
                                    <div>Estado: ${p.status ?? '-'}</div>
                                </div>
                            `);
                        }
                    }).addTo(this.map);
                },

                fitAll() {
                    const groups = Object.values(this.layers).filter(Boolean);
                    if (groups.length === 0) {
                        return;
                    }

                    const bounds = window.L.featureGroup(groups).getBounds();
                    if (bounds.isValid()) {
                        this.map.fitBounds(bounds.pad(0.15));
                    }
                },

                toggleLayer(name, visible) {
                    if (!this.layers[name]) {
                        return;
                    }

                    if (visible) {
                        this.layers[name].addTo(this.map);
                    } else {
                        this.map.removeLayer(this.layers[name]);
                    }
                },

                toggleActiveOnly() {
                    this.activeOnly = !this.activeOnly;
                    this.reload();
                },

                nodeColor(status) {
                    if (status === 'active') {
                        return '#16a34a';
                    }

                    if (status === 'maintenance') {
                        return '#f59e0b';
                    }

                    return '#dc2626';
                },
            };
        }
    </script>
@endpush
