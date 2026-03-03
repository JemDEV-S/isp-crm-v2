<?php

namespace Modules\Network\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Network\Entities\Device;
use Modules\Network\Entities\IpAddress;
use Modules\Network\Entities\IpPool;
use Modules\Network\Entities\NapBox;
use Modules\Network\Entities\NapPort;
use Modules\Network\Entities\Node;
use Modules\Network\Enums\DeviceType;
use Modules\Network\Enums\IpStatus;
use Modules\Network\Enums\NapPortStatus;

class NetworkSeeder extends Seeder
{
    public function run(): void
    {
        // Create main datacenter node
        $datacenter = Node::create([
            'code' => 'DC-001',
            'name' => 'Datacenter Principal',
            'type' => 'datacenter',
            'address' => 'Av. Principal 123, Lima',
            'latitude' => -12.0464,
            'longitude' => -77.0428,
            'status' => 'active',
            'description' => 'Centro de datos principal de NORETEL',
            'commissioned_at' => now()->subYears(2),
        ]);

        // Create tower nodes
        $tower1 = Node::create([
            'code' => 'TW-001',
            'name' => 'Torre Norte',
            'type' => 'tower',
            'address' => 'Calle Los Pinos 456, Distrito Norte',
            'latitude' => -12.0200,
            'longitude' => -77.0500,
            'altitude' => 45.0,
            'status' => 'active',
            'commissioned_at' => now()->subYear(),
        ]);

        $tower2 = Node::create([
            'code' => 'TW-002',
            'name' => 'Torre Sur',
            'type' => 'tower',
            'address' => 'Av. del Sol 789, Distrito Sur',
            'latitude' => -12.0800,
            'longitude' => -77.0300,
            'altitude' => 38.0,
            'status' => 'active',
            'commissioned_at' => now()->subMonths(6),
        ]);

        // Create main router at datacenter
        $mainRouter = Device::create([
            'node_id' => $datacenter->id,
            'type' => DeviceType::ROUTER,
            'brand' => 'Mikrotik',
            'model' => 'CCR1036-8G-2S+',
            'serial_number' => 'MK-CCR-001',
            'ip_address' => '10.0.0.1',
            'mac_address' => 'AA:BB:CC:DD:EE:01',
            'firmware_version' => '7.11.2',
            'api_port' => 8728,
            'api_user' => 'admin',
            'api_password_encrypted' => encrypt('admin123'),
            'status' => 'active',
            'last_seen_at' => now(),
            'notes' => 'Router principal de borde',
        ]);

        // Create OLT at datacenter
        $olt = Device::create([
            'node_id' => $datacenter->id,
            'type' => DeviceType::OLT,
            'brand' => 'Huawei',
            'model' => 'MA5608T',
            'serial_number' => 'HW-OLT-001',
            'ip_address' => '10.0.0.10',
            'mac_address' => 'AA:BB:CC:DD:EE:10',
            'firmware_version' => 'V800R018C10',
            'snmp_community' => 'public',
            'status' => 'active',
            'last_seen_at' => now(),
            'notes' => 'OLT principal GPON',
        ]);

        // Create access points at towers
        Device::create([
            'node_id' => $tower1->id,
            'type' => DeviceType::AP,
            'brand' => 'Ubiquiti',
            'model' => 'LiteAP AC',
            'serial_number' => 'UB-AP-001',
            'ip_address' => '10.1.1.1',
            'mac_address' => 'AA:BB:CC:DD:EE:20',
            'status' => 'active',
            'last_seen_at' => now(),
        ]);

        Device::create([
            'node_id' => $tower2->id,
            'type' => DeviceType::AP,
            'brand' => 'Ubiquiti',
            'model' => 'LiteAP AC',
            'serial_number' => 'UB-AP-002',
            'ip_address' => '10.1.2.1',
            'mac_address' => 'AA:BB:CC:DD:EE:21',
            'status' => 'active',
            'last_seen_at' => now(),
        ]);

        // Create IP Pool for clients
        $clientPool = IpPool::create([
            'name' => 'Pool Clientes Residenciales',
            'network_cidr' => '100.64.0.0/22',
            'gateway' => '100.64.0.1',
            'dns_primary' => '8.8.8.8',
            'dns_secondary' => '8.8.4.4',
            'type' => 'cgnat',
            'device_id' => $mainRouter->id,
            'is_active' => true,
        ]);

        // Create IP addresses in the pool
        $this->populateIpPool($clientPool, '100.64.0.0', 22);

        // Create another pool for businesses
        $businessPool = IpPool::create([
            'name' => 'Pool Clientes Empresariales',
            'network_cidr' => '181.65.100.0/24',
            'gateway' => '181.65.100.1',
            'dns_primary' => '8.8.8.8',
            'dns_secondary' => '1.1.1.1',
            'type' => 'public',
            'device_id' => $mainRouter->id,
            'is_active' => true,
        ]);

        $this->populateIpPool($businessPool, '181.65.100.0', 24);

        // Create NAP boxes
        $nap1 = NapBox::create([
            'node_id' => $tower1->id,
            'code' => 'NAP-TW1-001',
            'name' => 'NAP Torre Norte - Sector A',
            'type' => 'splitter_1x8',
            'total_ports' => 8,
            'latitude' => -12.0205,
            'longitude' => -77.0495,
            'address' => 'Calle Los Robles esq. Los Pinos',
            'status' => 'active',
            'installed_at' => now()->subMonths(8),
        ]);

        $nap2 = NapBox::create([
            'node_id' => $tower1->id,
            'code' => 'NAP-TW1-002',
            'name' => 'NAP Torre Norte - Sector B',
            'type' => 'splitter_1x16',
            'total_ports' => 16,
            'latitude' => -12.0180,
            'longitude' => -77.0520,
            'address' => 'Av. Las Flores 200',
            'status' => 'active',
            'installed_at' => now()->subMonths(6),
        ]);

        $nap3 = NapBox::create([
            'node_id' => $tower2->id,
            'code' => 'NAP-TW2-001',
            'name' => 'NAP Torre Sur - Sector A',
            'type' => 'splitter_1x8',
            'total_ports' => 8,
            'latitude' => -12.0810,
            'longitude' => -77.0295,
            'address' => 'Jr. Progreso 500',
            'status' => 'active',
            'installed_at' => now()->subMonths(4),
        ]);

        // Create ports for NAP boxes
        $this->createNapPorts($nap1);
        $this->createNapPorts($nap2);
        $this->createNapPorts($nap3);

        $this->command->info('Network module seeded successfully!');
        $this->command->info("- Created {$datacenter->id} nodes");
        $this->command->info("- Created devices at nodes");
        $this->command->info("- Created IP pools with addresses");
        $this->command->info("- Created NAP boxes with ports");
    }

