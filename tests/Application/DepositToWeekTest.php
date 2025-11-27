<?php

declare(strict_types=1);

namespace Tests\Application;

use App\Application\DepositToWeek;
use App\Application\Dto\WeekSummaryDto;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Tests\Application\Double\FakeWeekRepository;
use Tests\Support\WeekBuilder;

final class DepositToWeekTest extends TestCase
{
    private FakeWeekRepository $repository;
    private DepositToWeek $useCase;

    protected function setUp(): void
    {
        $this->repository = new FakeWeekRepository();
        $this->useCase = new DepositToWeek($this->repository);
    }

    public function testDepotAugmenteBudgetEtSolde(): void
    {
        $week = (new WeekBuilder())
            ->withChildId(5)
            ->withBudget(20.0)
            ->withTotalExpenses(5.0)
            ->build();
        $this->repository->save($week, 1);

        $dto = $this->useCase->execute(weekId: 1, childId: 5, amount: 10.0);

        $this->assertInstanceOf(WeekSummaryDto::class, $dto);
        $this->assertSame(30.0, $dto->budget);
        $this->assertSame(5.0, $dto->totalExpenses);
        $this->assertSame(25.0, $dto->balance);

        $updated = $this->repository->findByIdForChild(1, 5);
        $this->assertSame(30.0, $updated->budget());
        $this->assertSame(25.0, $updated->balance());
    }

    public function testDepotMontantNonPositifInterdit(): void
    {
        $week  = (new WeekBuilder())
            ->withChildId(5)
            ->withBudget(20.0)
            ->build();
        $this->repository->save($week, 1);

        $this->expectException(InvalidArgumentException::class);

        $this->useCase->execute(weekId: 1, childId: 5, amount: 0.0);
    }

    public function testDepotSurSemaineInexistanteDoitLeverException(): void
    {
        $this->expectException(RuntimeException::class);

        $this->useCase->execute(weekId: 99, childId: 5, amount: 10.0);
    }

    public function testDepotSurSemaineDunAutreEnfantDoitLeverException(): void
    {
        $week  = (new WeekBuilder())
            ->withChildId(9)
            ->withBudget(20.0)
            ->build();
        $this->repository->save($week, 1);

        $this->expectException(RuntimeException::class);

        $this->useCase->execute(weekId: 1, childId: 5, amount: 10.0);
    }

    public function testDepotNeModifiePasLesDatesNiLesDepenses(): void
    {
        $week = (new WeekBuilder())
            ->withChildId(15)
            ->withBudget(25.0)
            ->withTotalExpenses(4.0)
            ->withStartDate(new DateTimeImmutable('2024-06-10'))
            ->build();
        $this->repository->save($week, 3);

        $dto = $this->useCase->execute(weekId: 3, childId: 15, amount: 5.0);

        $this->assertEquals($week->startDate(), $dto->startDate);
        $this->assertEquals($week->endDate(), $dto->endDate);
        $this->assertEqualsWithDelta(4.0, $dto->totalExpenses, 0.001);

        $stored = $this->repository->findByIdForChild(3, 15);
        $this->assertEquals($week->startDate(), $stored?->startDate());
        $this->assertEquals($week->endDate(), $stored?->endDate());
        $this->assertEqualsWithDelta(4.0, $stored?->totalExpenses(), 0.001);
    }
}

