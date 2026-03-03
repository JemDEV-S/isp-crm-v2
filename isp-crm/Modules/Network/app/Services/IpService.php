<?php

declare(strict_types=1);

namespace Modules\Network\Services;

use Modules\Core\Exceptions\BusinessLogicException;
use Modules\Core\Services\BaseService;
use Modules\Network\Entities\IpAddress;
use Modules\Network\Entities\IpPool;

class IpService extends BaseService
{
    /**
     * Assign a free IP from a pool to a subscription.
     */
    public function assignFreeIp(int $poolId, int $subscriptionId): IpAddress
    {
        return $this->transaction(function () use ($poolId, $subscriptionId) {
            $pool = IpPool::findOrFail($poolId);

            if (!$pool->is_active) {
                throw new BusinessLogicException('El pool de IPs no está activo');
            }

            $ip = $pool->freeAddresses()->lockForUpdate()->first();

            if (!$ip) {
                throw new BusinessLogicException('No hay direcciones IP disponibles en este pool');
            }

            $ip->assignTo($subscriptionId);

            $this->log("IP {$ip->address} asignada a subscripción {$subscriptionId}", [
                'ip_id' => $ip->id,
                'pool_id' => $poolId,
                'subscription_id' => $subscriptionId,
            ]);

            return $ip->fresh();
        });
    }

    /**
     * Release an IP address.
     */
    public function releaseIp(int $ipId): IpAddress
    {
        return $this->transaction(function () use ($ipId) {
            $ip = IpAddress::findOrFail($ipId);

            if ($ip->isAvailable()) {
                throw new BusinessLogicException('La IP ya está libre');
            }

            $subscriptionId = $ip->subscription_id;
            $ip->release();

            $this->log("IP {$ip->address} liberada", [
                'ip_id' => $ip->id,
                'previous_subscription_id' => $subscriptionId,
            ]);

            return $ip->fresh();
        });
    }

    /**
     * Reserve an IP address.
     */
    public function reserveIp(int $ipId, string $notes = null): IpAddress
    {
        $ip = IpAddress::findOrFail($ipId);

        if (!$ip->isAvailable()) {
            throw new BusinessLogicException('La IP no está disponible para reservar');
        }

        $ip->reserve($notes);

        $this->log("IP {$ip->address} reservada", [
            'ip_id' => $ip->id,
            'notes' => $notes,
        ]);

        return $ip->fresh();
    }

    /**
     * Blacklist an IP address.
     */
    public function blacklistIp(int $ipId, string $reason): IpAddress
    {
        $ip = IpAddress::findOrFail($ipId);
        $ip->blacklist($reason);

        $this->log("IP {$ip->address} agregada a lista negra", [
            'ip_id' => $ip->id,
            'reason' => $reason,
        ]);

        return $ip->fresh();
    }

    /**
     * Get available IPs count in a pool.
     */
    public function getAvailableCount(int $poolId): int
    {
        $pool = IpPool::findOrFail($poolId);
        return $pool->remainingCapacity();
    }

    /**
     * Get pool usage statistics.
     */
    public function getPoolStats(int $poolId): array
    {
        $pool = IpPool::with(['ipAddresses'])->findOrFail($poolId);

        return [
            'total' => $pool->totalCapacity(),
            'free' => $pool->remainingCapacity(),
            'assigned' => $pool->assignedAddresses()->count(),
            'reserved' => $pool->ipAddresses()->where('status', 'reserved')->count(),
            'blacklisted' => $pool->ipAddresses()->where('status', 'blacklisted')->count(),
            'usage_percentage' => $pool->usagePercentage(),
        ];
    }
}
