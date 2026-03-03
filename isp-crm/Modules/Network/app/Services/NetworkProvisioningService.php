<?php

declare(strict_types=1);

namespace Modules\Network\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Core\Exceptions\BusinessLogicException;
use Modules\Core\Services\BaseService;
use Modules\Network\DTOs\FeasibilityResultDTO;
use Modules\Network\DTOs\ProvisionResultDTO;
use Modules\Network\DTOs\ProvisionServiceDTO;
use Modules\Network\Entities\Device;
use Modules\Network\Entities\IpAddress;
use Modules\Network\Entities\NapBox;
use Modules\Network\Entities\NapPort;
use Modules\Network\Enums\DeviceType;
use Modules\Network\Events\IpAssigned;
use Modules\Network\Events\IpReleased;
use Modules\Network\Events\ProvisioningCompleted;
use Modules\Network\Events\ProvisioningFailed;

class NetworkProvisioningService extends BaseService
{
    public function __construct(
        protected IpService $ipService,
        protected NapService $napService,
        protected RouterOsService $routerOsService,
        protected OltService $oltService,
    ) {}

    /**
     * Check technical feasibility for a given location.
     */
    public function checkFeasibility(float $latitude, float $longitude, int $radiusMeters = 500): FeasibilityResultDTO
    {
        $nearbyNaps = NapBox::where('status', 'active')
            ->whereHas('ports', fn($q) => $q->where('status', 'free'))
            ->get()
            ->map(function ($nap) use ($latitude, $longitude) {
                $nap->calculated_distance = $nap->distanceTo($latitude, $longitude);
                return $nap;
            })
            ->filter(fn($nap) => $nap->calculated_distance <= $radiusMeters)
            ->sortBy('calculated_distance');

        if ($nearbyNaps->isEmpty()) {
            return FeasibilityResultDTO::notFeasible('No hay cobertura en esta ubicación. No se encontraron NAPs disponibles en un radio de ' . $radiusMeters . ' metros.');
        }

        $nearest = $nearbyNaps->first();

        return FeasibilityResultDTO::feasible(
            nearestNap: $nearest,
            distanceMeters: $nearest->calculated_distance,
            availableNaps: $nearbyNaps->take(5)->toArray()
        );
    }

    /**
     * Provision network service for a subscription.
     */
    public function provisionService(ProvisionServiceDTO $dto): ProvisionResultDTO
    {
        return DB::transaction(function () use ($dto) {
            try {
                // 1. Assign IP address
                $ipAddress = $this->ipService->assignFreeIp($dto->ipPoolId, $dto->subscriptionId);

                event(new IpAssigned($ipAddress, $dto->subscriptionId));

                // 2. Assign NAP port if applicable
                $napPort = null;
                if ($dto->napBoxId) {
                    $napPort = $this->napService->assignPort(
                        $dto->napBoxId,
                        $dto->subscriptionId,
                        "SUB-{$dto->subscriptionId}"
                    );
                }

                // 3. Get the device from IP pool
                $device = $ipAddress->pool->device;

                // 4. Configure on network device
                if ($device) {
                    $this->configureOnDevice($device, $dto, $ipAddress);
                }

                // 5. Create provision result
                $result = ProvisionResultDTO::success(
                    ipAddress: $ipAddress,
                    napPort: $napPort,
                    pppoeUser: $dto->pppoeUser,
                    details: [
                        'ip_address' => $ipAddress->address,
                        'pool_name' => $ipAddress->pool->name,
                        'nap_port' => $napPort?->port_number,
                        'nap_box' => $napPort?->napBox->code,
                        'device_id' => $device?->id,
                        'provisioned_at' => now()->toIso8601String(),
                    ]
                );

                $this->log("Servicio aprovisionado para suscripción {$dto->subscriptionId}", [
                    'subscription_id' => $dto->subscriptionId,
                    'ip_address' => $ipAddress->address,
                    'nap_port_id' => $napPort?->id,
                ]);

                event(new ProvisioningCompleted($dto->subscriptionId, $result));

                return $result;

            } catch (\Exception $e) {
                $this->log("Error en aprovisionamiento: {$e->getMessage()}", [
                    'subscription_id' => $dto->subscriptionId,
                    'error' => $e->getMessage(),
                ], 'error');

                event(new ProvisioningFailed($dto->subscriptionId, $e->getMessage()));

                throw $e;
            }
        });
    }

