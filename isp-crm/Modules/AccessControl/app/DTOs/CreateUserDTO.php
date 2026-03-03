<?php

declare(strict_types=1);

namespace Modules\AccessControl\DTOs;

use Modules\AccessControl\Http\Requests\StoreUserRequest;

final readonly class CreateUserDTO
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
        public ?string $phone = null,
        public ?int $zoneId = null,
        public bool $isActive = true,
        public array $roleIds = [],
    ) {
    }

    public static function fromRequest(StoreUserRequest $request): self
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
