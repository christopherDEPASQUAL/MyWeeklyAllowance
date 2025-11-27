<?php

declare(strict_types=1);

namespace App\Application\Dto;

use DateTimeImmutable;

final class WeekSummaryDto
{
    public function __construct(
        public readonly int $weekId,
        public readonly int $childId,
        public readonly float $budget,
        public readonly float $totalExpenses,
        public readonly float $balance,
        public readonly DateTimeImmutable $startDate,
        public readonly DateTimeImmutable $endDate
    ) {
    }
}
