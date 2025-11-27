<?php

declare(strict_types=1);

namespace Tests\Application;

use App\Application\DepositToWeek;
use App\Application\Dto\WeekSummaryDto;
use App\Domain\Week;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Tests\Application\Double\FakeWeekRepository;

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
        $start = new DateTimeImmutable('2024-06-03');
        $end   = new DateTimeImmutable('2024-06-09');
        $week  = new Week(childId: 5, budget: 20.0, startDate: $start, endDate: $end);
        $week->setTotalExpenses(5.0);
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
        $start = new DateTimeImmutable('2024-06-03');
        $end   = new DateTimeImmutable('2024-06-09');
        $week  = new Week(childId: 5, budget: 20.0, startDate: $start, endDate: $end);
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
        $start = new DateTimeImmutable('2024-06-03');
        $end   = new DateTimeImmutable('2024-06-09');
        $week  = new Week(childId: 9, budget: 20.0, startDate: $start, endDate: $end);
        $this->repository->save($week, 1);

        $this->expectException(RuntimeException::class);

        $this->useCase->execute(weekId: 1, childId: 5, amount: 10.0);
    }
}

