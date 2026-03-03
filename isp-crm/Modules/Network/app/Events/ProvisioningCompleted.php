<?php

declare(strict_types=1);

namespace Modules\Network\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Network\DTOs\ProvisionResultDTO;

class ProvisioningCompleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int $subscriptionId,
        public readonly ProvisionResultDTO $result,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('admin.provisioning'),
            new PrivateChannel("subscription.{$this->subscriptionId}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'provisioning.completed';
    }

    public function broadcastWith(): array
    {
        return [
            'subscription_id' => $this->subscriptionId,
            'ip_address' => $this->result->ipAddress?->address,
            'success' => $this->result->success,
        ];
    }
}
