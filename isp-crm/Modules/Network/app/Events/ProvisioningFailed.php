<?php

declare(strict_types=1);

namespace Modules\Network\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProvisioningFailed implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int $subscriptionId,
        public readonly string $error,
        public readonly array $details = [],
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
        return 'provisioning.failed';
    }

    public function broadcastWith(): array
    {
        return [
            'subscription_id' => $this->subscriptionId,
            'error' => $this->error,
            'details' => $this->details,
        ];
    }
}
