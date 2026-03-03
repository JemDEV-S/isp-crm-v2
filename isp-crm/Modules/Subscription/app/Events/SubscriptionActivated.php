<?php

declare(strict_types=1);

namespace Modules\Subscription\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Subscription\Entities\Subscription;

class SubscriptionActivated implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Subscription $subscription
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("customer.{$this->subscription->customer_id}"),
            new PrivateChannel('admin.subscriptions'),
        ];
    }
}
