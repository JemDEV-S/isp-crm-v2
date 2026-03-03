<?php

declare(strict_types=1);

namespace Modules\AccessControl\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\AccessControl\DTOs\CreateZoneDTO;
use Modules\AccessControl\DTOs\UpdateZoneDTO;
use Modules\AccessControl\Entities\Zone;
use Modules\AccessControl\Http\Requests\StoreZoneRequest;
use Modules\AccessControl\Http\Requests\UpdateZoneRequest;
use Modules\AccessControl\Services\ZoneService;

class ZoneController extends Controller
{
    public function __construct(
        private readonly ZoneService $zoneService,
    ) {
        $this->middleware('permission:accesscontrol.zone.view')->only(['index', 'show']);
        $this->middleware('permission:accesscontrol.zone.create')->only(['create', 'store']);
        $this->middleware('permission:accesscontrol.zone.update')->only(['edit', 'update']);
        $this->middleware('permission:accesscontrol.zone.delete')->only('destroy');
    }

    public function index(Request $request): View
    {
        $filters = [
            'search' => $request->get('search'),
            'is_active' => $request->has('is_active') ? (bool) $request->get('is_active') : null,
            'parent_id' => $request->get('parent_id'),
        ];

        $zones = $this->zoneService->paginate(15, array_filter($filters));
        $parentZones = $this->zoneService->all();

        return view('accesscontrol::zones.index', compact('zones', 'parentZones', 'filters'));
    }

    public function create(): View
    {
        $parentZones = $this->zoneService->all();

        return view('accesscontrol::zones.create', compact('parentZones'));
    }

    public function store(StoreZoneRequest $request): RedirectResponse
    {
        $dto = CreateZoneDTO::fromRequest($request);
        $this->zoneService->create($dto);

        return redirect()
            ->route('accesscontrol.zones.index')
            ->with('success', 'Zona creada exitosamente.');
    }

    public function show(Zone $zone): View
    {
        $zone->load(['parent', 'children', 'users']);
        $zone->loadCount(['children', 'users']);

        return view('accesscontrol::zones.show', compact('zone'));
    }

    public function edit(Zone $zone): View
    {
        $zone->load('parent');
        $parentZones = $this->zoneService->all()
            ->reject(function ($parentZone) use ($zone) {
                // Excluir la zona actual y sus descendientes
                return $parentZone->id === $zone->id || in_array($parentZone->id, $zone->descendants());
            });

        return view('accesscontrol::zones.edit', compact('zone', 'parentZones'));
    }

    public function update(UpdateZoneRequest $request, Zone $zone): RedirectResponse
    {
        try {
            $dto = UpdateZoneDTO::fromRequest($request);
            $this->zoneService->update($zone, $dto);

            return redirect()
                ->route('accesscontrol.zones.index')
                ->with('success', 'Zona actualizada exitosamente.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', $e->getMessage());
        }
    }

    public function destroy(Zone $zone): RedirectResponse
    {
        try {
            $this->zoneService->delete($zone);

            return redirect()
                ->route('accesscontrol.zones.index')
                ->with('success', 'Zona eliminada exitosamente.');
        } catch (\Exception $e) {
            return redirect()
                ->route('accesscontrol.zones.index')
                ->with('error', $e->getMessage());
        }
    }

    public function toggleStatus(Zone $zone): RedirectResponse
    {
        $this->zoneService->toggleStatus($zone);
        $status = $zone->is_active ? 'activada' : 'desactivada';

        return redirect()
            ->back()
            ->with('success', "Zona {$status} exitosamente.");
    }
}
