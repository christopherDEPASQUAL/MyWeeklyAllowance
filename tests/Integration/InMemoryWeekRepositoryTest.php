<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Domain\Week;
use App\Infrastructure\Persistence\InMemory\InMemoryWeekRepository;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class InMemoryWeekRepositoryTest extends TestCase
{
    public function testSaveGeneratesIdsAndFindsByChild(): void
    {
        $repository = new InMemoryWeekRepository();
        $start = new DateTimeImmutable('2024-06-03');
        $end = $start->modify('+6 days');

        $id = $repository->save(new Week(childId: 1, budget: 20.0, startDate: $start, endDate: $end));
        $this->assertSame(1, $id);

        $found = $repository->findByIdForChild($id, 1);
        $this->assertNotNull($found);
        $this->assertSame(20.0, $found->budget());
    }

    public function testSaveRejectsDuplicateChildAndStartDate(): void
    {
        $repository = new InMemoryWeekRepository();
        $start = new DateTimeImmutable('2024-06-03');
        $end = $start->modify('+6 days');

        $repository->save(new Week(childId: 2, budget: 15.0, startDate: $start, endDate: $end), 5);

        $this->expectException(RuntimeException::class);
        $repository->save(new Week(childId: 2, budget: 30.0, startDate: $start, endDate: $end));
    }

    public function testFindReturnsNullWhenChildDoesNotMatch(): void
    {
        $repository = new InMemoryWeekRepository();
        $start = new DateTimeImmutable('2024-06-03');
        $end = $start->modify('+6 days');

        $repository->save(new Week(childId: 3, budget: 40.0, startDate: $start, endDate: $end), 10);

        $this->assertNull($repository->findByIdForChild(10, 999));
    }

    public function testIncrementExpensesUpdatesTotal(): void
    {
        $repository = new InMemoryWeekRepository();
        $start = new DateTimeImmutable('2024-06-03');
        $end = $start->modify('+6 days');

        $week = new Week(childId: 4, budget: 50.0, startDate: $start, endDate: $end);
        $repository->save($week, 1);

        $repository->incrementExpenses(1, 5.0);

        $updated = $repository->findByIdForChild(1, 4);
        $this->assertEqualsWithDelta(5.0, $updated?->totalExpenses(), 0.001);
        $this->assertEqualsWithDelta(45.0, $updated?->balance(), 0.001);
    }

    public function testIncrementExpensesThrowsWhenWeekMissing(): void
    {
        $repository = new InMemoryWeekRepository();

        $this->expectException(RuntimeException::class);
        $repository->incrementExpenses(99, 5.0);
    }
}
