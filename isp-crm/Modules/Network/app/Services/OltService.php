<?php

declare(strict_types=1);

namespace Modules\Network\Services;

use Modules\Core\Exceptions\BusinessLogicException;
use Modules\Core\Services\BaseService;
use Modules\Network\Entities\Device;
use Modules\Network\Enums\DeviceType;

class OltService extends BaseService
{
    private ?Device $currentDevice = null;

    /**
     * Supported OLT vendors.
     */
    private const VENDOR_HUAWEI = 'huawei';
    private const VENDOR_ZTE = 'zte';
    private const VENDOR_FIBERHOME = 'fiberhome';

    /**
     * Authorize an ONU on the OLT.
     */
    public function authorizeOnu(Device $device, array $onuData): array
    {
        $this->validateOltDevice($device);

        $required = ['serial', 'profile'];
        foreach ($required as $field) {
            if (empty($onuData[$field])) {
                throw new BusinessLogicException("Campo requerido: {$field}");
            }
        }

        $vendor = $this->detectVendor($device);

        try {
            $result = match ($vendor) {
                self::VENDOR_HUAWEI => $this->authorizeOnuHuawei($device, $onuData),
                self::VENDOR_ZTE => $this->authorizeOnuZte($device, $onuData),
                self::VENDOR_FIBERHOME => $this->authorizeOnuFiberhome($device, $onuData),
                default => throw new BusinessLogicException("Vendor no soportado: {$vendor}"),
            };

            $this->log("ONU autorizada: {$onuData['serial']}", [
                'device_id' => $device->id,
                'serial' => $onuData['serial'],
                'profile' => $onuData['profile'],
                'vendor' => $vendor,
            ]);

            return $result;
        } catch (\Exception $e) {
            throw new BusinessLogicException("Error al autorizar ONU: {$e->getMessage()}");
        }
    }

    /**
     * Deauthorize an ONU from the OLT.
     */
    public function deauthorizeOnu(Device $device, string $serial): bool
    {
        $this->validateOltDevice($device);

        $vendor = $this->detectVendor($device);

        try {
            $result = match ($vendor) {
                self::VENDOR_HUAWEI => $this->deauthorizeOnuHuawei($device, $serial),
                self::VENDOR_ZTE => $this->deauthorizeOnuZte($device, $serial),
                self::VENDOR_FIBERHOME => $this->deauthorizeOnuFiberhome($device, $serial),
                default => throw new BusinessLogicException("Vendor no soportado: {$vendor}"),
            };

            $this->log("ONU desautorizada: {$serial}", [
                'device_id' => $device->id,
                'serial' => $serial,
                'vendor' => $vendor,
            ]);

            return $result;
        } catch (\Exception $e) {
            throw new BusinessLogicException("Error al desautorizar ONU: {$e->getMessage()}");
        }
    }

    /**
     * Get ONU status.
     */
    public function getOnuStatus(Device $device, string $serial): array
    {
        $this->validateOltDevice($device);

        $vendor = $this->detectVendor($device);

        return match ($vendor) {
            self::VENDOR_HUAWEI => $this->getOnuStatusHuawei($device, $serial),
            self::VENDOR_ZTE => $this->getOnuStatusZte($device, $serial),
            self::VENDOR_FIBERHOME => $this->getOnuStatusFiberhome($device, $serial),
            default => throw new BusinessLogicException("Vendor no soportado: {$vendor}"),
        };
    }

    /**
     * Get optical power for an ONU.
     */
    public function getOpticalPower(Device $device, string $serial): array
    {
        $this->validateOltDevice($device);

        $vendor = $this->detectVendor($device);

        return match ($vendor) {
            self::VENDOR_HUAWEI => $this->getOpticalPowerHuawei($device, $serial),
            self::VENDOR_ZTE => $this->getOpticalPowerZte($device, $serial),
            self::VENDOR_FIBERHOME => $this->getOpticalPowerFiberhome($device, $serial),
            default => throw new BusinessLogicException("Vendor no soportado: {$vendor}"),
        };
    }

