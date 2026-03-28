<?php

namespace Modules\Subscription\Console;

use Illuminate\Console\Command;
use Modules\Subscription\Jobs\ProcessScheduledPlanChangesJob;
use Modules\Subscription\Services\PlanChangeService;

class ProcessPlanChangesCommand extends Command
{
    protected $signature = 'subscription:process-plan-changes
        {--sync : Ejecutar sincrónicamente}';

    protected $description = 'Procesar cambios de plan programados cuya fecha efectiva ha llegado';

    public function handle(PlanChangeService $planChangeService): int
    {
        if ($this->option('sync')) {
            $processed = $planChangeService->processScheduledChanges();
            $this->info("Cambios de plan procesados: {$processed}");
        } else {
            ProcessScheduledPlanChangesJob::dispatch();
            $this->info('Job de procesamiento de cambios de plan despachado.');
        }

        return self::SUCCESS;
    }
}
