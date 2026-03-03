<?php

declare(strict_types=1);

namespace Modules\Network\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Network\Entities\Device;

class DeviceOnline implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Device $device,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('admin.network'),
            new PrivateChannel("node.{$this->device->node_id}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'device.online';
    }

    public function broadcastWith(): array
    {
        return [
            'device_id' => $this->device->id,
            'device_name' => $this->device->brand . ' ' . $this->device->model,
            'ip_address' => $this->device->ip_address,
            'node_id' => $this->device->node_id,
        ];
    }
}