    /**
     * Get list of unauthorized ONUs.
     */
    public function getUnauthorizedOnus(Device $device): array
    {
        $this->validateOltDevice($device);

        $vendor = $this->detectVendor($device);

        return match ($vendor) {
            self::VENDOR_HUAWEI => $this->getUnauthorizedOnusHuawei($device),
            self::VENDOR_ZTE => $this->getUnauthorizedOnusZte($device),
            self::VENDOR_FIBERHOME => $this->getUnauthorizedOnusFiberhome($device),
            default => throw new BusinessLogicException("Vendor no soportado: {$vendor}"),
        };
    }

    /**
     * Reboot an ONU remotely.
     */
    public function rebootOnu(Device $device, string $serial): bool
    {
        $this->validateOltDevice($device);

        $vendor = $this->detectVendor($device);

        try {
            $result = match ($vendor) {
                self::VENDOR_HUAWEI => $this->rebootOnuHuawei($device, $serial),
                self::VENDOR_ZTE => $this->rebootOnuZte($device, $serial),
                self::VENDOR_FIBERHOME => $this->rebootOnuFiberhome($device, $serial),
                default => throw new BusinessLogicException("Vendor no soportado: {$vendor}"),
            };

            $this->log("ONU reiniciada: {$serial}", [
                'device_id' => $device->id,
                'serial' => $serial,
            ]);

            return $result;
        } catch (\Exception $e) {
            throw new BusinessLogicException("Error al reiniciar ONU: {$e->getMessage()}");
        }
    }

    /**
     * Update ONU service profile.
     */
    public function updateOnuProfile(Device $device, string $serial, string $newProfile, ?int $vlanId = null): bool
    {
        $this->validateOltDevice($device);

        $vendor = $this->detectVendor($device);

        try {
            $result = match ($vendor) {
                self::VENDOR_HUAWEI => $this->updateOnuProfileHuawei($device, $serial, $newProfile, $vlanId),
                self::VENDOR_ZTE => $this->updateOnuProfileZte($device, $serial, $newProfile, $vlanId),
                self::VENDOR_FIBERHOME => $this->updateOnuProfileFiberhome($device, $serial, $newProfile, $vlanId),
                default => throw new BusinessLogicException("Vendor no soportado: {$vendor}"),
            };

            $this->log("Perfil ONU actualizado: {$serial} -> {$newProfile}", [
                'device_id' => $device->id,
                'serial' => $serial,
                'new_profile' => $newProfile,
                'vlan_id' => $vlanId,
            ]);

            return $result;
        } catch (\Exception $e) {
            throw new BusinessLogicException("Error al actualizar perfil: {$e->getMessage()}");
        }
    }

    // =========== Vendor-specific implementations ===========

    // --- Huawei ---

    protected function authorizeOnuHuawei(Device $device, array $onuData): array
    {
        // Huawei OLT uses SNMP or Telnet/SSH
        // This is a placeholder implementation

        // Example command sequence:
        // 1. interface gpon 0/0
        // 2. ont add {port} sn-auth {serial} ont-lineprofile-id {profile_id} ont-srvprofile-id {srv_profile_id}
        // 3. ont port native-vlan {port} ont {ont_id} eth 1 vlan {vlan_id}

        return [
            'success' => true,
            'serial' => $onuData['serial'],
            'profile' => $onuData['profile'],
            'vendor' => 'huawei',
        ];
    }

    protected function deauthorizeOnuHuawei(Device $device, string $serial): bool
    {
        // Delete ONU from Huawei OLT
        // ont delete {port} {ont_id}
        return true;
    }

    protected function getOnuStatusHuawei(Device $device, string $serial): array
    {
        // display ont info by-sn {serial}
        return [
            'serial' => $serial,
            'status' => 'online',
            'uptime' => '5 days',
            'vendor' => 'huawei',
        ];
    }

    protected function getOpticalPowerHuawei(Device $device, string $serial): array
    {
        // display ont optical-info {port} {ont_id}
        return [
            'serial' => $serial,
            'rx_power' => -18.5, // dBm
            'tx_power' => 2.1,   // dBm
            'vendor' => 'huawei',
        ];
    }

    protected function getUnauthorizedOnusHuawei(Device $device): array
    {
        // display ont autofind all
        return [];
    }

