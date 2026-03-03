<?php

declare(strict_types=1);

namespace Modules\Network\DTOs;

use Modules\Network\Entities\IpAddress;
use Modules\Network\Entities\NapPort;

final readonly class ProvisionResultDTO
{
    public function __construct(
        public bool $success,
        public ?IpAddress $ipAddress = null,
        public ?NapPort $napPort = null,
        public ?string $pppoeUser = null,
        public ?string $error = null,
        public array $details = [],
    ) {}

    public static function success(
        IpAddress $ipAddress,
        ?NapPort $napPort = null,
        ?string $pppoeUser = null,
        array $details = []
    ): self {
        return new self(
            success: true,
            ipAddress: $ipAddress,
            napPort: $napPort,
            pppoeUser: $pppoeUser,
            details: $details,
        );
    }

    public static function failure(string $error, array $details = []): self
    {
        return new self(
            success: false,
            error: $error,
            details: $details,
        );
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'ip_address' => $this->ipAddress?->address,
            'ip_address_id' => $this->ipAddress?->id,
            'nap_port_id' => $this->napPort?->id,
            'nap_port_number' => $this->napPort?->port_number,
            'pppoe_user' => $this->pppoeUser,
            'error' => $this->error,
            'details' => $this->details,
        ];
    }
}
