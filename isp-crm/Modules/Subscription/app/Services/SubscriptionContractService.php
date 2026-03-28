<?php

declare(strict_types=1);

namespace Modules\Subscription\Services;

use Modules\Catalog\Entities\Addon;
use Modules\Subscription\Entities\Subscription;
use Modules\Subscription\Entities\SubscriptionDocument;

class SubscriptionContractService
{
    /**
     * This service is the extraction seam for a future Contracts module.
     * For now it keeps contract-like concerns close to Subscription.
     */
    public function initializeForSubscription(Subscription $subscription): Subscription
    {
        $snapshot = $this->freezeCommercialSnapshot($subscription);

        $subscription->update([
            'commercial_snapshot' => $snapshot,
        ]);

        return $subscription->fresh(['documents']);
    }

    public function attachDocument(Subscription $subscription, array $data): SubscriptionDocument
    {
        return SubscriptionDocument::create([
            'subscription_id' => $subscription->id,
            'customer_id' => $subscription->customer_id,
            'document_type' => $data['document_type'],
            'document_number' => $data['document_number'] ?? null,
            'file_path' => $data['file_path'],
            'metadata' => $data['metadata'] ?? null,
        ]);
    }

    public function validateDocuments(Subscription $subscription, ?int $validatedBy = null): bool
    {
        $documents = $subscription->documents;

        if ($documents->isEmpty()) {
            return false;
        }

        foreach ($documents as $document) {
            if (empty($document->file_path)) {
                return false;
            }

            if ($document->validated_at === null) {
                $document->update([
                    'validated_at' => now(),
                    'validated_by' => $validatedBy,
                ]);
            }
        }

        return true;
    }

    public function recordAcceptance(
        Subscription $subscription,
        string $method,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): Subscription {
        if ($subscription->documents()->whereNull('validated_at')->exists() || !$subscription->documents()->exists()) {
            throw new \DomainException('No se pueden aceptar términos sin documentos validados');
        }

        $subscription->update([
            'terms_accepted_at' => now(),
            'acceptance_method' => $method,
            'acceptance_ip' => $ipAddress,
            'acceptance_user_agent' => $userAgent,
        ]);

        return $subscription->fresh(['documents']);
    }

    public function freezeCommercialSnapshot(Subscription $subscription): array
    {
        $subscription->loadMissing(['plan.parameters', 'addons', 'promotion']);

        return [
            'plan' => $subscription->plan ? [
                'id' => $subscription->plan->id,
                'name' => $subscription->plan->name,
                'price' => (float) $subscription->monthly_price,
                'installation_fee' => (float) $subscription->installation_fee,
                'parameters' => $subscription->plan->parameters->map(fn ($parameter) => [
                    'key' => $parameter->key,
                    'label' => $parameter->display_label,
                    'value' => $parameter->value,
                ])->values()->all(),
            ] : null,
            'addons' => $subscription->addons->map(fn (Addon $addon) => [
                'id' => $addon->id,
                'name' => $addon->name,
                'price' => (float) $addon->pivot->price,
            ])->values()->all(),
            'promotion' => $subscription->promotion ? [
                'id' => $subscription->promotion->id,
                'name' => $subscription->promotion->name,
                'discount_type' => $subscription->promotion->discount_type->value,
                'discount_value' => (float) $subscription->promotion->discount_value,
            ] : null,
            'billing_day' => $subscription->billing_day,
            'billing_cycle' => $subscription->billing_cycle->value,
            'contracted_months' => $subscription->contracted_months,
            'total_monthly_price' => (float) $subscription->getTotalMonthlyPrice(),
            'discount_percentage' => (float) $subscription->discount_percentage,
            'discount_months_remaining' => $subscription->discount_months_remaining,
            'estimated_first_invoice' => (float) ($subscription->installation_fee + $subscription->getTotalMonthlyPrice()),
            'frozen_at' => now()->toIso8601String(),
            'future_contract_module_ready' => true,
        ];
    }
}
