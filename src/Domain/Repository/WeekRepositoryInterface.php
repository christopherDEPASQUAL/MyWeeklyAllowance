<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Week;

interface WeekRepositoryInterface
{
    public function findByIdForChild(int $weekId, int $childId): ?Week;

    /**
     * Persist a week. If $weekId is null, the implementation must generate one (e.g. AUTO_INCREMENT) and return it.
     */
    public function save(Week $week, ?int $weekId = null): int;

    public function incrementExpenses(int $weekId, float $amount): void;
}
