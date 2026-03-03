<?php

declare(strict_types=1);

namespace Modules\Network\Services;

use Modules\Core\Exceptions\BusinessLogicException;
use Modules\Core\Services\BaseService;
use Modules\Network\Entities\Device;
use Modules\Network\Enums\DeviceType;

class RouterOsService extends BaseService
{
    private ?object $api = null;
    private ?Device $currentDevice = null;

    /**
     * Connect to a RouterOS device.
     */
    public function connect(Device $device): self
    {
        if ($device->type !== DeviceType::ROUTER) {
            throw new BusinessLogicException('El dispositivo no es un router Mikrotik');
        }

        if (!$device->hasApiConfigured()) {
            throw new BusinessLogicException('El router no tiene configuración API');
        }

        $this->currentDevice = $device;

        // Attempt connection using RouterOS API
        // Using PHP RouterOS API client library pattern
        try {
            $this->api = $this->createApiConnection(
                $device->ip_address,
                $device->api_user,
                $device->getApiPassword(),
                $device->api_port ?? 8728
            );
        } catch (\Exception $e) {
            throw new BusinessLogicException("No se pudo conectar al router: {$e->getMessage()}");
        }

        return $this;
    }

    /**
     * Disconnect from the current device.
     */
    public function disconnect(): void
    {
        if ($this->api) {
            // Close connection
            $this->api = null;
        }
        $this->currentDevice = null;
    }

    /**
     * Create a PPPoE user on the router.
     */
    public function createPppoeUser(Device $device, array $userData): array
    {
        $this->connect($device);

        $required = ['name', 'password', 'profile'];
        foreach ($required as $field) {
            if (empty($userData[$field])) {
                throw new BusinessLogicException("Campo requerido: {$field}");
            }
        }

        try {
            $command = [
                '/ppp/secret/add',
                '=name=' . $userData['name'],
                '=password=' . $userData['password'],
                '=profile=' . $userData['profile'],
                '=service=pppoe',
            ];

            if (!empty($userData['remote_address'])) {
                $command[] = '=remote-address=' . $userData['remote_address'];
            }

            if (!empty($userData['local_address'])) {
                $command[] = '=local-address=' . $userData['local_address'];
            }

            if (!empty($userData['comment'])) {
                $command[] = '=comment=' . $userData['comment'];
            }

            $result = $this->executeCommand($command);

            $this->log("Usuario PPPoE creado: {$userData['name']}", [
                'device_id' => $device->id,
                'username' => $userData['name'],
                'profile' => $userData['profile'],
            ]);

            return [
                'success' => true,
                'username' => $userData['name'],
            ];
        } catch (\Exception $e) {
            throw new BusinessLogicException("Error al crear usuario PPPoE: {$e->getMessage()}");
        } finally {
            $this->disconnect();
        }
    }

    /**
     * Disable a PPPoE user.
     */
    public function disablePppoeUser(Device $device, string $username): bool
    {
        $this->connect($device);

        try {
            // Find the user first
            $user = $this->findPppoeUser($username);

            if (!$user) {
                throw new BusinessLogicException("Usuario PPPoE no encontrado: {$username}");
            }

            $command = [
                '/ppp/secret/set',
                '=.id=' . $user['.id'],
                '=disabled=yes',
            ];

            $this->executeCommand($command);

            // Also disconnect active session
            $this->disconnectPppoeSession($username);

            $this->log("Usuario PPPoE deshabilitado: {$username}", [
                'device_id' => $device->id,
                'username' => $username,
            ]);

            return true;
        } catch (\Exception $e) {
            throw new BusinessLogicException("Error al deshabilitar usuario: {$e->getMessage()}");
        } finally {
            $this->disconnect();
        }
    }