    private function populateIpPool(IpPool $pool, string $network, int $bits): void
    {
        $networkLong = ip2long($network);
        $maskLong = -1 << (32 - $bits);
        $networkLong = $networkLong & $maskLong;

        $totalHosts = pow(2, 32 - $bits);
        $gateway = ip2long($pool->gateway);

        $ips = [];
        $count = 0;
        $maxIps = min($totalHosts - 2, 254); // Limit for seeding

        for ($i = 1; $i <= $maxIps && $count < $maxIps; $i++) {
            $ipLong = $networkLong + $i;

            if ($ipLong === $gateway || $ipLong === $networkLong) {
                continue;
            }

            $ips[] = [
                'pool_id' => $pool->id,
                'address' => long2ip($ipLong),
                'status' => IpStatus::FREE->value,
                'created_at' => now(),
                'updated_at' => now(),
            ];
            $count++;

            if (count($ips) >= 100) {
                IpAddress::insert($ips);
                $ips = [];
            }
        }

        if (!empty($ips)) {
            IpAddress::insert($ips);
        }
    }

    private function createNapPorts(NapBox $napBox): void
    {
        $ports = [];
        for ($i = 1; $i <= $napBox->total_ports; $i++) {
            $ports[] = [
                'nap_box_id' => $napBox->id,
                'port_number' => $i,
                'status' => NapPortStatus::FREE->value,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        NapPort::insert($ports);
    }
}
