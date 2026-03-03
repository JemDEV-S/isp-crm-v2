<?php

declare(strict_types=1);

namespace Modules\Catalog\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Catalog\Entities\Promotion;

class PromotionUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Promotion $promotion,
        public readonly array $oldData
    ) {}
}