    /**
     * Enable a PPPoE user.
     */
    public function enablePppoeUser(Device $device, string $username): bool
    {
        $this->connect($device);

        try {
            $user = $this->findPppoeUser($username);

            if (!$user) {
                throw new BusinessLogicException("Usuario PPPoE no encontrado: {$username}");
            }

            $command = [
                '/ppp/secret/set',
                '=.id=' . $user['.id'],
                '=disabled=no',
            ];

            $this->executeCommand($command);

            $this->log("Usuario PPPoE habilitado: {$username}", [
                'device_id' => $device->id,
                'username' => $username,
            ]);

            return true;
        } catch (\Exception $e) {
            throw new BusinessLogicException("Error al habilitar usuario: {$e->getMessage()}");
        } finally {
            $this->disconnect();
        }
    }

    /**
     * Delete a PPPoE user.
     */
    public function deletePppoeUser(Device $device, string $username): bool
    {
        $this->connect($device);

        try {
            $user = $this->findPppoeUser($username);

            if (!$user) {
                return true; // Already deleted
            }

            // Disconnect active session first
            $this->disconnectPppoeSession($username);

            $command = [
                '/ppp/secret/remove',
                '=.id=' . $user['.id'],
            ];

            $this->executeCommand($command);

            $this->log("Usuario PPPoE eliminado: {$username}", [
                'device_id' => $device->id,
                'username' => $username,
            ]);

            return true;
        } catch (\Exception $e) {
            throw new BusinessLogicException("Error al eliminar usuario: {$e->getMessage()}");
        } finally {
            $this->disconnect();
        }
    }

    /**
     * Update PPPoE user profile (for plan changes).
     */
    public function updatePppoeProfile(Device $device, string $username, string $newProfile): bool
    {
        $this->connect($device);

        try {
            $user = $this->findPppoeUser($username);

            if (!$user) {
                throw new BusinessLogicException("Usuario PPPoE no encontrado: {$username}");
            }

            $command = [
                '/ppp/secret/set',
                '=.id=' . $user['.id'],
                '=profile=' . $newProfile,
            ];

            $this->executeCommand($command);

            // Disconnect to apply new profile immediately
            $this->disconnectPppoeSession($username);

            $this->log("Perfil PPPoE actualizado: {$username} -> {$newProfile}", [
                'device_id' => $device->id,
                'username' => $username,
                'new_profile' => $newProfile,
            ]);

            return true;
        } catch (\Exception $e) {
            throw new BusinessLogicException("Error al actualizar perfil: {$e->getMessage()}");
        } finally {
            $this->disconnect();
        }
    }

    /**
     * Add IP to address list (for morosos/blocked users).
     */
    public function addToAddressList(Device $device, string $listName, string $ipAddress, ?string $comment = null): bool
    {
        $this->connect($device);

        try {
            $command = [
                '/ip/firewall/address-list/add',
                '=list=' . $listName,
                '=address=' . $ipAddress,
            ];

            if ($comment) {
                $command[] = '=comment=' . $comment;
            }

            $this->executeCommand($command);

            $this->log("IP agregada a lista {$listName}: {$ipAddress}", [
                'device_id' => $device->id,
                'list' => $listName,
                'ip' => $ipAddress,
            ]);

            return true;
        } catch (\Exception $e) {
            // Check if already exists
            if (str_contains($e->getMessage(), 'already have')) {
                return true;
            }
            throw new BusinessLogicException("Error al agregar a lista: {$e->getMessage()}");
        } finally {
            $this->disconnect();
        }
    }

    /**
     * Remove IP from address list.
     */
    public function removeFromAddressList(Device $device, string $listName, string $ipAddress): bool
    {
        $this->connect($device);

        try {
            // Find the entry first
            $entry = $this->findAddressListEntry($listName, $ipAddress);

            if (!$entry) {
                return true; // Already removed
            }

            $command = [
                '/ip/firewall/address-list/remove',
                '=.id=' . $entry['.id'],
            ];

            $this->executeCommand($command);

            $this->log("IP removida de lista {$listName}: {$ipAddress}", [
                'device_id' => $device->id,
                'list' => $listName,
                'ip' => $ipAddress,
            ]);

            return true;
        } catch (\Exception $e) {
            throw new BusinessLogicException("Error al remover de lista: {$e->getMessage()}");
        } finally {
            $this->disconnect();
        }
    }

