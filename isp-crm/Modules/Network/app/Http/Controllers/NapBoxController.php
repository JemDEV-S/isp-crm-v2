<?php

declare(strict_types=1);

namespace Modules\Network\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\Network\DTOs\CreateNapBoxDTO;
use Modules\Network\Entities\NapBox;
use Modules\Network\Entities\NapPort;
use Modules\Network\Entities\Node;
use Modules\Network\Enums\NapPortStatus;
use Modules\Network\Http\Requests\CheckFeasibilityRequest;
use Modules\Network\Http\Requests\StoreNapBoxRequest;
use Modules\Network\Services\NapService;
use Modules\Network\Services\NetworkProvisioningService;

class NapBoxController extends Controller
{
    public function __construct(
        protected NapService $napService,
        protected NetworkProvisioningService $provisioningService,
    ) {
        $this->middleware('permission:network.napbox.view')->only(['index', 'show', 'ports']);
        $this->middleware('permission:network.napbox.create')->only(['create', 'store']);
        $this->middleware('permission:network.napbox.update')->only(['edit', 'update']);
        $this->middleware('permission:network.napbox.delete')->only('destroy');
    }

    /**
     * Display a listing of NAP boxes.
     */
    public function index(Request $request): View
    {
        $query = NapBox::query()
            ->with('node')
            ->withCount([
                'ports',
                'ports as free_ports_count' => fn($q) => $q->where('status', NapPortStatus::FREE),
                'ports as occupied_ports_count' => fn($q) => $q->where('status', NapPortStatus::OCCUPIED),
            ]);

        if ($request->filled('node_id')) {
            $query->where('node_id', $request->input('node_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->boolean('has_free_ports')) {
            $query->whereHas('ports', fn($q) => $q->where('status', NapPortStatus::FREE));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%");
            });
        }

        $napBoxes = $query->orderBy('code')->paginate(15);
        $nodes = Node::where('status', 'active')->orderBy('name')->get();

        return view('network::nap-boxes.index', compact('napBoxes', 'nodes'));
    }

    /**
     * Show the form for creating a new NAP box.
     */
    public function create(Request $request): View
    {
        $nodes = Node::where('status', 'active')->orderBy('name')->get();
        $selectedNodeId = $request->input('node_id');

        return view('network::nap-boxes.create', compact('nodes', 'selectedNodeId'));
    }

    /**
     * Store a newly created NAP box.
     */
    public function store(StoreNapBoxRequest $request): RedirectResponse
    {
        $dto = CreateNapBoxDTO::fromArray($request->validated());
        $napBox = NapBox::create($dto->toArray());

        // Create ports
        $this->createPorts($napBox);

        return redirect()
            ->route('network.nap-boxes.show', $napBox)
            ->with('success', 'Caja NAP creada exitosamente.');
    }

    /**
     * Display the specified NAP box.
     */
    public function show(NapBox $napBox): View
    {
        $napBox->load([
            'node',
            'ports' => fn($q) => $q->orderBy('port_number'),
        ]);

        $stats = $this->napService->getNapStats($napBox->id);

        return view('network::nap-boxes.show', compact('napBox', 'stats'));
    }

    /**
     * Show ports of the NAP box.
     */
    public function ports(NapBox $napBox): View
    {
        $napBox->load([
            'ports' => fn($q) => $q->orderBy('port_number'),
        ]);

        return view('network::nap-boxes.ports', compact('napBox'));
    }

    /**
     * Show the form for editing the specified NAP box.
     */
    public function edit(NapBox $napBox): View
    {
        $nodes = Node::where('status', 'active')->orderBy('name')->get();

        return view('network::nap-boxes.edit', compact('napBox', 'nodes'));
    }

