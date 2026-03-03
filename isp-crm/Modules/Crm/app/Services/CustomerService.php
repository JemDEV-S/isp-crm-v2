<?php

declare(strict_types=1);

namespace Modules\Crm\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Crm\DTOs\ConvertLeadDTO;
use Modules\Crm\DTOs\CreateAddressDTO;
use Modules\Crm\DTOs\CreateCustomerDTO;
use Modules\Crm\Entities\Address;
use Modules\Crm\Entities\Customer;
use Modules\Crm\Entities\Lead;
use Modules\Crm\Events\CustomerCreated;
use Modules\Crm\Events\CustomerUpdated;

class CustomerService
{
    public function create(CreateCustomerDTO $dto): Customer
    {
        return DB::transaction(function () use ($dto) {
            $customer = Customer::create($dto->toArray());

            event(new CustomerCreated($customer));

            return $customer;
        });
    }

    public function createFromLead(Lead $lead, ConvertLeadDTO $dto): Customer
    {
        return DB::transaction(function () use ($lead, $dto) {
            $customer = Customer::create([
                'lead_id' => $lead->id,
                'customer_type' => $dto->customerType,
                'document_type' => $dto->documentType,
                'document_number' => $dto->documentNumber,
                'name' => $lead->name,
                'trade_name' => $dto->tradeName,
                'phone' => $lead->phone,
                'email' => $lead->email,
                'billing_email' => $dto->billingEmail ?? $lead->email,
            ]);

            if ($dto->address) {
                $this->createAddress($customer->id, $dto->address);
            }

            event(new CustomerCreated($customer));

            return $customer;
        });
    }

    public function update(Customer $customer, array $data): Customer
    {
        $changes = [];

        foreach ($data as $key => $value) {
            if ($customer->{$key} !== $value) {
                $changes[$key] = [
                    'old' => $customer->{$key},
                    'new' => $value,
                ];
            }
        }

        $customer->update($data);

        if (!empty($changes)) {
            event(new CustomerUpdated($customer, $changes));
        }

        return $customer->fresh();
    }

    public function activate(Customer $customer): Customer
    {
        return $this->update($customer, ['is_active' => true]);
    }

    public function deactivate(Customer $customer): Customer
    {
        return $this->update($customer, ['is_active' => false]);
    }

    public function createAddress(int $customerId, CreateAddressDTO $dto): Address
    {
        $data = $dto->toArray();
        $data['customer_id'] = $customerId;

        return Address::create($data);
    }

    public function addAddress(Customer $customer, array $addressData): Address
    {
        $addressData['customer_id'] = $customer->id;
        return Address::create($addressData);
    }

    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Customer::query()
            ->with(['addresses', 'contacts']);

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (!empty($filters['customer_type'])) {
            $query->where('customer_type', $filters['customer_type']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('trade_name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('document_number', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['zone_id'])) {
            $query->whereHas('addresses', function ($q) use ($filters) {
                $q->where('zone_id', $filters['zone_id']);
            });
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function findByDocument(string $documentType, string $documentNumber): ?Customer
    {
        return Customer::byDocument($documentType, $documentNumber)->first();
    }

    public function findByCode(string $code): ?Customer
    {
        return Customer::where('code', $code)->first();
    }

    public function getStats(): array
    {
        return [
            'total' => Customer::count(),
            'active' => Customer::active()->count(),
            'inactive' => Customer::inactive()->count(),
            'personal' => Customer::personal()->count(),
            'business' => Customer::business()->count(),
        ];
    }
}
