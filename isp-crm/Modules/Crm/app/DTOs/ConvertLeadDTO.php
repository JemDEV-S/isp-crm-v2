<?php

declare(strict_types=1);

namespace Modules\Crm\DTOs;

use Modules\Crm\Enums\CustomerType;
use Modules\Crm\Enums\DocumentType;

final readonly class ConvertLeadDTO
{
    public function __construct(
        public int $leadId,
        public CustomerType $customerType,
        public DocumentType $documentType,
        public string $documentNumber,
        public ?string $tradeName = null,
        public ?string $billingEmail = null,
        public ?CreateAddressDTO $address = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            leadId: $data['lead_id'],
            customerType: CustomerType::from($data['customer_type']),
            documentType: DocumentType::from($data['document_type']),
            documentNumber: $data['document_number'],
            tradeName: $data['trade_name'] ?? null,
            billingEmail: $data['billing_email'] ?? null,
            address: isset($data['address']) ? CreateAddressDTO::fromArray($data['address']) : null,
        );
    }
}
