<?php

declare(strict_types=1);

namespace Modules\AccessControl\DTOs;

use Modules\AccessControl\Http\Requests\StoreZoneRequest;

final readonly class CreateZoneDTO
{
    public function __construct(
        public string $code,
        public string $name,
        public ?string $description = null,
        public ?int $parentId = null,
        public ?array $polygon = null,
        public bool $isActive = true,
    ) {
    }

    public static function fromRequest(StoreZoneRequest $request): self
    {
        return new self(
            code: $request->validated('code'),
            name: $request->validated('name'),
            description: $request->validated('description'),
            parentId: $request->validated('parent_id'),
            polygon: $request->validated('polygon'),
            isActive: $request->validated('is_active', true),
        );
    }
}
