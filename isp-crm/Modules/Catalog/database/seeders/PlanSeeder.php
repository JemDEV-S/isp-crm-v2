<?php

declare(strict_types=1);

namespace Modules\Catalog\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Catalog\Entities\Plan;
use Modules\Catalog\Enums\Technology;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = [
            // Planes de Fibra Óptica
            [
                'code' => 'FIBER-10',
                'name' => 'Plan Básico Fibra',
                'description' => 'Plan de internet básico ideal para hogares pequeños',
                'technology' => Technology::FIBER,
                'download_speed' => 10,
                'upload_speed' => 5,
                'price' => 49.90,
                'installation_fee' => 100.00,
                'router_profile' => 'plan-10m',
                'priority' => 6,
                'is_active' => true,
                'is_visible' => true,
            ],
            [
                'code' => 'FIBER-20',
                'name' => 'Plan Hogar Fibra',
                'description' => 'Plan de internet para familias medianas con varios dispositivos',
                'technology' => Technology::FIBER,
                'download_speed' => 20,
                'upload_speed' => 10,
                'price' => 69.90,
                'installation_fee' => 100.00,
                'router_profile' => 'plan-20m',
                'priority' => 5,
                'is_active' => true,
                'is_visible' => true,
            ],
            [
                'code' => 'FIBER-50',
                'name' => 'Plan Premium Fibra',
                'description' => 'Plan de alta velocidad para trabajo remoto y streaming',
                'technology' => Technology::FIBER,
                'download_speed' => 50,
                'upload_speed' => 25,
                'price' => 99.90,
                'installation_fee' => 100.00,
                'router_profile' => 'plan-50m',
                'burst_enabled' => true,
                'priority' => 4,
                'is_active' => true,
                'is_visible' => true,
            ],
            [
                'code' => 'FIBER-100',
                'name' => 'Plan Ultra Fibra',
                'description' => 'Plan de máxima velocidad para hogares con múltiples usuarios',
                'technology' => Technology::FIBER,
                'download_speed' => 100,
                'upload_speed' => 50,
                'price' => 149.90,
                'installation_fee' => 0.00,
                'router_profile' => 'plan-100m',
                'burst_enabled' => true,
                'priority' => 3,
                'is_active' => true,
                'is_visible' => true,
            ],
            // Planes Inalámbricos
            [
                'code' => 'WIRELESS-5',
                'name' => 'Plan Básico Inalámbrico',
                'description' => 'Plan inalámbrico económico para zonas sin fibra',
                'technology' => Technology::WIRELESS,
                'download_speed' => 5,
                'upload_speed' => 2,
                'price' => 39.90,
                'installation_fee' => 80.00,
                'router_profile' => 'wireless-5m',
                'priority' => 7,
                'is_active' => true,
                'is_visible' => true,
            ],
            [
                'code' => 'WIRELESS-10',
                'name' => 'Plan Hogar Inalámbrico',
                'description' => 'Plan inalámbrico para uso familiar básico',
                'technology' => Technology::WIRELESS,
                'download_speed' => 10,
                'upload_speed' => 5,
                'price' => 59.90,
                'installation_fee' => 80.00,
                'router_profile' => 'wireless-10m',
                'priority' => 6,
                'is_active' => true,
                'is_visible' => true,
            ],
            // Plan Empresarial
            [
                'code' => 'BUSINESS-100',
                'name' => 'Plan Empresarial',
                'description' => 'Plan dedicado para empresas con IP fija incluida',
                'technology' => Technology::FIBER,
                'download_speed' => 100,
                'upload_speed' => 100,
                'price' => 299.90,
                'installation_fee' => 0.00,
                'router_profile' => 'business-100m',
                'burst_enabled' => false,
                'priority' => 1,
                'is_active' => true,
                'is_visible' => false, // Solo venta directa
            ],
        ];

        foreach ($plans as $planData) {
            $plan = Plan::create([
                ...$planData,
                'uuid' => Str::uuid()->toString(),
            ]);

            // Agregar parámetros técnicos
            $plan->setParameter('download_speed_mbps', (string) $planData['download_speed']);
            $plan->setParameter('upload_speed_mbps', (string) $planData['upload_speed']);

            $downloadKbps = $planData['download_speed'] * 1024;
            $uploadKbps = $planData['upload_speed'] * 1024;
            $plan->setParameter('rate_limit_string', "{$uploadKbps}k/{$downloadKbps}k");

            if (isset($planData['burst_enabled']) && $planData['burst_enabled']) {
                $burstDownload = $planData['download_speed'] * 1.5;
                $burstUpload = $planData['upload_speed'] * 1.5;
                $plan->setParameter('burst_download_mbps', (string) $burstDownload);
                $plan->setParameter('burst_upload_mbps', (string) $burstUpload);
                $plan->setParameter('burst_threshold', '50k/50k');
                $plan->setParameter('burst_time', '10s/10s');
            }
        }
    }
}
