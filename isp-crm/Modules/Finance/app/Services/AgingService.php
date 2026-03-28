<?php

declare(strict_types=1);

namespace Modules\Finance\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Finance\Entities\Invoice;
use Modules\Finance\Enums\AgingBucket;
use Modules\Finance\Enums\InvoiceStatus;

class AgingService
{
    public function refreshAll(): int
    {
        $count = 0;

        Invoice::overdue()->cursor()->each(function (Invoice $invoice) use (&$count) {
            $this->refresh($invoice);
            $count++;
        });

        return $count;
    }

    public function refresh(Invoice $invoice): void
    {
        $daysOverdue = (int) Carbon::parse($invoice->due_date)->diffInDays(now(), false);
        if ($daysOverdue < 0) {
            $daysOverdue = 0;
        }

        $bucket = AgingBucket::fromDays($daysOverdue);

        $invoice->update([
            'days_overdue' => $daysOverdue,
            'aging_bucket' => $bucket,
        ]);
    }

    public function getAgingReport(array $filters = []): array
    {
        $query = Invoice::overdue();

        if (!empty($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }

        $results = $query->select('aging_bucket', DB::raw('COUNT(*) as count'), DB::raw('SUM(total) as total_amount'))
            ->groupBy('aging_bucket')
            ->get();

        $report = [];
        foreach (AgingBucket::cases() as $bucket) {
            $row = $results->firstWhere('aging_bucket', $bucket->value);
            $report[] = [
                'bucket' => $bucket->value,
                'count' => $row ? (int) $row->count : 0,
                'total_amount' => $row ? (float) $row->total_amount : 0.00,
            ];
        }

        return $report;
    }

    public function getAgingSummary(): array
    {
        return Invoice::overdue()
            ->join('subscriptions', 'invoices.subscription_id', '=', 'subscriptions.id')
            ->join('customers', 'invoices.customer_id', '=', 'customers.id')
            ->select(
                'invoices.aging_bucket',
                DB::raw('COUNT(DISTINCT invoices.customer_id) as customers'),
                DB::raw('COUNT(*) as invoices_count'),
                DB::raw('SUM(invoices.total) as total_debt'),
            )
            ->groupBy('invoices.aging_bucket')
            ->get()
            ->toArray();
    }
}