    /**
     * Suspend network service for a subscription.
     */
    public function suspendService(int $subscriptionId, IpAddress $ipAddress, ?string $pppoeUser = null): bool
    {
        $device = $ipAddress->pool->device;

        if (!$device) {
            $this->log("No hay dispositivo asociado para suspender servicio", [
                'subscription_id' => $subscriptionId,
            ], 'warning');
            return true;
        }

        try {
            if ($device->type === DeviceType::ROUTER) {
                // Option 1: Disable PPPoE user
                if ($pppoeUser) {
                    $this->routerOsService->disablePppoeUser($device, $pppoeUser);
                }

                // Option 2: Add to blocked address list
                $this->routerOsService->addToAddressList(
                    $device,
                    'MOROSOS',
                    $ipAddress->address,
                    "Subscription: {$subscriptionId} - Suspendido por mora"
                );
            }

            $this->log("Servicio suspendido para suscripción {$subscriptionId}", [
                'subscription_id' => $subscriptionId,
                'ip_address' => $ipAddress->address,
                'device_id' => $device->id,
            ]);

            return true;
        } catch (\Exception $e) {
            $this->log("Error al suspender servicio: {$e->getMessage()}", [
                'subscription_id' => $subscriptionId,
                'error' => $e->getMessage(),
            ], 'error');

            throw new BusinessLogicException("Error al suspender servicio: {$e->getMessage()}");
        }
    }

    /**
     * Reactivate network service after suspension.
     */
    public function reactivateService(int $subscriptionId, IpAddress $ipAddress, ?string $pppoeUser = null): bool
    {
        $device = $ipAddress->pool->device;

        if (!$device) {
            return true;
        }

        try {
            if ($device->type === DeviceType::ROUTER) {
                // Enable PPPoE user
                if ($pppoeUser) {
                    $this->routerOsService->enablePppoeUser($device, $pppoeUser);
                }

                // Remove from blocked address list
                $this->routerOsService->removeFromAddressList(
                    $device,
                    'MOROSOS',
                    $ipAddress->address
                );
            }

            $this->log("Servicio reactivado para suscripción {$subscriptionId}", [
                'subscription_id' => $subscriptionId,
                'ip_address' => $ipAddress->address,
                'device_id' => $device->id,
            ]);

            return true;
        } catch (\Exception $e) {
            $this->log("Error al reactivar servicio: {$e->getMessage()}", [
                'subscription_id' => $subscriptionId,
                'error' => $e->getMessage(),
            ], 'error');

            throw new BusinessLogicException("Error al reactivar servicio: {$e->getMessage()}");
        }
    }

    /**
     * Deprovision network service (cancel/terminate).
     */
    public function deprovisionService(
        int $subscriptionId,
        ?int $ipAddressId = null,
        ?int $napPortId = null,
        ?string $pppoeUser = null,
        ?string $onuSerial = null
    ): bool {
        return DB::transaction(function () use ($subscriptionId, $ipAddressId, $napPortId, $pppoeUser, $onuSerial) {
            // 1. Release IP address
            if ($ipAddressId) {
                $ipAddress = IpAddress::find($ipAddressId);
                if ($ipAddress) {
                    $device = $ipAddress->pool->device;

                    // Remove from router
                    if ($device && $device->type === DeviceType::ROUTER && $pppoeUser) {
                        try {
                            $this->routerOsService->deletePppoeUser($device, $pppoeUser);
                            $this->routerOsService->removeFromAddressList($device, 'MOROSOS', $ipAddress->address);
                        } catch (\Exception $e) {
                            // Log but continue
                            $this->log("Error removiendo de router: {$e->getMessage()}", [], 'warning');
                        }
                    }

                    // Deauthorize ONU if applicable
                    if ($device && $device->type === DeviceType::OLT && $onuSerial) {
                        try {
                            $this->oltService->deauthorizeOnu($device, $onuSerial);
                        } catch (\Exception $e) {
                            $this->log("Error desautorizando ONU: {$e->getMessage()}", [], 'warning');
                        }
                    }

                    $this->ipService->releaseIp($ipAddressId);
                    event(new IpReleased($ipAddress));
                }
            }

            // 2. Release NAP port
            if ($napPortId) {
                $this->napService->releasePort($napPortId);
            }

            $this->log("Servicio desaprovisionado para suscripción {$subscriptionId}", [
                'subscription_id' => $subscriptionId,
                'ip_address_id' => $ipAddressId,
                'nap_port_id' => $napPortId,
            ]);

            return true;
        });
    }