    /**
     * Update the specified NAP box.
     */
    public function update(Request $request, NapBox $napBox): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'status' => ['nullable', 'string', 'in:active,inactive,maintenance'],
        ]);

        $napBox->update($validated);

        return redirect()
            ->route('network.nap-boxes.show', $napBox)
            ->with('success', 'Caja NAP actualizada exitosamente.');
    }

    /**
     * Remove the specified NAP box.
     */
    public function destroy(NapBox $napBox): RedirectResponse
    {
        if ($napBox->occupiedPorts()->exists()) {
            return back()->with('error', 'No se puede eliminar una NAP con puertos ocupados.');
        }

        $napBox->ports()->delete();
        $napBox->delete();

        return redirect()
            ->route('network.nap-boxes.index')
            ->with('success', 'Caja NAP eliminada exitosamente.');
    }

    /**
     * Assign a port to a subscription.
     */
    public function assignPort(Request $request, NapBox $napBox): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'subscription_id' => 'required|integer|exists:subscriptions,id',
            'label' => 'nullable|string|max:50',
        ]);

        try {
            $port = $this->napService->assignPort(
                $napBox->id,
                $request->input('subscription_id'),
                $request->input('label')
            );

            return response()->json([
                'success' => true,
                'message' => "Puerto {$port->port_number} asignado exitosamente.",
                'data' => [
                    'port_id' => $port->id,
                    'port_number' => $port->port_number,
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
     * Release a port.
     */
    public function releasePort(NapPort $napPort): \Illuminate\Http\JsonResponse
    {
        try {
            $this->napService->releasePort($napPort->id);

            return response()->json([
                'success' => true,
                'message' => "Puerto {$napPort->port_number} liberado exitosamente.",
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Update port status.
     */
    public function updatePortStatus(Request $request, NapPort $napPort): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'status' => 'required|string|in:free,reserved,damaged',
        ]);

        if ($napPort->isAssigned() && $request->input('status') !== 'damaged') {
            return response()->json([
                'success' => false,
                'message' => 'No se puede cambiar el estado de un puerto asignado.',
            ], 400);
        }

        $napPort->update([
            'status' => $request->input('status'),
            'subscription_id' => null,
            'label' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Estado del puerto actualizado.',
        ]);
    }

    /**
     * Check coverage/feasibility at coordinates.
     */
    public function checkFeasibility(CheckFeasibilityRequest $request): \Illuminate\Http\JsonResponse
    {
        $result = $this->provisioningService->checkFeasibility(
            $request->input('latitude'),
            $request->input('longitude'),
            $request->input('radius_meters', 500)
        );

        return response()->json($result->toArray());
    }

    /**
     * Find nearest NAP boxes with available ports.
     */
    public function findNearest(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'radius' => 'nullable|integer|min:100|max:2000',
            'limit' => 'nullable|integer|min:1|max:10',
        ]);

        $naps = $this->napService->findNearestAvailable(
            $request->input('latitude'),
            $request->input('longitude'),
            $request->input('radius', 500),
            $request->input('limit', 5)
        );

        return response()->json([
            'success' => true,
            'data' => $naps,
            'count' => count($naps),
        ]);
    }

    /**
     * Get NAP boxes as GeoJSON for map.
     */
    public function geoJson(Request $request): \Illuminate\Http\JsonResponse
    {
        $query = NapBox::with('node')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude');

        if ($request->boolean('active_only')) {
            $query->where('status', 'active');
        }

        if ($request->boolean('has_free_ports')) {
            $query->whereHas('ports', fn($q) => $q->where('status', NapPortStatus::FREE));
        }

        $napBoxes = $query->get();

        $features = $napBoxes->map(function ($nap) {
            return [
                'type' => 'Feature',
                'geometry' => [
                    'type' => 'Point',
                    'coordinates' => [$nap->longitude, $nap->latitude],
                ],
                'properties' => [
                    'id' => $nap->id,
                    'code' => $nap->code,
                    'name' => $nap->name,
                    'type' => $nap->type,
                    'status' => $nap->status,
                    'total_ports' => $nap->total_ports,
                    'free_ports' => $nap->freePorts()->count(),
                    'node_name' => $nap->node?->name,
                ],
            ];
        });

        return response()->json([
            'type' => 'FeatureCollection',
            'features' => $features,
        ]);
    }

    /**
     * Create ports for a new NAP box.
     */
    protected function createPorts(NapBox $napBox): void
    {
        $ports = [];
        for ($i = 1; $i <= $napBox->total_ports; $i++) {
            $ports[] = [
                'nap_box_id' => $napBox->id,
                'port_number' => $i,
                'status' => NapPortStatus::FREE->value,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        NapPort::insert($ports);
    }
}
