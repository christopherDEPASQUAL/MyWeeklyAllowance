<?php

declare(strict_types=1);

namespace App\Application;

use App\Application\Dto\WeekSummaryDto;
use App\Domain\Repository\WeekRepositoryInterface;
use App\Domain\Week;
use DateTimeImmutable;
use DomainException;
use InvalidArgumentException;
use RuntimeException;

final class ApplyWeeklyAllowance
{
    public function __construct(private WeekRepositoryInterface $weekRepository)
    {
    }

    public function execute(int $childId, float $weeklyAmount, DateTimeImmutable $startDate): WeekSummaryDto
    {
        if ($weeklyAmount <= 0) {
            throw new InvalidArgumentException('Weekly amount must be positive');
        }

        if ($startDate->format('N') !== '1') {
            throw new DomainException('Start date must be a Monday');
        }

        $week = new Week(
            childId: $childId,
            budget: $weeklyAmount,
            startDate: $startDate,
            endDate: $startDate->modify('+6 days')
        );

        $generatedId = $this->weekRepository->save($week);

        return new WeekSummaryDto(
            weekId: $generatedId,
            childId: $childId,
            budget: $week->budget(),
            totalExpenses: $week->totalExpenses(),
            balance: $week->balance(),
            startDate: $week->startDate(),
            endDate: $week->endDate()
        );
    }
}
