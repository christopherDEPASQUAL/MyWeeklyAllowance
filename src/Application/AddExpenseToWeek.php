<?php

declare(strict_types=1);

namespace App\Application;

use App\Application\Dto\ExpenseSummaryDto;
use App\Domain\Repository\ExpenseRepositoryInterface;
use App\Domain\Repository\WeekRepositoryInterface;
use App\Domain\Week;
use DateTimeImmutable;
use InvalidArgumentException;
use RuntimeException;

final class AddExpenseToWeek
{
    public function __construct(
        private WeekRepositoryInterface $weekRepository,
        private ExpenseRepositoryInterface $expenseRepository
    ) {
    }

    public function execute(
        int $childId,
        int $weekId,
        string $category,
        float $amount,
        DateTimeImmutable $date,
        ?string $description = null
    ): ExpenseSummaryDto {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Amount must be positive');
        }

        $week = $this->weekRepository->findByIdForChild($weekId, $childId);
        if (!$week instanceof Week) {
            throw new RuntimeException('Week not found for child');
        }

        $week->addExpense($category, $amount, $date);
        $this->weekRepository->save($week, $weekId);

        $this->expenseRepository->save($weekId, $category, $amount, $date, $description);

        return new ExpenseSummaryDto(
            weekId: $weekId,
            category: $category,
            amount: $amount,
            date: $date,
            newBalance: $week->balance()
        );
    }
}