    protected function rebootOnuHuawei(Device $device, string $serial): bool
    {
        // ont reset {port} {ont_id}
        return true;
    }

    protected function updateOnuProfileHuawei(Device $device, string $serial, string $newProfile, ?int $vlanId): bool
    {
        return true;
    }

    // --- ZTE ---

    protected function authorizeOnuZte(Device $device, array $onuData): array
    {
        // ZTE OLT commands
        // onu add sn {serial} type {type} service-profile {profile}

        return [
            'success' => true,
            'serial' => $onuData['serial'],
            'profile' => $onuData['profile'],
            'vendor' => 'zte',
        ];
    }

    protected function deauthorizeOnuZte(Device $device, string $serial): bool
    {
        return true;
    }

    protected function getOnuStatusZte(Device $device, string $serial): array
    {
        return [
            'serial' => $serial,
            'status' => 'online',
            'uptime' => '3 days',
            'vendor' => 'zte',
        ];
    }

    protected function getOpticalPowerZte(Device $device, string $serial): array
    {
        return [
            'serial' => $serial,
            'rx_power' => -19.2,
            'tx_power' => 1.8,
            'vendor' => 'zte',
        ];
    }

    protected function getUnauthorizedOnusZte(Device $device): array
    {
        return [];
    }

    protected function rebootOnuZte(Device $device, string $serial): bool
    {
        return true;
    }

    protected function updateOnuProfileZte(Device $device, string $serial, string $newProfile, ?int $vlanId): bool
    {
        return true;
    }

    // --- FiberHome ---

    protected function authorizeOnuFiberhome(Device $device, array $onuData): array
    {
        return [
            'success' => true,
            'serial' => $onuData['serial'],
            'profile' => $onuData['profile'],
            'vendor' => 'fiberhome',
        ];
    }

    protected function deauthorizeOnuFiberhome(Device $device, string $serial): bool
    {
        return true;
    }

    protected function getOnuStatusFiberhome(Device $device, string $serial): array
    {
        return [
            'serial' => $serial,
            'status' => 'online',
            'uptime' => '2 days',
            'vendor' => 'fiberhome',
        ];
    }

    protected function getOpticalPowerFiberhome(Device $device, string $serial): array
    {
        return [
            'serial' => $serial,
            'rx_power' => -17.8,
            'tx_power' => 2.3,
            'vendor' => 'fiberhome',
        ];
    }

    protected function getUnauthorizedOnusFiberhome(Device $device): array
    {
        return [];
    }

    protected function rebootOnuFiberhome(Device $device, string $serial): bool
    {
        return true;
    }

    protected function updateOnuProfileFiberhome(Device $device, string $serial, string $newProfile, ?int $vlanId): bool
    {
        return true;
    }

    // =========== Helper Methods ===========

    /**
     * Validate that the device is an OLT.
     */
    protected function validateOltDevice(Device $device): void
    {
        if ($device->type !== DeviceType::OLT) {
            throw new BusinessLogicException('El dispositivo no es una OLT');
        }

        if (!$device->hasApiConfigured() && empty($device->snmp_community)) {
            throw new BusinessLogicException('La OLT no tiene configuración de acceso');
        }
    }

    /**
     * Detect the vendor from the device brand.
     */
    protected function detectVendor(Device $device): string
    {
        $brand = strtolower($device->brand);

        return match (true) {
            str_contains($brand, 'huawei') => self::VENDOR_HUAWEI,
            str_contains($brand, 'zte') => self::VENDOR_ZTE,
            str_contains($brand, 'fiberhome') => self::VENDOR_FIBERHOME,
            default => $brand,
        };
    }

    /**
     * Execute SNMP command on OLT.
     */
    protected function snmpGet(Device $device, string $oid): mixed
    {
        $community = $device->snmp_community ?? 'public';

        // Use PHP SNMP extension
        // return snmpget($device->ip_address, $community, $oid);

        return null;
    }

    /**
     * Execute SNMP walk on OLT.
     */
    protected function snmpWalk(Device $device, string $oid): array
    {
        $community = $device->snmp_community ?? 'public';

        // Use PHP SNMP extension
        // return snmpwalk($device->ip_address, $community, $oid);

        return [];
    }
}
