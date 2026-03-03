<?php

declare(strict_types=1);

namespace Modules\Catalog\Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Catalog\Entities\Plan;
use Modules\Catalog\Entities\Promotion;
use Modules\Catalog\Enums\AppliesTo;
use Modules\Catalog\Enums\DiscountType;

class PromotionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $promotions = [
            [
                'code' => 'BIENVENIDO',
                'name' => 'Promoción Bienvenida',
                'description' => '50% de descuento en instalación para nuevos clientes',
                'discount_type' => DiscountType::PERCENTAGE,
                'discount_value' => 50.00,
                'applies_to' => AppliesTo::INSTALLATION,
                'min_months' => 12,
                'is_active' => true,
            ],
            [
                'code' => 'INSTALACION-GRATIS',
                'name' => 'Instalación Gratis',
                'description' => 'Instalación sin costo con contrato de 24 meses',
                'discount_type' => DiscountType::PERCENTAGE,
                'discount_value' => 100.00,
                'applies_to' => AppliesTo::INSTALLATION,
                'min_months' => 24,
                'is_active' => true,
            ],
            [
                'code' => 'PRIMER-MES',
                'name' => 'Primer Mes Gratis',
                'description' => 'Primer mes de servicio totalmente gratis',
                'discount_type' => DiscountType::PERCENTAGE,
                'discount_value' => 100.00,
                'applies_to' => AppliesTo::MONTHLY,
                'min_months' => 12,
                'discount_months' => 1,
                'is_active' => true,
            ],
            [
                'code' => 'DESC20-3MESES',
                'name' => '20% Descuento 3 Meses',
                'description' => '20% de descuento los primeros 3 meses',
                'discount_type' => DiscountType::PERCENTAGE,
                'discount_value' => 20.00,
                'applies_to' => AppliesTo::MONTHLY,
                'min_months' => 6,
                'discount_months' => 3,
                'is_active' => true,
            ],
            [
                'code' => 'NAVIDAD2024',
                'name' => 'Promoción Navidad',
                'description' => 'Oferta especial de temporada navideña',
                'discount_type' => DiscountType::FIXED,
                'discount_value' => 30.00,
                'applies_to' => AppliesTo::BOTH,
                'min_months' => 12,
                'discount_months' => 2,
                'valid_from' => Carbon::create(2024, 12, 1),
                'valid_until' => Carbon::create(2024, 12, 31),
                'max_uses' => 100,
                'is_active' => true,
            ],
            [
                'code' => 'REFERIDO',
                'name' => 'Promoción Referido',
                'description' => 'Descuento especial por referencia de cliente existente',
                'discount_type' => DiscountType::FIXED,
                'discount_value' => 20.00,
                'applies_to' => AppliesTo::MONTHLY,
                'min_months' => 6,
                'discount_months' => 3,
                'is_active' => true,
            ],
        ];

        foreach ($promotions as $promotionData) {
            $promotion = Promotion::create([
                ...$promotionData,
                'uuid' => Str::uuid()->toString(),
            ]);

            // Asociar con planes de fibra (excepto empresarial)
            $fiberPlans = Plan::where('technology', 'fiber')
                ->where('code', '!=', 'BUSINESS-100')
                ->pluck('id');

            $promotion->plans()->attach($fiberPlans);
        }
    }
}
