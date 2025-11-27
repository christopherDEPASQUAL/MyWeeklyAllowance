<?php

declare(strict_types=1);

namespace App\Application;

use App\Application\Dto\WeekSummaryDto;
use App\Domain\Repository\WeekRepositoryInterface;
use App\Domain\Week;
use InvalidArgumentException;
use RuntimeException;

final class DepositToWeek
{
    public function __construct(private WeekRepositoryInterface $weekRepository)
    {
    }

    public function execute(int $weekId, int $childId, float $amount): WeekSummaryDto
    {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Deposit amount must be positive');
        }

        $week = $this->weekRepository->findByIdForChild($weekId, $childId);

        if (!$week instanceof Week) {
            throw new RuntimeException('Week not found for child');
        }

        $week->addFunds($amount);
        $this->weekRepository->save($week, $weekId);

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
