<?php

declare(strict_types=1);

namespace Modules\Finance\Console;

use Illuminate\Console\Command;
use Modules\Finance\Entities\BillingJobRun;
use Modules\Finance\Jobs\GenerateMonthlyInvoicesJob;
use Modules\Finance\Services\RecurringBillingService;
use Modules\Subscription\Entities\Subscription;

class GenerateInvoicesCommand extends Command
{
    protected $signature = 'finance:generate-invoices
        {--period= : Periodo en formato YYYY-MM (default: mes actual)}
        {--sync : Ejecutar de forma sincrona en vez de via queue}
        {--subscription= : Facturar una sola suscripcion por ID}';

    protected $description = 'Genera facturas recurrentes para el periodo indicado';

    public function handle(RecurringBillingService $billingService): int
    {
        $period = $this->option('period') ?? now()->format('Y-m');

        if ($subscriptionId = $this->option('subscription')) {
            $subscription = Subscription::findOrFail($subscriptionId);
            $invoice = $billingService->billSubscription($subscription, $period, 'manual');
            $this->info("Factura {$invoice->invoice_number} generada.");
            return self::SUCCESS;
        }

        if ($this->option('sync')) {
            $this->info("Iniciando facturacion sincrona para periodo {$period}...");
            $jobRun = $billingService->runBillingCycle($period, 'artisan', auth()->id());
            $this->printSummary($jobRun);
            return self::SUCCESS;
        }

        GenerateMonthlyInvoicesJob::dispatch($period, 'artisan', auth()->id());
        $this->info("Job de facturacion encolado para periodo {$period}.");
        return self::SUCCESS;
    }

    protected function printSummary(BillingJobRun $jobRun): void
    {
        $this->newLine();
        $this->info('=== Resumen de Facturacion ===');
        $this->table(
            ['Metrica', 'Valor'],
            [
                ['Periodo', $jobRun->billing_period],
                ['Estado', $jobRun->status],
                ['Procesadas', $jobRun->total_processed],
                ['Facturadas', $jobRun->total_invoiced],
                ['Omitidas', $jobRun->total_skipped],
                ['Fallidas', $jobRun->total_failed],
                ['Duracion', ($jobRun->metadata['duration_seconds'] ?? 0) . 's'],
            ],
        );
    }
}
