<?php

declare(strict_types=1);

namespace Modules\Finance\Services;

use Modules\Finance\Entities\InvoiceDispute;
use Modules\Finance\Enums\DisputeStatus;
use Modules\Finance\Events\InvoiceDisputeOpened;
use Modules\Finance\Events\InvoiceDisputeResolved;

class InvoiceDisputeService
{
    public function open(array $data): InvoiceDispute
    {
        $dispute = InvoiceDispute::create([
            'invoice_id' => $data['invoice_id'],
            'customer_id' => $data['customer_id'],
            'reason_code' => $data['reason_code'],
            'description' => $data['description'],
            'status' => DisputeStatus::OPEN,
            'created_by' => auth()->id(),
        ]);

        event(new InvoiceDisputeOpened($dispute));

        return $dispute;
    }

    public function resolve(InvoiceDispute $dispute, string $resolution, string $status): InvoiceDispute
    {
        $disputeStatus = DisputeStatus::from($status);

        $dispute->update([
            'status' => $disputeStatus,
            'resolution' => $resolution,
            'resolved_by' => auth()->id(),
            'resolved_at' => now(),
        ]);

        event(new InvoiceDisputeResolved($dispute->fresh()));

        return $dispute->fresh();
    }

    public function close(InvoiceDispute $dispute): InvoiceDispute
    {
        $dispute->update([
            'status' => DisputeStatus::CLOSED,
            'resolved_at' => now(),
            'resolved_by' => auth()->id(),
        ]);

        return $dispute->fresh();
    }

    public function hasOpenDispute(int $invoiceId): bool
    {
        return InvoiceDispute::where('invoice_id', $invoiceId)
            ->whereIn('status', [DisputeStatus::OPEN, DisputeStatus::UNDER_REVIEW])
            ->exists();
    }
}
