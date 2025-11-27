<?php

declare(strict_types=1);

namespace App\Application\Dto;

use DateTimeImmutable;

final class ExpenseSummaryDto
{
    public function __construct(
        public readonly int $weekId,
        public readonly string $category,
        public readonly float $amount,
        public readonly DateTimeImmutable $date,
        public readonly float $newBalance
    ) {
    }
}
