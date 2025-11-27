<?php

declare(strict_types=1);

namespace App\Application;

use App\Application\Dto\WeekSummaryDto;
use App\Domain\Repository\WeekRepositoryInterface;
use App\Domain\Week;
use RuntimeException;

final class GetWeekSummary
{
    public function __construct(private WeekRepositoryInterface $weekRepository)
    {
    }

    public function execute(int $weekId, int $childId): WeekSummaryDto
    {
        $week = $this->weekRepository->findByIdForChild($weekId, $childId);

        if ($week === null) {
            throw new RuntimeException('Week not found');
        }

        return $this->toDto($weekId, $childId, $week);
    }

    private function toDto(int $weekId, int $childId, Week $week): WeekSummaryDto
    {
        return new WeekSummaryDto(
            weekId: $weekId,
            childId: $childId,
            budget: $week->budget(),
            totalExpenses: $week->totalExpenses(),
            balance: $week->balance(),
            startDate: $week->startDate(),
            endDate: $week->endDate()
        );
    }
}
