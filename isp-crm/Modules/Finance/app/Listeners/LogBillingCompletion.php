<?php

declare(strict_types=1);

namespace Modules\Finance\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\Finance\Events\InvoiceBatchCompleted;

class LogBillingCompletion
{
    public function handle(InvoiceBatchCompleted $event): void
    {
        $jobRun = $event->jobRun;

        Log::info("Ciclo de facturacion completado", [
            'job_run_id' => $jobRun->id,
            'billing_period' => $jobRun->billing_period,
            'status' => $jobRun->status,
            'total_processed' => $jobRun->total_processed,
            'total_invoiced' => $jobRun->total_invoiced,
            'total_skipped' => $jobRun->total_skipped,
            'total_failed' => $jobRun->total_failed,
            'duration_seconds' => $jobRun->metadata['duration_seconds'] ?? null,
        ]);

        // TODO: Implementar notificacion admin (email/Slack)
    }
}
