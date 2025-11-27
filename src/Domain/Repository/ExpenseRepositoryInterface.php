<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use DateTimeImmutable;

interface ExpenseRepositoryInterface
{
    public function save(int $weekId, string $category, float $amount, DateTimeImmutable $date, ?string $description = null): void;
}
