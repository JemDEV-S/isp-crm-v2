<?php

declare(strict_types=1);

namespace Modules\Finance\DTOs;

class DunningRunResult
{
    public function __construct(
        public readonly string $jobRunId,
        public readonly int $totalProcessed = 0,
        public readonly int $totalExecuted = 0,
        public readonly int $totalSkipped = 0,
        public readonly int $totalFailed = 0,
        public readonly int $totalOutOfRange = 0,
    ) {}
}
