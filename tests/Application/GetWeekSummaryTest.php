<?php

declare(strict_types=1);

namespace Tests\Application;

use App\Application\Dto\WeekSummaryDto;
use App\Application\GetWeekSummary;
use App\Domain\Repository\WeekRepositoryInterface;
use App\Domain\Week;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class GetWeekSummaryTest extends TestCase
{
    private FakeWeekRepository $repository;
    private GetWeekSummary $useCase;

    protected function setUp(): void
    {
        $this->repository = new FakeWeekRepository();
        $this->useCase = new GetWeekSummary($this->repository);
    }

    public function testReturnsWeekSummaryDtoWhenWeekExists(): void
    {
        // Arrange
        $start = new DateTimeImmutable('2024-06-03');
        $end   = new DateTimeImmutable('2024-06-09');

        $week = new Week(
            childId: 42,
            budget: 50.0,
            startDate: $start,
            endDate: $end
        );
        $week->setTotalExpenses(10.0);

        $this->repository->save(1, $week);

        // Act
        $dto = $this->useCase->execute(weekId: 1, childId: 42);

        // Assert
        $this->assertInstanceOf(WeekSummaryDto::class, $dto);
        $this->assertSame(1, $dto->weekId);
        $this->assertSame(42, $dto->childId);
        $this->assertSame(50.0, $dto->budget);
        $this->assertSame(10.0, $dto->totalExpenses);
        $this->assertSame(40.0, $dto->balance);
        $this->assertEquals($start, $dto->startDate);
        $this->assertEquals($end, $dto->endDate);
    }

    public function testThrowsWhenWeekDoesNotExist(): void
    {
        $this->expectException(RuntimeException::class);

        $this->useCase->execute(weekId: 999, childId: 1);
    }

    public function testThrowsWhenChildDoesNotMatchWeek(): void
    {
        $start = new DateTimeImmutable('2024-06-10');
        $end   = new DateTimeImmutable('2024-06-16');

        $week = new Week(
            childId: 10,
            budget: 30.0,
            startDate: $start,
            endDate: $end
        );

        $this->repository->save(5, $week);

        $this->expectException(RuntimeException::class);

        $this->useCase->execute(weekId: 5, childId: 99);
    }
}

final class FakeWeekRepository implements WeekRepositoryInterface
{
    /** @var array<int, Week> */
    private array $weeks = [];

    public function save(int $weekId, Week $week): void
    {
        $this->weeks[$weekId] = $week;
    }

    public function findByIdForChild(int $weekId, int $childId): ?Week
    {
        $week = $this->weeks[$weekId] ?? null;

        if ($week === null) {
            return null;
        }

        if ($week->childId() !== $childId) {
            return null;
        }

        return $week;
    }
}
