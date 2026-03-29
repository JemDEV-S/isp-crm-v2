<?php

declare(strict_types=1);

namespace Modules\Crm\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\AccessControl\Entities\Zone;
use Modules\Crm\DTOs\CreateCustomerDTO;
use Modules\Crm\Entities\Customer;
use Modules\Crm\Enums\CustomerType;
use Modules\Crm\Enums\DocumentType;
use Modules\Crm\Enums\ContactType;
use Modules\Crm\Services\CustomerService;

class CustomerController extends Controller
{
    public function __construct(
        protected CustomerService $customerService
    ) {}

    public function index(Request $request)
    {
        $customers = $this->customerService->paginate(
            $request->only(['is_active', 'customer_type', 'search', 'zone_id']),
            $request->integer('per_page', 15)
        );

        $stats = $this->customerService->getStats();

        return view('crm::customers.index', [
            'customers' => $customers,
            'stats' => $stats,
            'zones' => Zone::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function create()
    {
        return view('crm::customers.create', $this->getCustomerFormOptions());
    }

    public function edit(Customer $customer)
    {
        return view('crm::customers.edit', array_merge(compact('customer'), $this->getCustomerFormOptions()));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'customer_type' => 'required|string|in:personal,business',
            'document_type' => 'required|string|in:dni,ruc,ce,passport',
            'document_number' => 'required|string|max:20|unique:customers,document_number',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:100',
            'trade_name' => 'nullable|string|max:150',
            'billing_email' => 'nullable|email|max:100',
            'credit_limit' => 'nullable|numeric|min:0',
            'tax_exempt' => 'nullable|boolean',
            'service_address' => 'nullable|array',
            'service_address.label' => 'nullable|string|max:50',
            'service_address.street' => 'required_with:service_address|string|max:200',
            'service_address.number' => 'nullable|string|max:20',
            'service_address.floor' => 'nullable|string|max:10',
            'service_address.apartment' => 'nullable|string|max:20',
            'service_address.reference' => 'nullable|string',
            'service_address.address_reference' => 'nullable|string',
            'service_address.photo_url' => 'nullable|url|max:255',
            'service_address.district' => 'required_with:service_address|string|max:100',
            'service_address.city' => 'required_with:service_address|string|max:100',
            'service_address.province' => 'required_with:service_address|string|max:100',
            'service_address.postal_code' => 'nullable|string|max:10',
            'service_address.latitude' => 'nullable|numeric',
            'service_address.longitude' => 'nullable|numeric',
            'service_address.zone_id' => 'nullable|exists:zones,id',
            'primary_contact' => 'nullable|array',
            'primary_contact.name' => 'required_with:primary_contact|string|max:100',
            'primary_contact.relationship' => 'nullable|string|max:50',
            'primary_contact.type' => 'required_with:primary_contact|string|in:phone,email,whatsapp',
            'primary_contact.value' => 'required_with:primary_contact|string|max:100',
            'primary_contact.receives_notifications' => 'nullable|boolean',
        ]);

        $customer = $this->customerService->create(CreateCustomerDTO::fromArray($validated));

        if (!empty($validated['service_address']['street'] ?? null)) {
            $this->customerService->addAddress($customer, [
                ...$validated['service_address'],
                'type' => 'service',
                'is_default' => true,
            ]);
        }

        if (!empty($validated['primary_contact']['value'] ?? null)) {
            $customer->contacts()->create([
                ...$validated['primary_contact'],
                'is_primary' => true,
                'receives_notifications' => (bool) ($validated['primary_contact']['receives_notifications'] ?? false),
            ]);
        }

        return redirect()->route('crm.customers.show', $customer)
            ->with('success', 'Cliente creado exitosamente');
    }

    public function show(Customer $customer)
    {
        $customer->load([
            'addresses.zone',
            'contacts',
            'documents',
            'notes.user',
            'lead',
        ]);

        return view('crm::customers.show', compact('customer'));
    }

    public function update(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:150',
            'customer_type' => 'sometimes|string|in:personal,business',
            'document_type' => 'sometimes|string|in:dni,ruc,ce,passport',
            'document_number' => 'sometimes|string|max:20|unique:customers,document_number,' . $customer->id,
            'phone' => 'sometimes|string|max:20',
            'email' => 'nullable|email|max:100',
            'trade_name' => 'nullable|string|max:150',
            'billing_email' => 'nullable|email|max:100',
            'credit_limit' => 'nullable|numeric|min:0',
            'tax_exempt' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ]);

