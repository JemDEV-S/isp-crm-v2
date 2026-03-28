<?php

declare(strict_types=1);

namespace Modules\Finance\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Finance\Entities\WalletTransaction;

class WalletDebited
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly WalletTransaction $transaction,
        public readonly float $amount,
        public readonly string $concept,
    ) {}
}
