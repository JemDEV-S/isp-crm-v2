<?php

declare(strict_types=1);

namespace Modules\FieldOps\app\DTOs;

final readonly class SubmitChecklistDTO
{
    public function __construct(
        public int $checklistTemplateId,
        public array $responses,
        public int $completedBy = 0,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            checklistTemplateId: $data['checklist_template_id'],
            responses: $data['responses'],
            completedBy: $data['completed_by'] ?? auth()->id(),
        );
    }
}
