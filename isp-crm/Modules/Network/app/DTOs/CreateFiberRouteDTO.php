<?php

declare(strict_types=1);

namespace Modules\Network\DTOs;

final readonly class CreateFiberRouteDTO
{
    public function __construct(
        public int $fromNodeId,
        public int $toNodeId,
        public ?int $distanceMeters = null,
        public ?int $fiberCount = null,
        public ?array $routeGeojson = null,
        public string $status = 'active',
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            fromNodeId: (int) $data['from_node_id'],
            toNodeId: (int) $data['to_node_id'],
            distanceMeters: isset($data['distance_meters']) ? (int) $data['distance_meters'] : null,
            fiberCount: isset($data['fiber_count']) ? (int) $data['fiber_count'] : null,
            routeGeojson: $data['route_geojson'] ?? null,
            status: $data['status'] ?? 'active',
        );
    }

    public function toArray(): array
    {
        return [
            'from_node_id' => $this->fromNodeId,
            'to_node_id' => $this->toNodeId,
            'distance_meters' => $this->distanceMeters,
            'fiber_count' => $this->fiberCount,
            'route_geojson' => $this->routeGeojson,
            'status' => $this->status,
        ];
    }
}
