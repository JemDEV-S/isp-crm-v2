<?php

declare(strict_types=1);

namespace Modules\Crm\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\AccessControl\Entities\User;
use Modules\AccessControl\Entities\Zone;
use Modules\Crm\DTOs\ConvertLeadDTO;
use Modules\Crm\DTOs\CreateLeadDTO;
use Modules\Crm\Entities\Lead;
use Modules\Crm\Enums\LeadSource;
use Modules\Crm\Enums\LeadStatus;
use Modules\Crm\Services\CustomerOnboardingOrchestrator;
use Modules\Crm\Services\FeasibilityService;
use Modules\Crm\Services\LeadService;

class LeadController extends Controller
{
    public function __construct(
        protected LeadService $leadService,
        protected FeasibilityService $feasibilityService,
        protected CustomerOnboardingOrchestrator $onboardingOrchestrator,
    ) {}

    public function index(Request $request)
    {
        $leads = $this->leadService->paginate(
            $request->only(['status', 'source', 'zone_id', 'assigned_to', 'search', 'date_from', 'date_to']),
            $request->integer('per_page', 15)
        );

        $stats = $this->leadService->getStats();

        return view('crm::leads.index', array_merge(compact('leads', 'stats'), $this->getLeadFormOptions()));
    }

    public function create()
    {
        return view('crm::leads.create', $this->getLeadFormOptions());
    }

    public function edit(Lead $lead)
    {
        return view('crm::leads.edit', array_merge(compact('lead'), $this->getLeadFormOptions()));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:100',
            'document_type' => 'nullable|string|in:dni,ruc,ce,passport',
            'document_number' => 'nullable|string|max:20',
            'source' => 'nullable|string|in:' . implode(',', array_column(LeadSource::cases(), 'value')),
            'notes' => 'nullable|string',
            'zone_id' => 'nullable|exists:zones,id',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        $lead = $this->leadService->create(CreateLeadDTO::fromArray($validated));

        return redirect()->route('crm.leads.show', $lead)
            ->with('success', 'Lead creado exitosamente');
    }

    public function show(Lead $lead)
    {
        $lead->load(['zone', 'assignedUser', 'createdByUser', 'customer', 'duplicateOf']);
        return view('crm::leads.show', compact('lead'));
    }

    public function onboarding(Lead $lead)
    {
        $lead->load([
            'zone',
            'assignedUser',
            'createdByUser',
            'customer',
            'duplicateOf',
            'feasibilityRequests' => fn ($query) => $query->latest('requested_at'),
            'capacityReservations' => fn ($query) => $query->latest('expires_at'),
        ]);

        $onboarding = $this->onboardingOrchestrator->startOnboarding($lead->id);
        $latestFeasibilityRequest = $lead->feasibilityRequests->first();
        $activeReservation = $lead->capacityReservations->first(fn ($reservation) => $reservation->isActive());

        return view('crm::leads.onboarding', array_merge(
            compact('lead', 'onboarding', 'latestFeasibilityRequest', 'activeReservation'),
            $this->getLeadFormOptions(),
        ));
    }

    public function update(Request $request, Lead $lead)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:150',
            'phone' => 'sometimes|string|max:20',
            'email' => 'nullable|email|max:100',
            'document_type' => 'nullable|string|in:dni,ruc,ce,passport',
            'document_number' => 'nullable|string|max:20',
            'source' => 'nullable|string|in:' . implode(',', array_column(LeadSource::cases(), 'value')),
            'status' => 'nullable|string|in:' . implode(',', array_column(LeadStatus::cases(), 'value')),
            'notes' => 'nullable|string',
            'zone_id' => 'nullable|exists:zones,id',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        $lead = $this->leadService->update($lead, $validated);

