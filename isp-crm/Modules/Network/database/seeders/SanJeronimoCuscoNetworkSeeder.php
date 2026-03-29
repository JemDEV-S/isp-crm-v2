<?php

declare(strict_types=1);

namespace Modules\Network\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Network\Entities\Device;
use Modules\Network\Entities\FiberRoute;
use Modules\Network\Entities\NapBox;
use Modules\Network\Entities\NapPort;
use Modules\Network\Entities\Node;
use Modules\Network\Enums\DeviceType;
use Modules\Network\Enums\NapPortStatus;

class SanJeronimoCuscoNetworkSeeder extends Seeder
{
    public function run(): void
    {
        $nodes = collect([
            [
                'code' => 'CUS-SJ-POP-001',
                'name' => 'POP San Jeronimo Centro',
                'type' => 'pop',
                'address' => 'Av. Micaela Bastidas, sector centro, San Jeronimo, Cusco',
                'latitude' => -13.5446500,
                'longitude' => -71.8858500,
                'altitude' => 3225.00,
                'status' => 'active',
                'description' => 'Nodo principal de agregacion para la red de San Jeronimo.',
                'commissioned_at' => now()->subMonths(14),
            ],
            [
                'code' => 'CUS-SJ-TWR-001',
                'name' => 'Torre San Jeronimo Norte',
                'type' => 'tower',
                'address' => 'Sector Norte residencial, San Jeronimo, Cusco',
                'latitude' => -13.5398000,
                'longitude' => -71.8886000,
                'altitude' => 3254.00,
                'status' => 'active',
                'description' => 'Cobertura del corredor norte y areas universitarias.',
                'commissioned_at' => now()->subMonths(11),
            ],
            [
                'code' => 'CUS-SJ-TWR-002',
                'name' => 'Torre San Jeronimo Sur',
                'type' => 'tower',
                'address' => 'Sector Sur urbano, San Jeronimo, Cusco',
                'latitude' => -13.5518000,
                'longitude' => -71.8849000,
                'altitude' => 3238.00,
                'status' => 'active',
                'description' => 'Cobertura para zonas en expansion residencial.',
                'commissioned_at' => now()->subMonths(9),
            ],
            [
                'code' => 'CUS-SJ-CAB-001',
                'name' => 'Gabinete San Jeronimo Oeste',
                'type' => 'cabinet',
                'address' => 'Sector Oeste comercial, San Jeronimo, Cusco',
                'latitude' => -13.5455000,
                'longitude' => -71.8914000,
                'altitude' => 3220.00,
                'status' => 'active',
                'description' => 'Gabinete de distribucion para la zona comercial del distrito.',
                'commissioned_at' => now()->subMonths(8),
            ],
            [
                'code' => 'CUS-SJ-POP-002',
                'name' => 'POP San Jeronimo Este',
                'type' => 'pop',
                'address' => 'Sector Este, eje de crecimiento, San Jeronimo, Cusco',
                'latitude' => -13.5433000,
                'longitude' => -71.8783000,
                'altitude' => 3231.00,
                'status' => 'active',
                'description' => 'Nodo de respaldo y distribucion oriental.',
                'commissioned_at' => now()->subMonths(7),
            ],
            [
                'code' => 'CUS-SJ-TWR-003',
                'name' => 'Torre San Jeronimo Industrial',
                'type' => 'tower',
                'address' => 'Zona industrial ligera, San Jeronimo, Cusco',
                'latitude' => -13.5484000,
                'longitude' => -71.8769000,
                'altitude' => 3240.00,
                'status' => 'maintenance',
                'description' => 'Cobertura para clientes pyme y capacidad de respaldo.',
                'commissioned_at' => now()->subMonths(6),
            ],
        ])->mapWithKeys(function (array $nodeData) {
            $node = Node::updateOrCreate(
                ['code' => $nodeData['code']],
                $nodeData,
            );

            return [$node->code => $node];
        });

        $this->seedDevices($nodes);
        $napBoxes = $this->seedNapBoxes($nodes);
        $this->seedFiberRoutes($nodes);

        $this->command?->info('SanJeronimoCuscoNetworkSeeder ejecutado correctamente.');
        $this->command?->line('- Nodos: ' . $nodes->count());
        $this->command?->line('- NAPs: ' . $napBoxes->count());
        $this->command?->line('- Rutas de fibra: 7');
    }

