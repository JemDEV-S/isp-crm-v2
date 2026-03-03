<?php

declare(strict_types=1);

namespace Modules\Network\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\Network\DTOs\CreateFiberRouteDTO;
use Modules\Network\Entities\FiberRoute;
use Modules\Network\Entities\Node;

class FiberRouteController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:network.fiberroute.view')->only(['index', 'show']);
        $this->middleware('permission:network.fiberroute.create')->only(['create', 'store']);
        $this->middleware('permission:network.fiberroute.update')->only(['edit', 'update']);
        $this->middleware('permission:network.fiberroute.delete')->only('destroy');
    }

    /**
     * Display a listing of fiber routes.
     */
    public function index(Request $request): View
    {
        $query = FiberRoute::query()
            ->with(['fromNode', 'toNode']);

        if ($request->filled('from_node_id')) {
            $query->where('from_node_id', $request->input('from_node_id'));
        }

        if ($request->filled('to_node_id')) {
            $query->where('to_node_id', $request->input('to_node_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->whereHas('fromNode', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            })->orWhereHas('toNode', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $routes = $query->orderBy('created_at', 'desc')->paginate(15);
        $nodes = Node::where('status', 'active')->orderBy('name')->get();

        $filters = $request->only(['from_node_id', 'to_node_id', 'status', 'search']);

        return view('network::fiber-routes.index', compact('routes', 'nodes', 'filters'));
    }

    /**
     * Show the form for creating a new fiber route.
     */
    public function create(Request $request): View
    {
        $nodes = Node::where('status', 'active')->orderBy('name')->get();
        $selectedFromNodeId = $request->input('from_node_id');
        $selectedToNodeId = $request->input('to_node_id');

        return view('network::fiber-routes.create', compact('nodes', 'selectedFromNodeId', 'selectedToNodeId'));
    }

    /**
     * Store a newly created fiber route.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'from_node_id' => 'required|exists:nodes,id',
            'to_node_id' => 'required|exists:nodes,id|different:from_node_id',
            'distance_meters' => 'nullable|integer|min:1|max:999999',
            'fiber_count' => 'nullable|integer|min:1|max:288',
            'status' => 'required|in:active,inactive,maintenance',
            'notes' => 'nullable|string|max:1000',
        ]);

        $dto = CreateFiberRouteDTO::fromArray($validated);
        $route = FiberRoute::create($dto->toArray());

        if ($request->filled('notes')) {
            $route->update(['notes' => $request->input('notes')]);
        }

        return redirect()
            ->route('network.fiber-routes.show', $route)
            ->with('success', 'Ruta de fibra creada exitosamente.');
    }

    /**
     * Display the specified fiber route.
     */
    public function show(FiberRoute $fiberRoute): View
    {
        $fiberRoute->load(['fromNode', 'toNode']);

        return view('network::fiber-routes.show', compact('fiberRoute'));
    }

    /**
     * Show the form for editing the specified fiber route.
     */
    public function edit(FiberRoute $fiberRoute): View
    {
        $nodes = Node::where('status', 'active')->orderBy('name')->get();

        return view('network::fiber-routes.edit', compact('fiberRoute', 'nodes'));
    }

    /**
     * Update the specified fiber route.
     */
    public function update(Request $request, FiberRoute $fiberRoute): RedirectResponse
    {
        $validated = $request->validate([
            'from_node_id' => 'required|exists:nodes,id',
            'to_node_id' => 'required|exists:nodes,id|different:from_node_id',
            'distance_meters' => 'nullable|integer|min:1|max:999999',
            'fiber_count' => 'nullable|integer|min:1|max:288',
            'status' => 'required|in:active,inactive,maintenance',
            'notes' => 'nullable|string|max:1000',
        ]);

        $fiberRoute->update($validated);

        return redirect()
            ->route('network.fiber-routes.show', $fiberRoute)
            ->with('success', 'Ruta de fibra actualizada exitosamente.');
    }

    /**
     * Remove the specified fiber route.
     */
    public function destroy(FiberRoute $fiberRoute): RedirectResponse
    {
        $fiberRoute->delete();

        return redirect()
            ->route('network.fiber-routes.index')
            ->with('success', 'Ruta de fibra eliminada exitosamente.');
    }

    /**
     * Get fiber routes as GeoJSON for map.
     */
    public function geoJson(Request $request): \Illuminate\Http\JsonResponse
    {
        $query = FiberRoute::with(['fromNode', 'toNode'])
            ->whereNotNull('route_geojson');

        if ($request->boolean('active_only')) {
            $query->where('status', 'active');
        }

        $routes = $query->get();

        $features = $routes->map(function ($route) {
            return [
                'type' => 'Feature',
                'geometry' => $route->route_geojson ?? [
                    'type' => 'LineString',
                    'coordinates' => [
                        [$route->fromNode->longitude, $route->fromNode->latitude],
                        [$route->toNode->longitude, $route->toNode->latitude],
                    ],
                ],
                'properties' => [
                    'id' => $route->id,
                    'from_node' => $route->fromNode->name,
                    'to_node' => $route->toNode->name,
                    'distance_km' => $route->distance_km,
                    'fiber_count' => $route->fiber_count,
                    'status' => $route->status,
                ],
            ];
        });

        return response()->json([
            'type' => 'FeatureCollection',
            'features' => $features,
        ]);
    }
}
