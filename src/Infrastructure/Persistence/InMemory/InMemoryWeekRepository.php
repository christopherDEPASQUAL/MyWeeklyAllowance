<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\InMemory;

use App\Domain\Repository\WeekRepositoryInterface;
use App\Domain\Week;
use RuntimeException;

final class InMemoryWeekRepository implements WeekRepositoryInterface
{
    /** @var array<int, Week> */
    private array $weeks = [];
    private int $nextId = 1;

    public function save(Week $week, ?int $weekId = null): int
    {
        if ($weekId === null) {
            $weekId = $this->nextId++;
        } else {
            if ($weekId >= $this->nextId) {
                $this->nextId = $weekId + 1;
            }
        }

        // enforce uniqueness child+start date
        foreach ($this->weeks as $id => $stored) {
            if ($id !== $weekId && $stored->childId() === $week->childId() && $stored->startDate() == $week->startDate()) {
                throw new RuntimeException('Week already exists for child and start date');
            }
        }

        $this->weeks[$weekId] = $week;
        return $weekId;
    }

    public function findByIdForChild(int $weekId, int $childId): ?Week
    {
        $week = $this->weeks[$weekId] ?? null;
        if ($week === null || $week->childId() !== $childId) {
            return null;
        }
        return $week;
    }

    public function incrementExpenses(int $weekId, float $amount): void
    {
        $week = $this->weeks[$weekId] ?? null;
        if ($week === null) {
            throw new RuntimeException('Week not found');
        }
        $week->addExpense('increment', $amount, $week->startDate());
    }
}