    /**
     * Get all PPPoE profiles.
     */
    public function getPppoeProfiles(Device $device): array
    {
        $this->connect($device);

        try {
            $command = ['/ppp/profile/print'];
            return $this->executeCommand($command);
        } finally {
            $this->disconnect();
        }
    }

    /**
     * Get active PPPoE connections.
     */
    public function getActiveConnections(Device $device): array
    {
        $this->connect($device);

        try {
            $command = ['/ppp/active/print'];
            return $this->executeCommand($command);
        } finally {
            $this->disconnect();
        }
    }

    /**
     * Check if a PPPoE user is currently connected.
     */
    public function isUserConnected(Device $device, string $username): bool
    {
        $this->connect($device);

        try {
            $command = [
                '/ppp/active/print',
                '?name=' . $username,
            ];

            $result = $this->executeCommand($command);
            return !empty($result);
        } finally {
            $this->disconnect();
        }
    }

    /**
     * Get router system information.
     */
    public function getSystemInfo(Device $device): array
    {
        $this->connect($device);

        try {
            $identity = $this->executeCommand(['/system/identity/print']);
            $resource = $this->executeCommand(['/system/resource/print']);
            $routerboard = $this->executeCommand(['/system/routerboard/print']);

            return [
                'identity' => $identity[0]['name'] ?? 'Unknown',
                'version' => $resource[0]['version'] ?? 'Unknown',
                'uptime' => $resource[0]['uptime'] ?? 'Unknown',
                'cpu_load' => $resource[0]['cpu-load'] ?? 0,
                'free_memory' => $resource[0]['free-memory'] ?? 0,
                'total_memory' => $resource[0]['total-memory'] ?? 0,
                'board_name' => $routerboard[0]['board-name'] ?? 'Unknown',
            ];
        } finally {
            $this->disconnect();
        }
    }

    // =========== Protected/Private Methods ===========

    /**
     * Create API connection to RouterOS.
     * This should be replaced with actual RouterOS API client implementation.
     */
    protected function createApiConnection(string $host, string $user, string $password, int $port): object
    {
        // This is a placeholder for the actual RouterOS API connection
        // In production, use a library like "pear2/Net_RouterOS" or "evilfreelancer/routeros-api-php"

        // Example using evilfreelancer/routeros-api-php:
        // $client = new \RouterOS\Client([
        //     'host' => $host,
        //     'user' => $user,
        //     'pass' => $password,
        //     'port' => $port,
        // ]);
        // return $client;

        // For now, return a mock object for development
        return new class {
            public function query($command, $params = []): array
            {
                // Mock implementation
                return [];
            }
        };
    }

    /**
     * Execute a RouterOS command.
     */
    protected function executeCommand(array $command): array
    {
        if (!$this->api) {
            throw new BusinessLogicException('No hay conexión activa al router');
        }

        // Implementation depends on the API library used
        // This is a placeholder
        return $this->api->query($command[0], array_slice($command, 1));
    }

    /**
     * Find a PPPoE user by username.
     */
    protected function findPppoeUser(string $username): ?array
    {
        $command = [
            '/ppp/secret/print',
            '?name=' . $username,
        ];

        $result = $this->executeCommand($command);
        return $result[0] ?? null;
    }

    /**
     * Find an address list entry.
     */
    protected function findAddressListEntry(string $listName, string $ipAddress): ?array
    {
        $command = [
            '/ip/firewall/address-list/print',
            '?list=' . $listName,
            '?address=' . $ipAddress,
        ];

        $result = $this->executeCommand($command);
        return $result[0] ?? null;
    }

    /**
     * Disconnect an active PPPoE session.
     */
    protected function disconnectPppoeSession(string $username): void
    {
        $command = [
            '/ppp/active/print',
            '?name=' . $username,
        ];

        $sessions = $this->executeCommand($command);

        foreach ($sessions as $session) {
            if (isset($session['.id'])) {
                $this->executeCommand([
                    '/ppp/active/remove',
                    '=.id=' . $session['.id'],
                ]);
            }
        }
    }
}
