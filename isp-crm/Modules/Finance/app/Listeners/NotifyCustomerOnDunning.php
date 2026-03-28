<?php

declare(strict_types=1);

namespace Modules\Finance\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\Finance\Events\DunningStageTriggered;

class NotifyCustomerOnDunning
{
    public function handle(DunningStageTriggered $event): void
    {
        $execution = $event->execution;
        $stage = $execution->stage;

        if (!$stage) {
            return;
        }

        $channels = $stage->channels ?? [];

        foreach ($channels as $channel) {
            // TODO: Integrar con sistema de notificaciones real (email, SMS, WhatsApp)
            Log::info('Dunning notification queued', [
                'invoice_id' => $execution->invoice_id,
                'customer_id' => $execution->customer_id,
                'stage' => $stage->code,
                'channel' => $channel,
                'template' => $stage->template_code,
            ]);
        }
    }
}
