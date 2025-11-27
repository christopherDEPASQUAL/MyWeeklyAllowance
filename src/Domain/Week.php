<?php

declare(strict_types=1);

namespace App\Domain;

use DateTimeImmutable;
use DomainException;
use InvalidArgumentException;

final class Week
{
    private int $childId;
    private float $budget;
    private float $totalExpenses = 0.0;
    private DateTimeImmutable $startDate;
    private DateTimeImmutable $endDate;

    public function __construct(int $childId, float $budget, DateTimeImmutable $startDate, DateTimeImmutable $endDate)
    {
        if ($budget <= 0) {
            throw new InvalidArgumentException('Budget must be positive');
        }
        if ($startDate > $endDate) {
            throw new InvalidArgumentException('Start date must be before or equal to end date');
        }

        $this->childId = $childId;
        $this->budget = $budget;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function childId(): int
    {
        return $this->childId;
    }

    public function budget(): float
    {
        return $this->budget;
    }

    public function balance(): float
    {
        return $this->budget - $this->totalExpenses;
    }

    public function totalExpenses(): float
    {
        return $this->totalExpenses;
    }

    public function startDate(): DateTimeImmutable
    {
        return $this->startDate;
    }

    public function endDate(): DateTimeImmutable
    {
        return $this->endDate;
    }

    public function addExpense(string $category, float $amount, DateTimeImmutable $date): void
    {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Amount must be positive');
        }

        if ($date < $this->startDate || $date > $this->endDate) {
            throw new DomainException('Expense date out of range');
        }

        if ($this->totalExpenses + $amount > $this->budget + 1e-9) {
            throw new DomainException('Expense exceeds remaining budget');
        }

        $this->totalExpenses += $amount;
    }

    public function setTotalExpenses(float $totalExpenses): void
    {
        if ($totalExpenses < 0) {
            throw new InvalidArgumentException('Total expenses cannot be negative');
        }

        if ($totalExpenses > $this->budget + 1e-9) {
            throw new DomainException('Total expenses cannot exceed budget');
        }

        $this->totalExpenses = $totalExpenses;
    }

    public function addFunds(float $amount): void
    {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Added funds must be positive');
        }

        $this->budget += $amount;
    }
}
