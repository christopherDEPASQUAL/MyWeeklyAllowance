<?php

declare(strict_types=1);

namespace Tests\Application\Double;

use App\Domain\Repository\WeekRepositoryInterface;
use App\Domain\Week;
use RuntimeException;

/**
 * Double in-memory pour tests application, sans dépendre d'Infrastructure.
 */
final class FakeWeekRepository implements WeekRepositoryInterface
{
    /** @var array<int, Week> */
    private array $weeks = [];
    private int $nextId = 1;

    public function findByIdForChild(int $weekId, int $childId): ?Week
    {
        $week = $this->weeks[$weekId] ?? null;
        if ($week === null || $week->childId() !== $childId) {
            return null;
        }
        return $week;
    }

    public function save(Week $week, ?int $weekId = null): int
    {
        if ($weekId === null) {
            $weekId = $this->nextId++;
        }

        // unicité sur (childId, startDate)
        foreach ($this->weeks as $id => $stored) {
            if ($id !== $weekId && $stored->childId() === $week->childId() && $stored->startDate() == $week->startDate()) {
                throw new RuntimeException('Week already exists for this child and start date');
            }
        }

        $this->weeks[$weekId] = $week;
        return $weekId;
    }

    public function incrementExpenses(int $weekId, float $amount): void
    {
        throw new RuntimeException('Not implemented in fake');
    }
}
