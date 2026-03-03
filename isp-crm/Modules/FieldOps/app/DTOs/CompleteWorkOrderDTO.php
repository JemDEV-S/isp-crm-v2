<?php

declare(strict_types=1);

namespace Modules\FieldOps\app\DTOs;

final readonly class CompleteWorkOrderDTO
{
    public function __construct(
        public array $checklistResponses,
        public array $materials = [],
        public array $photos = [],
        public ?string $notes = null,
        public int $completedBy = 0,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            checklistResponses: $data['checklist_responses'] ?? [],
            materials: $data['materials'] ?? [],
            photos: $data['photos'] ?? [],
            notes: $data['notes'] ?? null,
            completedBy: $data['completed_by'] ?? auth()->id(),
        );
    }
}
