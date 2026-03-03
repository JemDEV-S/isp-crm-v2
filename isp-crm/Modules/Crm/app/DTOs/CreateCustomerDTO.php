<?php

declare(strict_types=1);

namespace Modules\Crm\DTOs;

use Modules\Crm\Enums\CustomerType;
use Modules\Crm\Enums\DocumentType;

final readonly class CreateCustomerDTO
{
    public function __construct(
        public string $name,
        public CustomerType $customerType,
        public DocumentType $documentType,
        public string $documentNumber,
        public string $phone,
        public ?string $email = null,
        public ?string $tradeName = null,
        public ?string $billingEmail = null,
        public ?int $leadId = null,
        public decimal $creditLimit = 0,
        public bool $taxExempt = false,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            customerType: CustomerType::from($data['customer_type']),
            documentType: DocumentType::from($data['document_type']),
            documentNumber: $data['document_number'],
            phone: $data['phone'],
            email: $data['email'] ?? null,
            tradeName: $data['trade_name'] ?? null,
            billingEmail: $data['billing_email'] ?? null,
            leadId: $data['lead_id'] ?? null,
            creditLimit: (float) ($data['credit_limit'] ?? 0),
            taxExempt: (bool) ($data['tax_exempt'] ?? false),
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'customer_type' => $this->customerType->value,
            'document_type' => $this->documentType->value,
            'document_number' => $this->documentNumber,
            'phone' => $this->phone,
            'email' => $this->email,
            'trade_name' => $this->tradeName,
            'billing_email' => $this->billingEmail,
            'lead_id' => $this->leadId,
            'credit_limit' => $this->creditLimit,
            'tax_exempt' => $this->taxExempt,
        ];
    }
}