        $customer = $this->customerService->update($customer, $validated);

        return redirect()->route('crm.customers.show', $customer)
            ->with('success', 'Cliente actualizado exitosamente');
    }

    public function destroy(Customer $customer)
    {
        $customer->delete();

        return redirect()->route('crm.customers.index')
            ->with('success', 'Cliente eliminado exitosamente');
    }

    public function activate(Customer $customer)
    {
        $customer = $this->customerService->activate($customer);

        return redirect()->route('crm.customers.show', $customer)
            ->with('success', 'Cliente activado exitosamente');
    }

    public function deactivate(Customer $customer)
    {
        $customer = $this->customerService->deactivate($customer);

        return redirect()->route('crm.customers.show', $customer)
            ->with('success', 'Cliente desactivado exitosamente');
    }

    public function addAddress(Request $request, Customer $customer): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'required|string|in:service,billing',
            'label' => 'nullable|string|max:50',
            'street' => 'required|string|max:200',
            'number' => 'nullable|string|max:20',
            'floor' => 'nullable|string|max:10',
            'apartment' => 'nullable|string|max:20',
            'reference' => 'nullable|string',
            'address_reference' => 'nullable|string',
            'photo_url' => 'nullable|url|max:255',
            'district' => 'required|string|max:100',
            'city' => 'required|string|max:100',
            'province' => 'required|string|max:100',
            'postal_code' => 'nullable|string|max:10',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'zone_id' => 'nullable|exists:zones,id',
            'is_default' => 'nullable|boolean',
        ]);

        $address = $this->customerService->addAddress($customer, $validated);

        return response()->json([
            'message' => 'Dirección agregada exitosamente',
            'data' => $address->load('zone'),
        ], 201);
    }

    public function addNote(Request $request, Customer $customer): JsonResponse
    {
        $validated = $request->validate([
            'content' => 'required|string',
            'is_pinned' => 'nullable|boolean',
        ]);

        $note = $customer->notes()->create($validated);

        return response()->json([
            'message' => 'Nota agregada exitosamente',
            'data' => $note->load('user'),
        ], 201);
    }

    public function addContact(Request $request, Customer $customer): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'relationship' => 'nullable|string|max:50',
            'type' => 'required|string|in:phone,email,whatsapp',
            'value' => 'required|string|max:100',
            'is_primary' => 'nullable|boolean',
            'receives_notifications' => 'nullable|boolean',
        ]);

        $contact = $customer->contacts()->create($validated);

        return response()->json([
            'message' => 'Contacto agregado exitosamente',
            'data' => $contact,
        ], 201);
    }

    public function stats(): JsonResponse
    {
        return response()->json([
            'data' => $this->customerService->getStats(),
        ]);
    }

    public function enums(): JsonResponse
    {
        return response()->json([
            'data' => [
                'customer_types' => CustomerType::toArray(),
                'document_types' => DocumentType::toArray(),
            ],
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:2',
        ]);

        $customers = Customer::query()
            ->where(function ($query) use ($request) {
                $search = $request->input('q');
                $query->where('name', 'like', "%{$search}%")
                      ->orWhere('code', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%")
                      ->orWhere('document_number', 'like', "%{$search}%");
            })
            ->active()
            ->limit(20)
            ->get(['id', 'code', 'name', 'phone', 'document_type', 'document_number']);

        return response()->json([
            'data' => $customers,
        ]);
    }

    protected function getCustomerFormOptions(): array
    {
        return [
            'customerTypes' => CustomerType::toArray(),
            'documentTypes' => DocumentType::toArray(),
            'contactTypes' => ContactType::toArray(),
            'zones' => Zone::query()->orderBy('name')->get(['id', 'name']),
        ];
    }
}
