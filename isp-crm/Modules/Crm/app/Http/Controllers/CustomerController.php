<?php

declare(strict_types=1);

namespace Modules\Crm\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Crm\DTOs\CreateCustomerDTO;
use Modules\Crm\Entities\Customer;
use Modules\Crm\Enums\CustomerType;
use Modules\Crm\Enums\DocumentType;
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

        return view('crm::customers.index', compact('customers', 'stats'));
    }

    public function create()
    {
        return view('crm::customers.create');
    }

    public function edit(Customer $customer)
    {
        return view('crm::customers.edit', compact('customer'));
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
        ]);

        $customer = $this->customerService->create(CreateCustomerDTO::fromArray($validated));

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
}
