<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Domain\Week;
use DateTimeImmutable;

final class WeekBuilder
{
    private int $childId = 1;
    private float $budget = 50.0;
    private float $totalExpenses = 0.0;
    private DateTimeImmutable $startDate;
    private DateTimeImmutable $endDate;

    public function __construct()
    {
        $this->startDate = new DateTimeImmutable('2024-06-03');
        $this->endDate = $this->startDate->modify('+6 days');
    }

    public function withChildId(int $childId): self
    {
        $clone = clone $this;
        $clone->childId = $childId;
        return $clone;
    }

    public function withBudget(float $budget): self
    {
        $clone = clone $this;
        $clone->budget = $budget;
        return $clone;
    }

    public function withTotalExpenses(float $totalExpenses): self
    {
        $clone = clone $this;
        $clone->totalExpenses = $totalExpenses;
        return $clone;
    }

    public function withStartDate(DateTimeImmutable $startDate): self
    {
        $clone = clone $this;
        $clone->startDate = $startDate;
        $clone->endDate = $startDate->modify('+6 days');
        return $clone;
    }

    public function withEndDate(DateTimeImmutable $endDate): self
    {
        $clone = clone $this;
        $clone->endDate = $endDate;
        return $clone;
    }

    public function build(): Week
    {
        $week = new Week(
            childId: $this->childId,
            budget: $this->budget,
            startDate: $this->startDate,
            endDate: $this->endDate
        );

        if ($this->totalExpenses > 0) {
            $week->setTotalExpenses($this->totalExpenses);
        }

        return $week;
    }
}
