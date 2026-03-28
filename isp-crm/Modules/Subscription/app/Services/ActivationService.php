<?php

declare(strict_types=1);

namespace Modules\Subscription\Services;

use Illuminate\Support\Facades\DB;
use Modules\Network\Services\ProvisioningService;
use Modules\Subscription\Entities\Subscription;
use Modules\Subscription\Enums\ProvisionStatus;
use Modules\Subscription\Enums\SubscriptionStatus;
use Modules\Subscription\Events\ServiceInstanceProvisioned;
use Modules\Subscription\Events\SubscriptionActivated;
use Modules\Subscription\Events\SubscriptionActivationFailed;
use Modules\Subscription\Events\SubscriptionReadyForActivation;

class ActivationService
{
    public function __construct(
        protected ProvisioningService $provisioningService,
    ) {}

    public function activate(Subscription $subscription): Subscription
    {
        $subscription = $subscription->fresh([
            'customer',
            'plan.parameters',
            'address',
            'addons',
            'promotion',
            'documents',
            'serviceInstance.napPort.napBox',
        ]);

        $this->assertCanActivate($subscription);

        return DB::transaction(function () use ($subscription) {
            $serviceInstance = $subscription->serviceInstance;

            event(new SubscriptionReadyForActivation($subscription));

            $serviceInstance->update([
                'provision_status' => ProvisionStatus::PROVISIONING,
            ]);

            try {
                $result = $this->provisioningService->provisionSubscription($subscription);

                $serviceInstance = $this->provisioningService->updateServiceInstanceProvisionData($serviceInstance, $result);
                $metadata = $serviceInstance->metadata ?? [];
                $metadata['last_activation_attempt_at'] = now()->toIso8601String();

                $serviceInstance->update([
                    'provision_status' => ProvisionStatus::ACTIVE,
                    'metadata' => $metadata,
                ]);

                $oldStatus = $subscription->status;

                $subscription->statusHistory()->create([
                    'subscription_id' => $subscription->id,
                    'from_status' => $oldStatus,
                    'to_status' => SubscriptionStatus::ACTIVE,
                    'reason' => 'Activacion completada con aprovisionamiento de red',
                    'user_id' => auth()->id(),
                ]);

                $subscription->update([
                    'status' => SubscriptionStatus::ACTIVE,
                    'start_date' => $subscription->start_date ?? now()->toDateString(),
                ]);

                event(new ServiceInstanceProvisioned(
                    $subscription->fresh(),
                    $serviceInstance->fresh(),
                    $result->toArray(),
                ));

                event(new SubscriptionActivated($subscription->fresh(['serviceInstance'])));

                return $subscription->fresh([
                    'customer',
                    'plan',
                    'address',
                    'serviceInstance.ipAddress',
                    'serviceInstance.napPort',
                    'addons',
                    'documents',
                ]);
            } catch (\Throwable $exception) {
                $metadata = $serviceInstance->metadata ?? [];
                $metadata['activation_failed_reason'] = $exception->getMessage();
                $metadata['last_activation_attempt_at'] = now()->toIso8601String();

                $serviceInstance->update([
                    'provision_status' => ProvisionStatus::FAILED,
                    'metadata' => $metadata,
                ]);

                event(new SubscriptionActivationFailed(
                    $subscription->fresh(['serviceInstance']),
                    $exception->getMessage(),
                ));

                throw new \DomainException('No se pudo activar la suscripcion: ' . $exception->getMessage(), 0, $exception);
            }
        });
    }

    public function canActivate(Subscription $subscription): bool
    {
        return count($this->getActivationReadiness($subscription)['issues']) === 0;
    }

    public function getActivationReadiness(Subscription $subscription): array
    {
        $subscription = $subscription->fresh(['plan.parameters', 'documents', 'serviceInstance.napPort.napBox']);
        $issues = [];

        if ($subscription->status !== SubscriptionStatus::PENDING_INSTALLATION) {
            $issues[] = 'La suscripcion no esta en estado pending_installation';
        }

        if (empty($subscription->commercial_snapshot)) {
            $issues[] = 'Falta snapshot comercial congelado';
        }

        if (!$subscription->hasAcceptedTerms()) {
            $issues[] = 'Falta aceptacion de terminos';
        }

        if (!$subscription->documents()->exists()) {
            $issues[] = 'No hay documentos asociados a la suscripcion';
        } elseif ($subscription->documents()->whereNull('validated_at')->exists()) {
            $issues[] = 'Existen documentos sin validar';
        }

        if (!$subscription->serviceInstance) {
            $issues[] = 'No existe instancia de servicio';
        } else {
            if (empty($subscription->serviceInstance->pppoe_user) || empty($subscription->serviceInstance->pppoe_password)) {
                $issues[] = 'La instancia de servicio no tiene credenciales PPPoE completas';
            }
        }

        if (!$subscription->plan || !$subscription->plan->ip_pool_id) {
            $issues[] = 'El plan no tiene ip_pool_id configurado';
        }

        return [
            'ready' => empty($issues),
            'issues' => $issues,
        ];
    }

    public function rollbackActivation(int $subscriptionId, string $reason): void
    {
        $subscription = Subscription::with(['serviceInstance'])->findOrFail($subscriptionId);

        DB::transaction(function () use ($subscription, $reason) {
            $serviceInstance = $subscription->serviceInstance;

            if ($serviceInstance?->ip_address_id || $serviceInstance?->nap_port_id) {
                $this->provisioningService->deprovisionSubscription($subscription);
            }

            if ($serviceInstance) {
                $metadata = $serviceInstance->metadata ?? [];
                $metadata['rollback_reason'] = $reason;

                $serviceInstance->update([
                    'ip_address_id' => null,
                    'nap_port_id' => null,
                    'provision_status' => ProvisionStatus::FAILED,
                    'provision_data' => null,
                    'metadata' => $metadata,
                ]);
            }

            $oldStatus = $subscription->status;

            $subscription->statusHistory()->create([
                'subscription_id' => $subscription->id,
                'from_status' => $oldStatus,
                'to_status' => SubscriptionStatus::PENDING_INSTALLATION,
                'reason' => 'Rollback de activacion: ' . $reason,
                'user_id' => auth()->id(),
            ]);

            $subscription->update([
                'status' => SubscriptionStatus::PENDING_INSTALLATION,
            ]);
        });
    }

    protected function assertCanActivate(Subscription $subscription): void
    {
        if ($subscription->status !== SubscriptionStatus::PENDING_INSTALLATION) {
            throw new \DomainException('Solo se pueden activar suscripciones pendientes de instalacion');
        }

        if (empty($subscription->commercial_snapshot)) {
            throw new \DomainException('La suscripcion no tiene snapshot comercial congelado');
        }

        if (!$subscription->hasAcceptedTerms()) {
            throw new \DomainException('La suscripcion no tiene aceptacion de terminos registrada');
        }

        if (!$subscription->documents()->exists() || $subscription->documents()->whereNull('validated_at')->exists()) {
            throw new \DomainException('La suscripcion requiere documentos validados antes de activarse');
        }

        if (!$subscription->serviceInstance) {
            throw new \DomainException('La suscripcion no tiene instancia de servicio asociada');
        }

        if (!$subscription->plan || !$subscription->plan->ip_pool_id) {
            throw new \DomainException('El plan no tiene pool de IP configurado para aprovisionamiento');
        }

        if (empty($subscription->serviceInstance->pppoe_user) || empty($subscription->serviceInstance->pppoe_password)) {
            throw new \DomainException('La instancia de servicio no tiene credenciales PPPoE listas');
        }
    }
}