    /**
     * Update network configuration for plan change.
     */
    public function updatePlanConfig(
        int $subscriptionId,
        IpAddress $ipAddress,
        string $newProfile,
        ?string $pppoeUser = null,
        ?string $onuSerial = null,
        ?int $vlanId = null
    ): bool {
        $device = $ipAddress->pool->device;

        if (!$device) {
            return true;
        }

        try {
            if ($device->type === DeviceType::ROUTER && $pppoeUser) {
                $this->routerOsService->updatePppoeProfile($device, $pppoeUser, $newProfile);
            }

            if ($device->type === DeviceType::OLT && $onuSerial) {
                $this->oltService->updateOnuProfile($device, $onuSerial, $newProfile, $vlanId);
            }

            $this->log("Configuración de plan actualizada para suscripción {$subscriptionId}", [
                'subscription_id' => $subscriptionId,
                'new_profile' => $newProfile,
                'device_id' => $device->id,
            ]);

            return true;
        } catch (\Exception $e) {
            throw new BusinessLogicException("Error al actualizar configuración: {$e->getMessage()}");
        }
    }

    /**
     * Generate a unique PPPoE username.
     */
    public function generatePppoeUser(int $subscriptionId, ?string $prefix = null): string
    {
        $prefix = $prefix ?? config('network.pppoe_prefix', 'noretel');
        return sprintf('%s_%06d', $prefix, $subscriptionId);
    }

    /**
     * Generate a random PPPoE password.
     */
    public function generatePppoePassword(int $length = 12): string
    {
        return Str::random($length);
    }

    // =========== Protected Methods ===========

    /**
     * Configure service on network device.
     */
    protected function configureOnDevice(Device $device, ProvisionServiceDTO $dto, IpAddress $ipAddress): void
    {
        if ($device->type === DeviceType::ROUTER) {
            $this->configureRouter($device, $dto, $ipAddress);
        } elseif ($device->type === DeviceType::OLT) {
            $this->configureOlt($device, $dto);
        }
    }

    /**
     * Configure service on router.
     */
    protected function configureRouter(Device $device, ProvisionServiceDTO $dto, IpAddress $ipAddress): void
    {
        if (!$dto->pppoeUser || !$dto->pppoePassword) {
            $this->log("PPPoE credentials missing, skipping router config", [], 'warning');
            return;
        }

        $this->routerOsService->createPppoeUser($device, [
            'name' => $dto->pppoeUser,
            'password' => $dto->pppoePassword,
            'profile' => $dto->routerProfile ?? 'default',
            'remote_address' => $ipAddress->address,
            'comment' => "Subscription: {$dto->subscriptionId}",
        ]);
    }

    /**
     * Configure service on OLT.
     */
    protected function configureOlt(Device $device, ProvisionServiceDTO $dto): void
    {
        if (!$dto->onuSerial) {
            $this->log("ONU serial missing, skipping OLT config", [], 'warning');
            return;
        }

        $this->oltService->authorizeOnu($device, [
            'serial' => $dto->onuSerial,
            'profile' => $dto->oltProfile ?? 'default',
            'vlan' => $dto->vlanId,
            'subscription_id' => $dto->subscriptionId,
        ]);
    }
}
