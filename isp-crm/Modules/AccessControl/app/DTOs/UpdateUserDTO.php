<?php

declare(strict_types=1);

namespace Modules\AccessControl\DTOs;

use Modules\AccessControl\Http\Requests\UpdateUserRequest;

final readonly class UpdateUserDTO
{
    public function __construct(
        public string $name,
        public string $email,
        public ?string $password = null,
        public ?string $phone = null,
        public ?int $zoneId = null,
        public bool $isActive = true,
        public array $roleIds = [],
    ) {
    }

    public static function fromRequest(UpdateUserRequest $request): self
    {
        return new self(
            name: $request->validated('name'),
            email: $request->validated('email'),
            password: $request->validated('password'),
            phone: $request->validated('phone'),
            zoneId: $request->validated('zone_id'),
            isActive: $request->validated('is_active', true),
            roleIds: $request->validated('roles', []),
        );
    }
}
