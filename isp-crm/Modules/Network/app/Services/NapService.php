<?php

declare(strict_types=1);

namespace Modules\Network\Services;

use Modules\Core\Exceptions\BusinessLogicException;
use Modules\Core\Services\BaseService;
use Modules\Network\Entities\NapBox;
use Modules\Network\Entities\NapPort;

class NapService extends BaseService
{
    /**
     * Assign a free port from a NAP box to a subscription.
     */
    public function assignPort(int $napBoxId, int $subscriptionId, string $label = null): NapPort
    {
        return $this->transaction(function () use ($napBoxId, $subscriptionId, $label) {
            $napBox = NapBox::findOrFail($napBoxId);

            if (!$napBox->isActive()) {
                throw new BusinessLogicException('La caja NAP no está activa');
            }

            $port = $napBox->freePorts()->lockForUpdate()->first();

            if (!$port) {
                throw new BusinessLogicException('No hay puertos disponibles en esta caja NAP');
            }

            $port->assignTo($subscriptionId, $label);

            $this->log("Puerto NAP {$napBox->code}:{$port->port_number} asignado", [
                'nap_box_id' => $napBoxId,
                'port_id' => $port->id,
                'subscription_id' => $subscriptionId,
            ]);

            return $port->fresh();
        });
    }

    /**
     * Release a NAP port.
     */
    public function releasePort(int $portId): NapPort
    {
        return $this->transaction(function () use ($portId) {
            $port = NapPort::findOrFail($portId);

            if ($port->isAvailable()) {
                throw new BusinessLogicException('El puerto ya está libre');
            }

            $subscriptionId = $port->subscription_id;
            $port->release();

            $this->log("Puerto NAP liberado", [
                'port_id' => $port->id,
                'previous_subscription_id' => $subscriptionId,
            ]);

            return $port->fresh();
        });
    }

    /**
     * Find nearest NAP boxes with available ports.
     */
    public function findNearestAvailable(float $latitude, float $longitude, int $radiusMeters = 500, int $limit = 5): array
    {
        $napBoxes = NapBox::where('status', 'active')
            ->whereHas('ports', function ($query) {
                $query->where('status', 'free');
            })
            ->get()
            ->map(function ($nap) use ($latitude, $longitude) {
                $nap->distance = $nap->distanceTo($latitude, $longitude);
                return $nap;
            })
            ->filter(function ($nap) use ($radiusMeters) {
                return $nap->distance <= $radiusMeters;
            })
            ->sortBy('distance')
            ->take($limit)
            ->values()
            ->toArray();

        return $napBoxes;
    }

    /**
     * Get NAP box statistics.
     */
    public function getNapStats(int $napBoxId): array
    {
        $napBox = NapBox::with(['ports'])->findOrFail($napBoxId);

        return [
            'total_ports' => $napBox->total_ports,
            'free_ports' => $napBox->freePorts()->count(),
            'occupied_ports' => $napBox->occupiedPorts()->count(),
            'reserved_ports' => $napBox->ports()->where('status', 'reserved')->count(),
            'damaged_ports' => $napBox->ports()->where('status', 'damaged')->count(),
            'occupancy_percentage' => $napBox->occupancyPercentage(),
        ];
    }

    /**
     * Check if coordinates have coverage.
     */
    public function checkCoverage(float $latitude, float $longitude, int $radiusMeters = 500): array
    {
        $nearest = $this->findNearestAvailable($latitude, $longitude, $radiusMeters, 1);

        return [
            'has_coverage' => count($nearest) > 0,
            'nearest_nap' => $nearest[0] ?? null,
            'distance_meters' => $nearest[0]['distance'] ?? null,
        ];
    }
}
