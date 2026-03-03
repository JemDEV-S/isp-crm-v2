<?php

declare(strict_types=1);

namespace Modules\AccessControl\DTOs;

use Modules\AccessControl\Http\Requests\UpdateRoleRequest;

final readonly class UpdateRoleDTO
{
    public function __construct(
        public string $name,
        public ?string $description = null,
        public bool $isActive = true,
        public array $permissionIds = [],
    ) {
    }

    public static function fromRequest(UpdateRoleRequest $request): self
    {
        return new self(
            name: $request->validated('name'),
            description: $request->validated('description'),
            isActive: $request->validated('is_active', true),
            permissionIds: $request->validated('permissions', []),
        );
    }
}
