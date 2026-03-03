<?php

declare(strict_types=1);

namespace Modules\Network\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\Network\DTOs\CreateIpPoolDTO;
use Modules\Network\Entities\Device;
use Modules\Network\Entities\IpAddress;
use Modules\Network\Entities\IpPool;
use Modules\Network\Enums\IpStatus;
use Modules\Network\Http\Requests\AssignIpRequest;
use Modules\Network\Http\Requests\StoreIpPoolRequest;
use Modules\Network\Services\IpService;

class IpPoolController extends Controller
{
    public function __construct(
        protected IpService $ipService,
    ) {
        $this->middleware('permission:network.ippool.view')->only(['index', 'show', 'addresses']);
        $this->middleware('permission:network.ippool.create')->only(['create', 'store']);
        $this->middleware('permission:network.ippool.update')->only(['edit', 'update']);
        $this->middleware('permission:network.ip.assign')->only(['assignIp', 'releaseIp']);
        $this->middleware('permission:network.ippool.delete')->only('destroy');
    }

    /**
     * Display a listing of IP pools.
     */
    public function index(Request $request): View
    {
        $query = IpPool::query()
            ->with('device')
            ->withCount([
                'ipAddresses',
                'ipAddresses as free_count' => fn($q) => $q->where('status', IpStatus::FREE),
                'ipAddresses as assigned_count' => fn($q) => $q->where('status', IpStatus::ASSIGNED),
            ]);

        if ($request->filled('type')) {
            $query->byType($request->input('type'));
        }

        if ($request->filled('device_id')) {
            $query->where('device_id', $request->input('device_id'));
        }

        if ($request->boolean('active_only')) {
            $query->active();
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('network_cidr', 'like', "%{$search}%");
            });
        }

        $pools = $query->orderBy('name')->paginate(15);
        $devices = Device::whereIn('type', ['router', 'olt'])->orderBy('brand')->get();

        return view('network::ip-pools.index', compact('pools', 'devices'));
    }

    /**
     * Show the form for creating a new IP pool.
     */
    public function create(Request $request): View
    {
        $devices = Device::whereIn('type', ['router', 'olt'])
            ->with('node')
            ->orderBy('brand')
            ->get();

        $selectedDeviceId = $request->input('device_id');

        return view('network::ip-pools.create', compact('devices', 'selectedDeviceId'));
    }

    /**
     * Store a newly created IP pool.
     */
    public function store(StoreIpPoolRequest $request): RedirectResponse
    {
        $dto = CreateIpPoolDTO::fromArray($request->validated());
        $pool = IpPool::create($dto->toArray());

        // Populate IP addresses if requested
        if ($dto->populateAddresses) {
            $this->populatePoolAddresses($pool);
        }

        return redirect()
            ->route('network.ip-pools.show', $pool)
            ->with('success', 'Pool de IP creado exitosamente.');
    }

    /**
     * Display the specified IP pool.
     */
    public function show(IpPool $ipPool): View
    {
        $ipPool->load('device.node');

        $stats = $this->ipService->getPoolStats($ipPool->id);

        return view('network::ip-pools.show', compact('ipPool', 'stats'));
    }

    /**
     * Show addresses in the pool.
     */
    public function addresses(Request $request, IpPool $ipPool): View
    {
        $query = $ipPool->ipAddresses();

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('search')) {
            $query->where('address', 'like', '%' . $request->input('search') . '%');
        }

        $addresses = $query->orderByRaw("INET_ATON(address)")->paginate(50);

        return view('network::ip-pools.addresses', compact('ipPool', 'addresses'));
    }

    /**
     * Show the form for editing the specified IP pool.
     */
    public function edit(IpPool $ipPool): View
    {
        $devices = Device::whereIn('type', ['router', 'olt'])
            ->with('node')
            ->orderBy('brand')
            ->get();

        return view('network::ip-pools.edit', compact('ipPool', 'devices'));
    }

    /**
     * Update the specified IP pool.
     */
    public function update(Request $request, IpPool $ipPool): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:100'],
            'dns_primary' => ['nullable', 'ip'],
            'dns_secondary' => ['nullable', 'ip'],
            'vlan_id' => ['nullable', 'integer', 'min:1', 'max:4094'],
            'device_id' => ['nullable', 'integer', 'exists:devices,id'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $ipPool->update($validated);

        return redirect()
            ->route('network.ip-pools.show', $ipPool)
            ->with('success', 'Pool actualizado exitosamente.');
    }

    /**
     * Remove the specified IP pool.
     */
    public function destroy(IpPool $ipPool): RedirectResponse
    {
        if ($ipPool->assignedAddresses()->exists()) {
            return back()->with('error', 'No se puede eliminar un pool con IPs asignadas.');
        }

        // Delete all IPs in the pool
        $ipPool->ipAddresses()->delete();
        $ipPool->delete();

        return redirect()
            ->route('network.ip-pools.index')
            ->with('success', 'Pool eliminado exitosamente.');
    }

    /**
     * Assign an IP from this pool.
     */
    public function assignIp(AssignIpRequest $request): \Illuminate\Http\JsonResponse
    {
        try {
            $ip = $this->ipService->assignFreeIp(
                $request->input('pool_id'),
                $request->input('subscription_id')
            );

            return response()->json([
                'success' => true,
                'message' => "IP {$ip->address} asignada exitosamente.",
                'data' => [
                    'id' => $ip->id,
                    'address' => $ip->address,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Release an IP address.
     */
    public function releaseIp(IpAddress $ipAddress): \Illuminate\Http\JsonResponse
    {
        try {
            $this->ipService->releaseIp($ipAddress->id);

            return response()->json([
                'success' => true,
                'message' => "IP {$ipAddress->address} liberada exitosamente.",
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Reserve an IP address.
     */
    public function reserveIp(Request $request, IpAddress $ipAddress): \Illuminate\Http\JsonResponse
    {
        try {
            $this->ipService->reserveIp(
                $ipAddress->id,
                $request->input('notes')
            );

            return response()->json([
                'success' => true,
                'message' => "IP {$ipAddress->address} reservada exitosamente.",
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Blacklist an IP address.
     */
    public function blacklistIp(Request $request, IpAddress $ipAddress): \Illuminate\Http\JsonResponse
    {
        $request->validate(['reason' => 'required|string|max:255']);

        try {
            $this->ipService->blacklistIp(
                $ipAddress->id,
                $request->input('reason')
            );

            return response()->json([
                'success' => true,
                'message' => "IP {$ipAddress->address} agregada a lista negra.",
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Populate pool with IP addresses from CIDR.
     */
    protected function populatePoolAddresses(IpPool $pool): void
    {
        [$network, $bits] = explode('/', $pool->network_cidr);
        $bits = (int) $bits;

        $networkLong = ip2long($network);
        $maskLong = -1 << (32 - $bits);
        $networkLong = $networkLong & $maskLong;

        $totalHosts = pow(2, 32 - $bits);
        $gateway = ip2long($pool->gateway);

        $ips = [];
        for ($i = 1; $i < $totalHosts - 1; $i++) {
            $ipLong = $networkLong + $i;

            // Skip gateway and network addresses
            if ($ipLong === $gateway || $ipLong === $networkLong) {
                continue;
            }

            $ips[] = [
                'pool_id' => $pool->id,
                'address' => long2ip($ipLong),
                'status' => IpStatus::FREE->value,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            // Insert in batches of 100
            if (count($ips) >= 100) {
                IpAddress::insert($ips);
                $ips = [];
            }
        }

        // Insert remaining
        if (!empty($ips)) {
            IpAddress::insert($ips);
        }
    }

    /**
     * Get pool statistics as JSON.
     */
    public function stats(IpPool $ipPool): \Illuminate\Http\JsonResponse
    {
        $stats = $this->ipService->getPoolStats($ipPool->id);
        return response()->json($stats);
    }
}
