<?php

declare(strict_types=1);

namespace Modules\FieldOps\app\Services;

use Modules\Crm\Entities\Address;
use Modules\FieldOps\app\Models\TechnicianLocation;
use Modules\FieldOps\app\Models\WorkOrder;
use Modules\FieldOps\app\Models\WorkOrderException;
use Modules\FieldOps\app\Enums\ExceptionType;

class GeofenceService
{
    public function validateLocation(float $lat, float $lng, int $addressId, int $radiusMeters = 100): bool
    {
        $address = Address::findOrFail($addressId);

        if (!$address->hasCoordinates()) {
            return false;
        }

        $distance = $this->calculateDistance(
            $lat,
            $lng,
            (float) $address->latitude,
            (float) $address->longitude
        );

        return $distance <= $radiusMeters;
    }

    public function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000;

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLng / 2) * sin($dLng / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    public function recordViolation(int $workOrderId, float $distance): void
    {
        WorkOrderException::create([
            'work_order_id' => $workOrderId,
            'exception_type' => ExceptionType::FAILED_VALIDATION,
            'causal_code' => 'geofence_violation',
            'description' => 'Tecnico fuera del radio permitido. Distancia: ' . round($distance, 2) . ' metros.',
        ]);
    }

    public function validateWorkOrderArrival(WorkOrder $workOrder, TechnicianLocation $location, int $radiusMeters = 100): array
    {
        $address = Address::findOrFail($workOrder->address_id);

        if (!$address->hasCoordinates()) {
            return [
                'valid' => false,
                'distance' => null,
                'reason' => 'La direccion de la orden no tiene coordenadas',
            ];
        }

        $distance = $this->calculateDistance(
            (float) $location->latitude,
            (float) $location->longitude,
            (float) $address->latitude,
            (float) $address->longitude
        );

        if ($distance > $radiusMeters) {
            $this->recordViolation($workOrder->id, $distance);
        }

        return [
            'valid' => $distance <= $radiusMeters,
            'distance' => $distance,
            'reason' => $distance <= $radiusMeters ? null : 'Tecnico fuera del radio permitido',
        ];
    }
}
