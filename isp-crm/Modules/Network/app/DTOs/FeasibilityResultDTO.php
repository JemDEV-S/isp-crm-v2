<?php

declare(strict_types=1);

namespace Modules\Network\DTOs;

use Modules\Network\Entities\NapBox;

final readonly class FeasibilityResultDTO
{
    public function __construct(
        public bool $isFeasible,
        public ?NapBox $nearestNap = null,
        public ?float $distanceMeters = null,
        public ?string $reason = null,
        public array $availableNaps = [],
    ) {}

    public static function feasible(NapBox $nearestNap, float $distanceMeters, array $availableNaps = []): self
    {
        return new self(
            isFeasible: true,
            nearestNap: $nearestNap,
            distanceMeters: $distanceMeters,
            availableNaps: $availableNaps,
        );
    }

    public static function notFeasible(string $reason): self
    {
        return new self(
            isFeasible: false,
            reason: $reason,
        );
    }

    public function toArray(): array
    {
        return [
            'is_feasible' => $this->isFeasible,
            'nearest_nap' => $this->nearestNap ? [
                'id' => $this->nearestNap->id,
                'code' => $this->nearestNap->code,
                'name' => $this->nearestNap->name,
                'free_ports' => $this->nearestNap->freePorts()->count(),
            ] : null,
            'distance_meters' => $this->distanceMeters,
            'reason' => $this->reason,
            'available_naps_count' => count($this->availableNaps),
        ];
    }
}
