<?php

namespace Tests\Unit\FieldOps;

use Modules\FieldOps\app\Services\GeofenceService;
use PHPUnit\Framework\TestCase;

class GeofenceServiceTest extends TestCase
{
    public function test_it_returns_zero_distance_for_same_coordinates(): void
    {
        $service = new GeofenceService();

        $distance = $service->calculateDistance(-12.0464, -77.0428, -12.0464, -77.0428);

        $this->assertEquals(0.0, $distance);
    }

    public function test_it_calculates_reasonable_distance_between_points(): void
    {
        $service = new GeofenceService();

        $distance = $service->calculateDistance(-12.0464, -77.0428, -12.0469, -77.0428);

        $this->assertGreaterThan(40, $distance);
        $this->assertLessThan(70, $distance);
    }
}
