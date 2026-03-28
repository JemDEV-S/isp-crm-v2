<?php

declare(strict_types=1);

namespace Modules\Finance\Services;

use Modules\Finance\Entities\CollectionCase;
use Modules\Finance\Enums\CollectionCaseStatus;
use Modules\Finance\Events\CollectionCaseOpened;

class CollectionCaseService
{
    public function open(int $customerId, ?int $subscriptionId, float $totalDebt): CollectionCase
    {
        $case = CollectionCase::create([
            'customer_id' => $customerId,
            'subscription_id' => $subscriptionId,
            'total_debt' => $totalDebt,
            'status' => CollectionCaseStatus::OPEN,
            'priority' => $this->determinePriority($totalDebt),
        ]);

        event(new CollectionCaseOpened($case));

        return $case;
    }

    public function assign(CollectionCase $case, int $userId): CollectionCase
    {
        $case->update([
            'assigned_to' => $userId,
            'status' => CollectionCaseStatus::IN_PROGRESS,
        ]);

        return $case->fresh();
    }

    public function sendToExternal(CollectionCase $case, string $agency): CollectionCase
    {
        $case->update([
            'status' => CollectionCaseStatus::SENT_EXTERNAL,
            'external_agency' => $agency,
            'sent_to_external_at' => now(),
        ]);

        return $case->fresh();
    }

    public function markRecovered(CollectionCase $case): CollectionCase
    {
        $case->update([
            'status' => CollectionCaseStatus::RECOVERED,
            'closed_at' => now(),
            'close_reason' => 'recovered',
        ]);

        return $case->fresh();
    }

    public function writeOff(CollectionCase $case, string $reason): CollectionCase
    {
        $case->update([
            'status' => CollectionCaseStatus::WRITTEN_OFF,
            'closed_at' => now(),
            'close_reason' => $reason,
        ]);

        return $case->fresh();
    }

    protected function determinePriority(float $totalDebt): string
    {
        return match (true) {
            $totalDebt >= 1000 => 'critical',
            $totalDebt >= 500 => 'high',
            $totalDebt >= 100 => 'medium',
            default => 'low',
        };
    }
}
