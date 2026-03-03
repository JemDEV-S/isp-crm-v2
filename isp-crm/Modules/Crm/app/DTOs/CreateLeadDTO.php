<?php

declare(strict_types=1);

namespace Modules\Crm\DTOs;

use Modules\Crm\Enums\DocumentType;
use Modules\Crm\Enums\LeadSource;

final readonly class CreateLeadDTO
{
    public function __construct(
        public string $name,
        public string $phone,
        public ?string $email = null,
        public ?DocumentType $documentType = null,
        public ?string $documentNumber = null,
        public LeadSource $source = LeadSource::WALK_IN,
        public ?string $notes = null,
        public ?int $zoneId = null,
        public ?int $assignedTo = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            phone: $data['phone'],
            email: $data['email'] ?? null,
            documentType: isset($data['document_type']) ? DocumentType::from($data['document_type']) : null,
            documentNumber: $data['document_number'] ?? null,
            source: isset($data['source']) ? LeadSource::from($data['source']) : LeadSource::WALK_IN,
            notes: $data['notes'] ?? null,
            zoneId: $data['zone_id'] ?? null,
            assignedTo: $data['assigned_to'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'document_type' => $this->documentType?->value,
            'document_number' => $this->documentNumber,
            'source' => $this->source->value,
            'notes' => $this->notes,
            'zone_id' => $this->zoneId,
            'assigned_to' => $this->assignedTo,
        ];
    }
}
