<?php

declare(strict_types=1);

namespace Modules\Finance\Services;

use Modules\Finance\DTOs\RegisterPaymentDTO;
use Modules\Finance\Entities\PaymentWebhookLog;
use Modules\Finance\Enums\PaymentChannel;
use Modules\Finance\Events\WebhookDuplicateDetected;

class PaymentWebhookService
{
    public function __construct(
        protected PaymentService $paymentService,
    ) {}

    public function process(string $gateway, array $payload, string $signature, string $ip): PaymentWebhookLog
    {
        $externalId = $this->extractExternalId($gateway, $payload);
        $eventType = $this->extractEventType($gateway, $payload);

        $log = PaymentWebhookLog::create([
            'gateway' => $gateway,
            'event_type' => $eventType,
            'external_id' => $externalId,
            'payload' => $payload,
            'signature' => $signature,
            'ip_address' => $ip,
            'status' => 'received',
        ]);

        // Validar firma
        if (! $this->validateSignature($gateway, $payload, $signature)) {
            $log->update([
                'status' => 'rejected',
                'processing_result' => 'Firma inválida',
                'processed_at' => now(),
            ]);

            return $log;
        }

        // Verificar duplicado
        if ($externalId && $this->isDuplicate($gateway, $externalId)) {
            $log->update([
                'status' => 'duplicate',
                'processing_result' => 'Webhook duplicado detectado',
                'processed_at' => now(),
            ]);

            event(new WebhookDuplicateDetected($log));

            return $log;
        }

        try {
            $dto = $this->mapToPaymentDTO($gateway, $payload);
            $payment = $this->paymentService->registerPayment($dto);

            $log->update([
                'status' => 'processed',
                'payment_id' => $payment->id,
                'processing_result' => "Pago #{$payment->id} creado",
                'processed_at' => now(),
            ]);
        } catch (\Throwable $e) {
            $log->update([
                'status' => 'failed',
                'processing_result' => $e->getMessage(),
                'processed_at' => now(),
            ]);
        }

        return $log;
    }

    protected function validateSignature(string $gateway, array $payload, string $signature): bool
    {
        $secret = config("finance.webhooks.gateways.{$gateway}.secret");

        if (! $secret) {
            return true; // No se configuró firma, se acepta
        }

        $computed = hash_hmac('sha256', json_encode($payload), $secret);

        return hash_equals($computed, $signature);
    }

    protected function isDuplicate(string $gateway, string $externalId): bool
    {
        $replayMinutes = config('finance.webhooks.replay_protection_minutes', 5);

        return PaymentWebhookLog::where('gateway', $gateway)
            ->where('external_id', $externalId)
            ->where('status', 'processed')
            ->where('created_at', '>=', now()->subMinutes($replayMinutes))
            ->exists();
    }

    protected function mapToPaymentDTO(string $gateway, array $payload): RegisterPaymentDTO
    {
        // Mapeo genérico — cada gateway debería tener su mapper específico
        return new RegisterPaymentDTO(
            customerId: (int) ($payload['customer_id'] ?? 0),
            amount: (float) ($payload['amount'] ?? 0),
            method: $payload['method'] ?? 'credit_card',
            channel: PaymentChannel::WEBHOOK->value,
            invoiceId: isset($payload['invoice_id']) ? (int) $payload['invoice_id'] : null,
            reference: $payload['reference'] ?? null,
            externalId: $payload['external_id'] ?? $payload['id'] ?? null,
            idempotencyKey: $gateway . ':' . ($payload['external_id'] ?? $payload['id'] ?? uniqid()),
            gatewayResponse: $payload,
        );
    }

    protected function extractExternalId(string $gateway, array $payload): ?string
    {
        return $payload['external_id'] ?? $payload['id'] ?? null;
    }

    protected function extractEventType(string $gateway, array $payload): string
    {
        return $payload['event_type'] ?? $payload['type'] ?? 'payment';
    }
}
