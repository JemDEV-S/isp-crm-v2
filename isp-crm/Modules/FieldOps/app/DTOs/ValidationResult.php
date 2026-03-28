<?php

declare(strict_types=1);

namespace Modules\FieldOps\app\DTOs;

final readonly class ValidationResult
{
    public function __construct(
        public bool $passed,
        public string $status,
        public array $criteriaChecked = [],
        public array $issues = [],
    ) {}

    public function toArray(): array
    {
        return [
            'passed' => $this->passed,
            'status' => $this->status,
            'criteria_checked' => $this->criteriaChecked,
            'issues' => $this->issues,
        ];
    }
}