    protected function seedDevices($nodes): void
    {
        $devices = [
            [
                'node_code' => 'CUS-SJ-POP-001',
                'type' => DeviceType::ROUTER->value,
                'brand' => 'Mikrotik',
                'model' => 'CCR2004-16G-2S+',
                'serial_number' => 'SJ-ROUTER-001',
                'ip_address' => '10.30.0.1',
                'status' => 'active',
                'notes' => 'Router de borde principal del distrito.',
            ],
            [
                'node_code' => 'CUS-SJ-POP-001',
                'type' => DeviceType::OLT->value,
                'brand' => 'Huawei',
                'model' => 'MA5800-X7',
                'serial_number' => 'SJ-OLT-001',
                'ip_address' => '10.30.0.10',
                'status' => 'active',
                'notes' => 'OLT principal para la red FTTH de San Jeronimo.',
            ],
            [
                'node_code' => 'CUS-SJ-POP-002',
                'type' => DeviceType::SWITCH->value,
                'brand' => 'Cisco',
                'model' => 'CBS350-24T-4X',
                'serial_number' => 'SJ-SWITCH-001',
                'ip_address' => '10.30.1.20',
                'status' => 'active',
                'notes' => 'Switch de agregacion oriental.',
            ],
            [
                'node_code' => 'CUS-SJ-TWR-001',
                'type' => DeviceType::AP->value,
                'brand' => 'Ubiquiti',
                'model' => 'Rocket Prism 5AC',
                'serial_number' => 'SJ-AP-001',
                'ip_address' => '10.30.2.11',
                'status' => 'active',
                'notes' => 'Enlace de respaldo norte.',
            ],
            [
                'node_code' => 'CUS-SJ-TWR-003',
                'type' => DeviceType::AP->value,
                'brand' => 'Ubiquiti',
                'model' => 'Rocket Prism 5AC',
                'serial_number' => 'SJ-AP-002',
                'ip_address' => '10.30.2.31',
                'status' => 'maintenance',
                'notes' => 'Equipo en mantenimiento preventivo.',
            ],
        ];

        foreach ($devices as $deviceData) {
            $node = $nodes->get($deviceData['node_code']);

            Device::updateOrCreate(
                ['serial_number' => $deviceData['serial_number']],
                [
                    'node_id' => $node->id,
                    'type' => $deviceData['type'],
                    'brand' => $deviceData['brand'],
                    'model' => $deviceData['model'],
                    'serial_number' => $deviceData['serial_number'],
                    'ip_address' => $deviceData['ip_address'],
                    'status' => $deviceData['status'],
                    'last_seen_at' => $deviceData['status'] === 'active' ? now()->subMinutes(rand(1, 4)) : now()->subHours(5),
                    'notes' => $deviceData['notes'],
                ],
            );
        }
    }

    protected function seedNapBoxes($nodes)
    {
        $napDefinitions = [
            ['code' => 'NAP-SJ-001', 'node_code' => 'CUS-SJ-TWR-001', 'name' => 'NAP Norte A', 'type' => 'splitter_1x8', 'latitude' => -13.5403000, 'longitude' => -71.8879000, 'address' => 'Sector Norte A', 'total_ports' => 8, 'status' => 'active'],
            ['code' => 'NAP-SJ-002', 'node_code' => 'CUS-SJ-TWR-001', 'name' => 'NAP Norte B', 'type' => 'splitter_1x16', 'latitude' => -13.5389000, 'longitude' => -71.8892000, 'address' => 'Sector Norte B', 'total_ports' => 16, 'status' => 'active'],
            ['code' => 'NAP-SJ-003', 'node_code' => 'CUS-SJ-POP-001', 'name' => 'NAP Centro A', 'type' => 'splitter_1x16', 'latitude' => -13.5449000, 'longitude' => -71.8853000, 'address' => 'Centro A', 'total_ports' => 16, 'status' => 'active'],
            ['code' => 'NAP-SJ-004', 'node_code' => 'CUS-SJ-POP-001', 'name' => 'NAP Centro B', 'type' => 'splitter_1x8', 'latitude' => -13.5454000, 'longitude' => -71.8838000, 'address' => 'Centro B', 'total_ports' => 8, 'status' => 'active'],
            ['code' => 'NAP-SJ-005', 'node_code' => 'CUS-SJ-TWR-002', 'name' => 'NAP Sur A', 'type' => 'splitter_1x16', 'latitude' => -13.5511000, 'longitude' => -71.8856000, 'address' => 'Sector Sur A', 'total_ports' => 16, 'status' => 'active'],
            ['code' => 'NAP-SJ-006', 'node_code' => 'CUS-SJ-TWR-002', 'name' => 'NAP Sur B', 'type' => 'splitter_1x8', 'latitude' => -13.5525000, 'longitude' => -71.8837000, 'address' => 'Sector Sur B', 'total_ports' => 8, 'status' => 'active'],
            ['code' => 'NAP-SJ-007', 'node_code' => 'CUS-SJ-CAB-001', 'name' => 'NAP Oeste A', 'type' => 'splitter_1x8', 'latitude' => -13.5459000, 'longitude' => -71.8905000, 'address' => 'Sector Oeste A', 'total_ports' => 8, 'status' => 'active'],
            ['code' => 'NAP-SJ-008', 'node_code' => 'CUS-SJ-POP-002', 'name' => 'NAP Este A', 'type' => 'splitter_1x16', 'latitude' => -13.5437000, 'longitude' => -71.8792000, 'address' => 'Sector Este A', 'total_ports' => 16, 'status' => 'active'],
            ['code' => 'NAP-SJ-009', 'node_code' => 'CUS-SJ-POP-002', 'name' => 'NAP Este B', 'type' => 'splitter_1x8', 'latitude' => -13.5421000, 'longitude' => -71.8778000, 'address' => 'Sector Este B', 'total_ports' => 8, 'status' => 'active'],
            ['code' => 'NAP-SJ-010', 'node_code' => 'CUS-SJ-TWR-003', 'name' => 'NAP Industrial', 'type' => 'splitter_1x8', 'latitude' => -13.5480000, 'longitude' => -71.8775000, 'address' => 'Sector Industrial', 'total_ports' => 8, 'status' => 'maintenance'],
        ];

        return collect($napDefinitions)->mapWithKeys(function (array $napData) use ($nodes) {
            $node = $nodes->get($napData['node_code']);

            $napBox = NapBox::updateOrCreate(
                ['code' => $napData['code']],
                [
                    'node_id' => $node->id,
                    'code' => $napData['code'],
                    'name' => $napData['name'],
                    'type' => $napData['type'],
                    'latitude' => $napData['latitude'],
                    'longitude' => $napData['longitude'],
                    'address' => $napData['address'] . ', San Jeronimo, Cusco',
                    'total_ports' => $napData['total_ports'],
                    'status' => $napData['status'],
                    'installed_at' => now()->subMonths(rand(4, 12)),
                    'notes' => 'NAP demo para verificaciones de cobertura y topologia.',
                ],
            );

            $this->syncPorts($napBox);

            return [$napBox->code => $napBox];
        });
    }