        return redirect()->route('crm.leads.show', $lead)
            ->with('success', 'Lead actualizado exitosamente');
    }

    public function destroy(Lead $lead)
    {
        if ($lead->isConverted()) {
            return redirect()->back()
                ->with('error', 'No se puede eliminar un lead convertido');
        }

        $lead->delete();

        return redirect()->route('crm.leads.index')
            ->with('success', 'Lead eliminado exitosamente');
    }

    public function convert(Request $request, Lead $lead)
    {
        $validated = $request->validate([
            'customer_type' => 'required|string|in:personal,business',
            'document_type' => 'required|string|in:dni,ruc,ce,passport',
            'document_number' => 'required|string|max:20',
            'trade_name' => 'nullable|string|max:150',
            'billing_email' => 'nullable|email|max:100',
            'address' => 'nullable|array',
            'address.type' => 'required_with:address|string|in:service,billing',
            'address.street' => 'required_with:address|string|max:200',
            'address.district' => 'required_with:address|string|max:100',
            'address.city' => 'required_with:address|string|max:100',
            'address.province' => 'required_with:address|string|max:100',
            'address.number' => 'nullable|string|max:20',
            'address.reference' => 'nullable|string',
            'address.address_reference' => 'nullable|string',
            'address.photo_url' => 'nullable|url|max:255',
            'address.latitude' => 'nullable|numeric',
            'address.longitude' => 'nullable|numeric',
            'address.zone_id' => 'nullable|exists:zones,id',
        ]);

        $validated['lead_id'] = $lead->id;

        try {
            $customer = $this->leadService->convert(ConvertLeadDTO::fromArray($validated));

            return redirect()->route('crm.customers.show', $customer)
                ->with('success', 'Lead convertido exitosamente a cliente');
        } catch (\DomainException $e) {
            return redirect()->back()
                ->with('error', $e->getMessage())
                ->withInput();
        }
    }

    public function changeStatus(Request $request, Lead $lead): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|string|in:' . implode(',', array_column(LeadStatus::cases(), 'value')),
        ]);

        try {
            $lead = $this->leadService->changeStatus($lead, LeadStatus::from($validated['status']));

            return response()->json([
                'message' => 'Estado actualizado exitosamente',
                'data' => $lead,
            ]);
        } catch (\DomainException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function assign(Request $request, Lead $lead): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $lead = $this->leadService->assign($lead, $validated['user_id']);

        return response()->json([
            'message' => 'Lead asignado exitosamente',
            'data' => $lead->load('assignedUser'),
        ]);
    }

    public function stats(): JsonResponse
    {
        return response()->json([
            'data' => $this->leadService->getStats(),
        ]);
    }

    public function checkDuplicates(Lead $lead): JsonResponse
    {
        return response()->json([
            'data' => $this->leadService->checkDuplicates($lead),
        ]);
    }

    public function checkFeasibility(Request $request, Lead $lead): JsonResponse
    {
        $validated = $request->validate([
            'address_id' => 'nullable|exists:addresses,id',
            'latitude' => 'required_without:address_id|numeric',
            'longitude' => 'required_without:address_id|numeric',
            'radius_meters' => 'nullable|integer|min:50|max:5000',
        ]);

        $result = $this->feasibilityService->check($lead->id, $validated);

        return response()->json([
            'message' => 'Factibilidad procesada exitosamente',
            'data' => $result,
        ]);
    }

    public function reserveCapacity(Request $request, Lead $lead): JsonResponse
    {
        $validated = $request->validate([
            'nap_port_id' => 'required|exists:nap_ports,id',
            'hours' => 'nullable|integer|min:1|max:168',
            'feasibility_request_id' => 'nullable|exists:feasibility_requests,id',
        ]);

        $reservation = $this->feasibilityService->reserveCapacity(
            napPortId: (int) $validated['nap_port_id'],
            leadId: $lead->id,
            hours: (int) ($validated['hours'] ?? 24),
            feasibilityRequestId: isset($validated['feasibility_request_id']) ? (int) $validated['feasibility_request_id'] : null,
        );

        return response()->json([
            'message' => 'Capacidad reservada exitosamente',
            'data' => $reservation,
        ], 201);
    }

    public function enums(): JsonResponse
    {
        return response()->json([
            'data' => [
                'sources' => LeadSource::toArray(),
                'statuses' => LeadStatus::toArray(),
            ],
        ]);
    }

    protected function getLeadFormOptions(): array
    {
        return [
            'zones' => Zone::query()->orderBy('name')->get(['id', 'name']),
            'users' => User::query()->orderBy('name')->get(['id', 'name']),
        ];
    }
}
