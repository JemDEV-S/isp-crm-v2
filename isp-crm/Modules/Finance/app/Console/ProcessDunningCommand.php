<?php

declare(strict_types=1);

namespace Modules\Finance\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Modules\Finance\Entities\Invoice;
use Modules\Finance\Jobs\ProcessDunningJob;
use Modules\Finance\Services\DunningService;

class ProcessDunningCommand extends Command
{
    protected $signature = 'finance:process-dunning
        {--sync : Ejecutar de forma síncrona}
        {--invoice= : Procesar una sola factura por ID}
        {--dry-run : Simular sin ejecutar acciones}';

    protected $description = 'Procesa el motor de dunning para facturas vencidas';

    public function handle(DunningService $dunningService): int
    {
        if (!config('finance.dunning.enabled', true)) {
            $this->warn('El motor de dunning está deshabilitado.');
            return self::SUCCESS;
        }

        $invoiceId = $this->option('invoice');
        $isDryRun = $this->option('dry-run');
        $isSync = $this->option('sync');

        if ($invoiceId) {
            return $this->processSingleInvoice($dunningService, (int) $invoiceId, $isDryRun);
        }

        if (!$isSync) {
            ProcessDunningJob::dispatch();
            $this->info('Job de dunning despachado a la cola.');
            return self::SUCCESS;
        }

        $this->info('Iniciando procesamiento de dunning...');

        if ($isDryRun) {
            $eligible = Invoice::dunningEligible()->count();
            $this->info("Modo simulación: {$eligible} facturas elegibles para dunning.");
            return self::SUCCESS;
        }

        $jobRunId = (string) Str::uuid();
        $result = $dunningService->processAll($jobRunId);

        $this->info("Dunning completado (Run: {$result->jobRunId})");
        $this->table(
            ['Métrica', 'Valor'],
            [
                ['Procesadas', $result->totalProcessed],
                ['Ejecutadas', $result->totalExecuted],
                ['Omitidas', $result->totalSkipped],
                ['Fallidas', $result->totalFailed],
                ['Fuera de rango', $result->totalOutOfRange],
            ]
        );

        return self::SUCCESS;
    }

    protected function processSingleInvoice(DunningService $dunningService, int $invoiceId, bool $isDryRun): int
    {
        $invoice = Invoice::find($invoiceId);

        if (!$invoice) {
            $this->error("Factura #{$invoiceId} no encontrada.");
            return self::FAILURE;
        }

        if ($isDryRun) {
            $eligible = $dunningService->isEligible($invoice);
            $exclusions = $dunningService->getExclusions($invoice);
            $this->info("Factura #{$invoiceId}: " . ($eligible ? 'Elegible' : 'No elegible'));
            if (!empty($exclusions)) {
                $this->warn('Exclusiones: ' . implode(', ', $exclusions));
            }
            return self::SUCCESS;
        }

        $jobRunId = (string) Str::uuid();
        $execution = $dunningService->processInvoice($invoice, $jobRunId);

        if ($execution) {
            $this->info("Factura #{$invoiceId}: {$execution->status} - {$execution->action_type->value}");
        } else {
            $this->info("Factura #{$invoiceId}: Sin acción (fuera de rango o ya procesada).");
        }

        return self::SUCCESS;
    }
}