    protected function seedFiberRoutes($nodes): void
    {
        $routes = [
            ['from' => 'CUS-SJ-POP-001', 'to' => 'CUS-SJ-TWR-001', 'distance' => 780, 'fiber_count' => 24, 'status' => 'active'],
            ['from' => 'CUS-SJ-POP-001', 'to' => 'CUS-SJ-TWR-002', 'distance' => 860, 'fiber_count' => 24, 'status' => 'active'],
            ['from' => 'CUS-SJ-POP-001', 'to' => 'CUS-SJ-CAB-001', 'distance' => 640, 'fiber_count' => 12, 'status' => 'active'],
            ['from' => 'CUS-SJ-CAB-001', 'to' => 'CUS-SJ-POP-002', 'distance' => 1220, 'fiber_count' => 24, 'status' => 'active'],
            ['from' => 'CUS-SJ-POP-002', 'to' => 'CUS-SJ-TWR-003', 'distance' => 790, 'fiber_count' => 12, 'status' => 'maintenance'],
            ['from' => 'CUS-SJ-TWR-002', 'to' => 'CUS-SJ-TWR-003', 'distance' => 930, 'fiber_count' => 12, 'status' => 'active'],
            ['from' => 'CUS-SJ-TWR-001', 'to' => 'CUS-SJ-POP-002', 'distance' => 1180, 'fiber_count' => 24, 'status' => 'active'],
        ];

        foreach ($routes as $routeData) {
            $fromNode = $nodes->get($routeData['from']);
            $toNode = $nodes->get($routeData['to']);

            FiberRoute::updateOrCreate(
                [
                    'from_node_id' => $fromNode->id,
                    'to_node_id' => $toNode->id,
                ],
                [
                    'distance_meters' => $routeData['distance'],
                    'fiber_count' => $routeData['fiber_count'],
                    'status' => $routeData['status'],
                    'route_geojson' => [
                        'type' => 'LineString',
                        'coordinates' => [
                            [(float) $fromNode->longitude, (float) $fromNode->latitude],
                            [(float) $toNode->longitude, (float) $toNode->latitude],
                        ],
                    ],
                    'notes' => 'Ruta troncal demo para la topologia de San Jeronimo.',
                ],
            );
        }
    }

    protected function syncPorts(NapBox $napBox): void
    {
        for ($portNumber = 1; $portNumber <= $napBox->total_ports; $portNumber++) {
            $status = match (true) {
                $napBox->status !== 'active' && $portNumber <= 2 => NapPortStatus::DAMAGED->value,
                $portNumber <= 2 => NapPortStatus::OCCUPIED->value,
                $portNumber === 3 => NapPortStatus::RESERVED->value,
                $portNumber === $napBox->total_ports && $napBox->total_ports >= 8 => NapPortStatus::DAMAGED->value,
                default => NapPortStatus::FREE->value,
            };

            NapPort::updateOrCreate(
                [
                    'nap_box_id' => $napBox->id,
                    'port_number' => $portNumber,
                ],
                [
                    'status' => $status,
                    'subscription_id' => null,
                    'label' => $status === NapPortStatus::OCCUPIED->value ? "SJ-SUB-{$napBox->id}-{$portNumber}" : null,
                    'notes' => $this->portNotes($status, $napBox, $portNumber),
                ],
            );
        }
    }

    protected function portNotes(string $status, NapBox $napBox, int $portNumber): string
    {
        return match ($status) {
            NapPortStatus::OCCUPIED->value => "Puerto ocupado para cliente demo en {$napBox->code}.",
            NapPortStatus::RESERVED->value => "Puerto reservado para prefactibilidad demo en {$napBox->code}.",
            NapPortStatus::DAMAGED->value => "Puerto fuera de servicio para pruebas de visualizacion.",
            default => "Puerto libre disponible para nuevas altas en {$napBox->code}.",
        };
    }
}
