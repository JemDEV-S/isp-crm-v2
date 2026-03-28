<?php

declare(strict_types=1);

namespace Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Finance\Services\PaymentWebhookService;

class PaymentWebhookController extends Controller
{
    public function __construct(
        protected PaymentWebhookService $webhookService,
    ) {}

    public function handle(string $gateway, Request $request): JsonResponse
    {
        $payload = $request->all();
        $signature = $request->header('X-Webhook-Signature', '');
        $ip = $request->ip();

        $log = $this->webhookService->process($gateway, $payload, $signature, $ip);

        return response()->json([
            'status' => $log->status,
            'message' => $log->processing_result ?? 'Recibido',
        ]);
    }
}
