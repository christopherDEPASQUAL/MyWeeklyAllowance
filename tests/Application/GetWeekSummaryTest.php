<?php

declare(strict_types=1);

namespace Tests\Application;

use App\Application\Dto\WeekSummaryDto;
use App\Application\GetWeekSummary;
use App\Domain\Week;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Tests\Application\Double\FakeWeekRepository;

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

        $this->repository->save($week, 1);

        // Act
        $dto = $this->useCase->execute(weekId: 1, childId: 42);

        // Assert
        $this->assertInstanceOf(WeekSummaryDto::class, $dto);
        $this->assertSame(1, $dto->weekId);
        $this->assertSame(42, $dto->childId);
        $this->assertEqualsWithDelta(50.0, $dto->budget, 0.001);
        $this->assertEqualsWithDelta(10.0, $dto->totalExpenses, 0.001);
        $this->assertEqualsWithDelta(40.0, $dto->balance, 0.001);
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

        $this->repository->save($week, 5);

        $this->expectException(RuntimeException::class);

        $this->useCase->execute(weekId: 5, childId: 99);
    }

    public function testRetourneZeroDepensesParDefaut(): void
    {
        $start = new DateTimeImmutable('2024-07-01');
        $end   = new DateTimeImmutable('2024-07-07');

        $week = new Week(
            childId: 50,
            budget: 25.0,
            startDate: $start,
            endDate: $end
        );

        $this->repository->save($week, 10);

        $dto = $this->useCase->execute(weekId: 10, childId: 50);

        $this->assertEqualsWithDelta(0.0, $dto->totalExpenses, 0.001);
        $this->assertEqualsWithDelta(25.0, $dto->balance, 0.001);
    }
}

