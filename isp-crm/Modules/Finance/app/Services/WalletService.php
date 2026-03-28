<?php

declare(strict_types=1);

namespace Modules\Finance\Services;

use Modules\Crm\Entities\Customer;
use Modules\Finance\Entities\Wallet;

class WalletService
{
    public function createForCustomer(Customer $customer): Wallet
    {
        return Wallet::firstOrCreate(
            ['customer_id' => $customer->id],
            [
                'balance' => 0,
                'credit_limit' => (float) ($customer->credit_limit ?? 0),
                'status' => 'active',
            ]
        );
    }

    public function getOrCreateForCustomer(int $customerId): Wallet
    {
        $customer = Customer::findOrFail($customerId);

        return $this->createForCustomer($customer);
    }
}
