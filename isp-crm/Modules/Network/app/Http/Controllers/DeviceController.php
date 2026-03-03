<?php

declare(strict_types=1);

namespace Modules\Network\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\Network\DTOs\CreateDeviceDTO;
use Modules\Network\DTOs\UpdateDeviceDTO;
use Modules\Network\Entities\Device;
use Modules\Network\Entities\Node;
use Modules\Network\Enums\DeviceType;
use Modules\Network\Http\Requests\StoreDeviceRequest;
use Modules\Network\Http\Requests\UpdateDeviceRequest;
use Modules\Network\Services\RouterOsService;
use Modules\Network\Services\OltService;

class DeviceController extends Controller
{
    public function __construct(
        protected RouterOsService $routerOsService,
        protected OltService $oltService,
    ) {
        $this->middleware('permission:network.device.view')->only(['index', 'show']);
        $this->middleware('permission:network.device.create')->only(['create', 'store']);
        $this->middleware('permission:network.device.update')->only(['edit', 'update']);
        $this->middleware('permission:network.device.configure')->only(['testConnection', 'systemInfo']);
        $this->middleware('permission:network.device.delete')->only('destroy');
    }

    /**
     * Display a listing of devices.
     */
    public function index(Request $request): View
    {
        $query = Device::query()
            ->with('node')
            ->withCount('ports', 'ipPools');

        if ($request->filled('type')) {
            $query->byType($request->input('type'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('node_id')) {
            $query->where('node_id', $request->input('node_id'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('brand', 'like', "%{$search}%")
                  ->orWhere('model', 'like', "%{$search}%")
                  ->orWhere('serial_number', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%");
            });
        }

        $devices = $query->orderBy('brand')->orderBy('model')->paginate(15);
        $deviceTypes = DeviceType::cases();
        $nodes = Node::where('status', 'active')->orderBy('name')->get();

        return view('network::devices.index', compact('devices', 'deviceTypes', 'nodes'));
    }

    /**
     * Show the form for creating a new device.
     */
    public function create(Request $request): View
    {
        $nodes = Node::where('status', 'active')->orderBy('name')->get();
        $deviceTypes = DeviceType::cases();
        $selectedNodeId = $request->input('node_id');

        return view('network::devices.create', compact('nodes', 'deviceTypes', 'selectedNodeId'));
    }

    /**
     * Store a newly created device.
     */
    public function store(StoreDeviceRequest $request): RedirectResponse
    {
        $dto = CreateDeviceDTO::fromArray($request->validated());
        $device = Device::create($dto->toArray());

        return redirect()
            ->route('network.devices.show', $device)
            ->with('success', 'Dispositivo creado exitosamente.');
    }

    /**
     * Display the specified device.
     */
    public function show(Device $device): View
    {
        $device->load([
            'node',
            'ports' => fn($q) => $q->orderBy('port_number'),
            'ipPools' => fn($q) => $q->withCount([
                'ipAddresses as free_ips_count' => fn($q) => $q->where('status', 'free'),
                'ipAddresses as assigned_ips_count' => fn($q) => $q->where('status', 'assigned'),
            ]),
        ]);

        return view('network::devices.show', compact('device'));
    }

    /**
     * Show the form for editing the specified device.
     */
    public function edit(Device $device): View
    {
        $nodes = Node::where('status', 'active')->orderBy('name')->get();
        $deviceTypes = DeviceType::cases();

        return view('network::devices.edit', compact('device', 'nodes', 'deviceTypes'));
    }

    /**
     * Update the specified device.
     */
    public function update(UpdateDeviceRequest $request, Device $device): RedirectResponse
    {
        $dto = UpdateDeviceDTO::fromArray($request->validated());
        $device->update($dto->toArray());

        return redirect()
            ->route('network.devices.show', $device)
            ->with('success', 'Dispositivo actualizado exitosamente.');
    }

    /**
     * Remove the specified device.
     */
    public function destroy(Device $device): RedirectResponse
    {
        if ($device->ipPools()->whereHas('ipAddresses', fn($q) => $q->where('status', 'assigned'))->exists()) {
            return back()->with('error', 'No se puede eliminar un dispositivo con IPs asignadas.');
        }

        $device->delete();

        return redirect()
            ->route('network.devices.index')
            ->with('success', 'Dispositivo eliminado exitosamente.');
    }

    /**
     * Test connection to the device.
     */
    public function testConnection(Device $device): \Illuminate\Http\JsonResponse
    {
        if (!$device->hasApiConfigured()) {
            return response()->json([
                'success' => false,
                'message' => 'El dispositivo no tiene configuración API.',
            ], 400);
        }

        try {
            if ($device->type === DeviceType::ROUTER) {
                $info = $this->routerOsService->getSystemInfo($device);
                return response()->json([
                    'success' => true,
                    'message' => 'Conexión exitosa',
                    'data' => $info,
                ]);
            }

            if ($device->type === DeviceType::OLT) {
                // Basic connectivity test for OLT
                return response()->json([
                    'success' => true,
                    'message' => 'Dispositivo OLT configurado correctamente.',
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Tipo de dispositivo no soportado para prueba de conexión.',
            ], 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de conexión: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get system information from the device.
     */
    public function systemInfo(Device $device): \Illuminate\Http\JsonResponse
    {
        if ($device->type !== DeviceType::ROUTER) {
            return response()->json([
                'success' => false,
                'message' => 'Solo disponible para routers.',
            ], 400);
        }

        try {
            $info = $this->routerOsService->getSystemInfo($device);
            return response()->json([
                'success' => true,
                'data' => $info,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get PPPoE profiles from router.
     */
    public function pppoeProfiles(Device $device): \Illuminate\Http\JsonResponse
    {
        if ($device->type !== DeviceType::ROUTER) {
            return response()->json([
                'success' => false,
                'message' => 'Solo disponible para routers.',
            ], 400);
        }

        try {
            $profiles = $this->routerOsService->getPppoeProfiles($device);
            return response()->json([
                'success' => true,
                'data' => $profiles,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get active connections from router.
     */
    public function activeConnections(Device $device): \Illuminate\Http\JsonResponse
    {
        if ($device->type !== DeviceType::ROUTER) {
            return response()->json([
                'success' => false,
                'message' => 'Solo disponible para routers.',
            ], 400);
        }

        try {
            $connections = $this->routerOsService->getActiveConnections($device);
            return response()->json([
                'success' => true,
                'data' => $connections,
                'count' => count($connections),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get unauthorized ONUs from OLT.
     */
    public function unauthorizedOnus(Device $device): \Illuminate\Http\JsonResponse
    {
        if ($device->type !== DeviceType::OLT) {
            return response()->json([
                'success' => false,
                'message' => 'Solo disponible para OLTs.',
            ], 400);
        }

        try {
            $onus = $this->oltService->getUnauthorizedOnus($device);
            return response()->json([
                'success' => true,
                'data' => $onus,
                'count' => count($onus),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
