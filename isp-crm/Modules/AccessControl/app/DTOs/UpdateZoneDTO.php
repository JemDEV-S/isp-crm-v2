<?php

declare(strict_types=1);

namespace Modules\AccessControl\DTOs;

use Modules\AccessControl\Http\Requests\UpdateZoneRequest;

final readonly class UpdateZoneDTO
{
    public function __construct(
        public string $name,
        public ?string $description = null,
        public ?int $parentId = null,
        public ?array $polygon = null,
        public bool $isActive = true,
    ) {
    }

    public static function fromRequest(UpdateZoneRequest $request): self
    {
        return new self(
            name: $request->validated('name'),
            description: $request->validated('description'),
            parentId: $request->validated('parent_id'),
            polygon: $request->validated('polygon'),
            isActive: $request->validated('is_active', true),
        );
    }
}
