<?php

declare(strict_types=1);

namespace Modules\AccessControl\DTOs;

use Modules\AccessControl\Http\Requests\StoreRoleRequest;

final readonly class CreateRoleDTO
{
    public function __construct(
        public string $code,
        public string $name,
        public ?string $description = null,
        public bool $isActive = true,
        public array $permissionIds = [],
    ) {
    }

    public static function fromRequest(StoreRoleRequest $request): self
    {
        return new self(
            code: $request->validated('code'),
            name: $request->validated('name'),
            description: $request->validated('description'),
            isActive: $request->validated('is_active', true),
            permissionIds: $request->validated('permissions', []),
        );
    }
}
