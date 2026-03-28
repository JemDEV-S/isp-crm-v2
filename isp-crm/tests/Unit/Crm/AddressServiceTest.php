<?php

namespace Tests\Unit\Crm;

use Modules\Crm\Services\AddressService;
use PHPUnit\Framework\TestCase;

class AddressServiceTest extends TestCase
{
    public function test_it_normalizes_address_fields_and_georeference_quality(): void
    {
        $service = new AddressService();

        $normalized = $service->normalize([
            'street' => '  av. los   olivos ',
            'district' => ' san juan de lurigancho ',
            'city' => ' lima ',
            'province' => ' lima ',
            'reference' => '  frente al parque ',
            'address_reference' => ' porton negro ',
            'latitude' => -12.045654,
            'longitude' => -77.031234,
        ]);

        $this->assertSame('Av. Los Olivos', $normalized['street']);
        $this->assertSame('San Juan De Lurigancho', $normalized['district']);
        $this->assertSame('Frente al parque', $normalized['reference']);
        $this->assertSame('porton negro', $normalized['address_reference']);
        $this->assertSame('high', $normalized['georeference_quality']);
    }

    public function test_it_rejects_invalid_georeference(): void
    {
        $service = new AddressService();

        $this->assertFalse($service->validateGeoreference(0.0, 0.0));
        $this->assertFalse($service->validateGeoreference(120.0, -77.0));
        $this->assertTrue($service->validateGeoreference(-12.04, -77.03));
    }
}
