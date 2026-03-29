<?php

declare(strict_types=1);

namespace Modules\Network\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\Network\DTOs\CreateNodeDTO;
use Modules\Network\Entities\FiberRoute;
use Modules\Network\Entities\NapBox;
use Modules\Network\Entities\NapPort;
use Modules\Network\Entities\Node;
use Modules\Network\Http\Requests\StoreNodeRequest;
use Modules\Network\Http\Requests\UpdateNodeRequest;

class NodeController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:network.node.view')->only(['index', 'show', 'topology']);
        $this->middleware('permission:network.node.create')->only(['create', 'store']);
        $this->middleware('permission:network.node.update')->only(['edit', 'update']);
        $this->middleware('permission:network.node.delete')->only('destroy');
    }

    /**
     * Display a listing of nodes.
     */
    public function index(Request $request): View
    {
        $query = Node::query()
            ->withCount(['devices', 'napBoxes']);

        if ($request->filled('type')) {
            $query->byType($request->input('type'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%");
            });
        }

        $nodes = $query->orderBy('name')->paginate(15);

        return view('network::nodes.index', compact('nodes'));
    }

    public function topology(): View
    {
        $stats = [
            'nodes' => Node::count(),
            'active_nodes' => Node::where('status', 'active')->count(),
            'nap_boxes' => NapBox::count(),
            'free_ports' => NapPort::where('status', 'free')->count(),
            'fiber_routes' => FiberRoute::count(),
        ];

        return view('network::topology', compact('stats'));
    }

    /**
     * Show the form for creating a new node.
     */
    public function create(): View
    {
        return view('network::nodes.create');
    }

    /**
     * Store a newly created node.
     */
    public function store(StoreNodeRequest $request): RedirectResponse
    {
        $dto = CreateNodeDTO::fromArray($request->validated());
        $node = Node::create($dto->toArray());

        return redirect()
            ->route('network.nodes.show', $node)
            ->with('success', 'Nodo creado exitosamente.');
    }

    /**
     * Display the specified node.
     */
    public function show(Node $node): View
    {
        $node->load([
            'devices' => fn($q) => $q->withCount('ports'),
            'napBoxes' => fn($q) => $q->withCount(['ports as free_ports_count' => fn($q) => $q->where('status', 'free')]),
            'fiberRoutesFrom.toNode',
            'fiberRoutesTo.fromNode',
        ]);

        return view('network::nodes.show', compact('node'));
    }

    /**
     * Show the form for editing the specified node.
     */
    public function edit(Node $node): View
    {
        return view('network::nodes.edit', compact('node'));
    }

    /**
     * Update the specified node.
     */
    public function update(UpdateNodeRequest $request, Node $node): RedirectResponse
    {
        $node->update($request->validated());

        return redirect()
            ->route('network.nodes.show', $node)
            ->with('success', 'Nodo actualizado exitosamente.');
    }

    /**
     * Remove the specified node.
     */
    public function destroy(Node $node): RedirectResponse
    {
        if ($node->devices()->exists()) {
            return back()->with('error', 'No se puede eliminar un nodo que tiene dispositivos asociados.');
        }

        if ($node->napBoxes()->exists()) {
            return back()->with('error', 'No se puede eliminar un nodo que tiene cajas NAP asociadas.');
        }

        $node->delete();

        return redirect()
            ->route('network.nodes.index')
            ->with('success', 'Nodo eliminado exitosamente.');
    }

    /**
     * Get nodes as JSON for maps/selects.
     */
    public function json(Request $request): JsonResponse
    {
        $nodes = Node::query()
            ->select(['id', 'code', 'name', 'type', 'latitude', 'longitude', 'status', 'address'])
            ->withCount(['devices', 'napBoxes'])
            ->when($request->filled('type'), fn($q) => $q->byType($request->input('type')))
            ->when($request->filled('active'), fn($q) => $q->where('status', 'active'))
            ->get()
            ->map(fn (Node $node) => [
                'id' => $node->id,
                'code' => $node->code,
                'name' => $node->name,
                'type' => $node->type?->value,
                'type_label' => $node->type?->label(),
                'latitude' => $node->latitude !== null ? (float) $node->latitude : null,
                'longitude' => $node->longitude !== null ? (float) $node->longitude : null,
                'status' => $node->status?->value,
                'status_label' => $node->status?->label(),
                'address' => $node->address,
                'devices_count' => $node->devices_count,
                'nap_boxes_count' => $node->nap_boxes_count,
            ]);

        return response()->json($nodes);
    }
}
