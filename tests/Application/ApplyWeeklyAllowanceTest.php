<?php

declare(strict_types=1);

namespace Tests\Application;

use App\Application\ApplyWeeklyAllowance;
use App\Application\Dto\WeekSummaryDto;
use App\Domain\Week;
use DateTimeImmutable;
use DomainException;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Tests\Application\Double\FakeWeekRepository;

final class ApplyWeeklyAllowanceTest extends TestCase
{
    private FakeWeekRepository $repository;
    private ApplyWeeklyAllowance $useCase;

    protected function setUp(): void
    {
        $this->repository = new FakeWeekRepository();
        $this->useCase = new ApplyWeeklyAllowance($this->repository);
    }

    public function testCreeUneSemaineAvecAllocationAutomatique(): void
    {
        $start = new DateTimeImmutable('2024-06-03'); // lundi

        $dto = $this->useCase->execute(
            childId: 7,
            weeklyAmount: 30.0,
            startDate: $start
        );

        $this->assertInstanceOf(WeekSummaryDto::class, $dto);
        $this->assertSame(1, $dto->weekId);
        $this->assertSame(7, $dto->childId);
        $this->assertSame(30.0, $dto->budget);
        $this->assertSame(0.0, $dto->totalExpenses);
        $this->assertSame(30.0, $dto->balance);
        $this->assertEquals($start, $dto->startDate);
        $this->assertEquals($start->modify('+6 days'), $dto->endDate);

        $stored = $this->repository->findByIdForChild(1, 7);
        $this->assertSame(30.0, $stored->budget());
        $this->assertSame(30.0, $stored->balance());
    }

    public function testMontantHebdomadaireNonPositifInterdit(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->useCase->execute(
            childId: 7,
            weeklyAmount: 0.0,
            startDate: new DateTimeImmutable('2024-06-03')
        );
    }

    public function testRefuseCreationSiSemaineExisteDeja(): void
    {
        $start = new DateTimeImmutable('2024-06-03');
        $existing = new Week(childId: 7, budget: 25.0, startDate: $start, endDate: $start->modify('+6 days'));
        $this->repository->save($existing, 3);

        $this->expectException(RuntimeException::class);

        $this->useCase->execute(
            childId: 7,
            weeklyAmount: 30.0,
            startDate: $start
        );
    }

    public function testRefuseDateDeDebutNonLundi(): void
    {
        $start = new DateTimeImmutable('2024-06-05'); // mercredi

        $this->expectException(DomainException::class);

        $this->useCase->execute(
            childId: 7,
            weeklyAmount: 30.0,
            startDate: $start
        );
    }
}

