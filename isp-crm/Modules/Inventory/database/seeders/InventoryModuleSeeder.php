<?php

declare(strict_types=1);

namespace Modules\Inventory\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Inventory\Entities\ProductCategory;
use Modules\Inventory\Entities\Product;
use Modules\Inventory\Entities\Warehouse;
use Modules\Inventory\Enums\WarehouseType;

class InventoryModuleSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedCategories();
        $this->seedWarehouses();
        $this->seedProducts();
    }

    private function seedCategories(): void
    {
        $categories = [
            ['code' => 'NETWORK_EQUIP', 'name' => 'Equipos de Red', 'parent_id' => null],
            ['code' => 'ONT', 'name' => 'ONT/ONU', 'parent_id' => 1],
            ['code' => 'ROUTER', 'name' => 'Routers', 'parent_id' => 1],
            ['code' => 'AP', 'name' => 'Access Points', 'parent_id' => 1],

            ['code' => 'FIBER', 'name' => 'Fibra Óptica', 'parent_id' => null],
            ['code' => 'FIBER_DROP', 'name' => 'Drop Cable', 'parent_id' => 5],
            ['code' => 'FIBER_TRUNK', 'name' => 'Cable Troncal', 'parent_id' => 5],

            ['code' => 'CONNECTORS', 'name' => 'Conectores', 'parent_id' => null],
            ['code' => 'SC_APC', 'name' => 'Conectores SC/APC', 'parent_id' => 8],
            ['code' => 'FAST_CONN', 'name' => 'Fast Connectors', 'parent_id' => 8],

            ['code' => 'ACCESSORIES', 'name' => 'Accesorios', 'parent_id' => null],
            ['code' => 'SPLITTER', 'name' => 'Splitters', 'parent_id' => 11],
            ['code' => 'NAP_BOX', 'name' => 'Cajas NAP', 'parent_id' => 11],
            ['code' => 'CABLE_TIES', 'name' => 'Amarras y Grapas', 'parent_id' => 11],
        ];

        foreach ($categories as $category) {
            ProductCategory::firstOrCreate(
                ['code' => $category['code']],
                $category
            );
        }
    }

    private function seedWarehouses(): void
    {
        $warehouses = [
            [
                'code' => 'CENTRAL',
                'name' => 'Almacén Central',
                'type' => WarehouseType::CENTRAL,
                'address' => 'Av. Principal 123, Arequipa',
                'contact_name' => 'Juan Pérez',
                'contact_phone' => '054-123456',
                'is_active' => true,
            ],
            [
                'code' => 'SUCURSAL_01',
                'name' => 'Sucursal Cercado',
                'type' => WarehouseType::BRANCH,
                'address' => 'Calle Real 456, Cercado',
                'is_active' => true,
            ],
        ];

        foreach ($warehouses as $warehouse) {
            Warehouse::firstOrCreate(
                ['code' => $warehouse['code']],
                $warehouse
            );
        }
    }

    private function seedProducts(): void
    {
        $products = [
            // ONTs
            [
                'sku' => 'ONT-HG8010H',
                'name' => 'ONT Huawei HG8010H',
                'category_id' => 2,
                'unit_of_measure' => 'unit',
                'min_stock' => 50,
                'requires_serial' => true,
                'unit_cost' => 45.00,
                'brand' => 'Huawei',
                'model' => 'HG8010H',
                'is_active' => true,
            ],
            [
                'sku' => 'ONT-ZTE-F601',
                'name' => 'ONT ZTE F601',
                'category_id' => 2,
                'unit_of_measure' => 'unit',
                'min_stock' => 50,
                'requires_serial' => true,
                'unit_cost' => 42.00,
                'brand' => 'ZTE',
                'model' => 'F601',
                'is_active' => true,
            ],

            // Routers
            [
                'sku' => 'RTR-TPLINK-C6',
                'name' => 'Router TP-Link Archer C6',
                'category_id' => 3,
                'unit_of_measure' => 'unit',
                'min_stock' => 30,
                'requires_serial' => false,
                'unit_cost' => 85.00,
                'brand' => 'TP-Link',
                'model' => 'Archer C6',
                'is_active' => true,
            ],

            // Fibra
            [
                'sku' => 'FIBER-DROP-100M',
                'name' => 'Drop Cable FTTH 100m',
                'category_id' => 6,
                'unit_of_measure' => 'meter',
                'min_stock' => 500,
                'requires_serial' => false,
                'unit_cost' => 0.80,
                'is_active' => true,
            ],
            [
                'sku' => 'FIBER-DROP-1KM',
                'name' => 'Drop Cable FTTH 1km',
                'category_id' => 6,
                'unit_of_measure' => 'meter',
                'min_stock' => 2000,
                'requires_serial' => false,
                'unit_cost' => 0.75,
                'is_active' => true,
            ],

            // Conectores
            [
                'sku' => 'CONN-SC-APC',
                'name' => 'Conector SC/APC',
                'category_id' => 9,
                'unit_of_measure' => 'unit',
                'min_stock' => 200,
                'requires_serial' => false,
                'unit_cost' => 1.50,
                'is_active' => true,
            ],
            [
                'sku' => 'CONN-FAST-SC',
                'name' => 'Fast Connector SC/APC',
                'category_id' => 10,
                'unit_of_measure' => 'unit',
                'min_stock' => 200,
                'requires_serial' => false,
                'unit_cost' => 2.80,
                'is_active' => true,
            ],

            // Splitters
            [
                'sku' => 'SPL-1X8-PLC',
                'name' => 'Splitter PLC 1x8',
                'category_id' => 12,
                'unit_of_measure' => 'unit',
                'min_stock' => 20,
                'requires_serial' => false,
                'unit_cost' => 18.00,
                'is_active' => true,
            ],
            [
                'sku' => 'SPL-1X16-PLC',
                'name' => 'Splitter PLC 1x16',
                'category_id' => 12,
                'unit_of_measure' => 'unit',
                'min_stock' => 15,
                'requires_serial' => false,
                'unit_cost' => 32.00,
                'is_active' => true,
            ],

            // Cajas NAP
            [
                'sku' => 'NAP-16P',
                'name' => 'Caja NAP 16 puertos',
                'category_id' => 13,
                'unit_of_measure' => 'unit',
                'min_stock' => 10,
                'requires_serial' => false,
                'unit_cost' => 65.00,
                'is_active' => true,
            ],

            // Accesorios
            [
                'sku' => 'ACC-TIES-100',
                'name' => 'Amarras Plásticas 100u',
                'category_id' => 14,
                'unit_of_measure' => 'pack',
                'min_stock' => 50,
                'requires_serial' => false,
                'unit_cost' => 5.00,
                'is_active' => true,
            ],
        ];

        foreach ($products as $product) {
            Product::firstOrCreate(
                ['sku' => $product['sku']],
                $product
            );
        }
    }
}
