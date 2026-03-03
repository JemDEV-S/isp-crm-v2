<?php

declare(strict_types=1);

namespace Modules\Crm\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Crm\DTOs\ConvertLeadDTO;
use Modules\Crm\DTOs\CreateLeadDTO;
use Modules\Crm\Entities\Lead;
use Modules\Crm\Enums\LeadSource;
use Modules\Crm\Enums\LeadStatus;
use Modules\Crm\Services\LeadService;

class LeadController extends Controller
{
    public function __construct(
        protected LeadService $leadService
    ) {}

    public function index(Request $request)
    {
        $leads = $this->leadService->paginate(
            $request->only(['status', 'source', 'zone_id', 'assigned_to', 'search', 'date_from', 'date_to']),
            $request->integer('per_page', 15)
        );

        $stats = $this->leadService->getStats();

        return view('crm::leads.index', compact('leads', 'stats'));
    }

    public function create()
    {
        return view('crm::leads.create');
    }

    public function edit(Lead $lead)
    {
        return view('crm::leads.edit', compact('lead'));
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
        $lead->load(['zone', 'assignedUser', 'createdByUser', 'customer']);
        return view('crm::leads.show', compact('lead'));
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

    public function enums(): JsonResponse
    {
        return response()->json([
            'data' => [
                'sources' => LeadSource::toArray(),
                'statuses' => LeadStatus::toArray(),
            ],
        ]);
    }
}
