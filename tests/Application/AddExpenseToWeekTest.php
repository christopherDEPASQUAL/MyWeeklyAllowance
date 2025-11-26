<?php

declare(strict_types=1);

namespace Tests\Application;

use App\Application\AddExpenseToWeek;
use App\Application\Dto\ExpenseSummaryDto;
use App\Domain\Repository\ExpenseRepositoryInterface;
use App\Domain\Repository\WeekRepositoryInterface;
use DateTimeImmutable;
use DomainException;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class AddExpenseToWeekTest extends TestCase
{
    private FakeWeekRepository $weekRepository;
    private FakeExpenseRepository $expenseRepository;
    private AddExpenseToWeek $useCase;

    protected function setUp(): void
    {
        $this->weekRepository = new FakeWeekRepository();
        $this->expenseRepository = new FakeExpenseRepository();
        $this->useCase = new AddExpenseToWeek(
            $this->weekRepository,
            $this->expenseRepository
        );
    }

    public function testAjoutDepenseValideMetAJourLeSolde(): void
    {
        $this->weekRepository->storeWeek(
            weekId: 1,
            childId: 10,
            budget: 50.0,
            totalExpenses: 10.0,
            start: new DateTimeImmutable('2024-06-03'),
            end: new DateTimeImmutable('2024-06-09')
        );

        $dto = $this->useCase->execute(
            childId: 10,
            weekId: 1,
            category: 'food',
            amount: 5.0,
            date: new DateTimeImmutable('2024-06-05')
        );

        $this->assertSame(1, $dto->weekId);
        $this->assertSame('food', $dto->category);
        $this->assertSame(5.0, $dto->amount);
        $this->assertEquals(new DateTimeImmutable('2024-06-05'), $dto->date);
        $this->assertEquals(35.0, $dto->newBalance); // budget 50 - (10+5)
        $this->assertEquals(15.0, $this->weekRepository->getTotalExpenses(1));
    }

    public function testAjoutDepenseDansUneSemaineInexistanteDoitLeverException(): void
    {
        $this->expectException(RuntimeException::class);

        $this->useCase->execute(
            childId: 10,
            weekId: 999,
            category: 'food',
            amount: 5.0,
            date: new DateTimeImmutable('2024-06-05')
        );
    }

    public function testAjoutDepenseAvecMontantNegatifDoitLeverException(): void
    {
        $this->weekRepository->storeWeek(
            weekId: 1,
            childId: 10,
            budget: 50.0,
            totalExpenses: 0.0,
            start: new DateTimeImmutable('2024-06-03'),
            end: new DateTimeImmutable('2024-06-09')
        );

        $this->expectException(InvalidArgumentException::class);

        $this->useCase->execute(
            childId: 10,
            weekId: 1,
            category: 'food',
            amount: -1.0,
            date: new DateTimeImmutable('2024-06-05')
        );
    }

    public function testAjoutDepenseHorsPlageDoitLeverException(): void
    {
        $this->weekRepository->storeWeek(
            weekId: 1,
            childId: 10,
            budget: 50.0,
            totalExpenses: 0.0,
            start: new DateTimeImmutable('2024-06-03'),
            end: new DateTimeImmutable('2024-06-09')
        );

        $this->expectException(DomainException::class);

        $this->useCase->execute(
            childId: 10,
            weekId: 1,
            category: 'food',
            amount: 5.0,
            date: new DateTimeImmutable('2024-06-12')
        );
    }

    public function testLeDTORetourneContientLesBonnesValeurs(): void
    {
        $this->weekRepository->storeWeek(
            weekId: 2,
            childId: 7,
            budget: 100.0,
            totalExpenses: 20.0,
            start: new DateTimeImmutable('2024-06-03'),
            end: new DateTimeImmutable('2024-06-09')
        );

        $dto = $this->useCase->execute(
            childId: 7,
            weekId: 2,
            category: 'transport',
            amount: 15.0,
            date: new DateTimeImmutable('2024-06-07')
        );

        $this->assertInstanceOf(ExpenseSummaryDto::class, $dto);
        $this->assertSame(2, $dto->weekId);
        $this->assertSame('transport', $dto->category);
        $this->assertSame(15.0, $dto->amount);
        $this->assertEquals(new DateTimeImmutable('2024-06-07'), $dto->date);
        $this->assertEquals(65.0, $dto->newBalance); // budget 100 - (20+15)
    }
}

final class FakeWeekRepository implements WeekRepositoryInterface
{
    /** @var array<int, array{childId:int,budget:float,totalExpenses:float,start:DateTimeImmutable,end:DateTimeImmutable}> */
    private array $weeks = [];

    public function storeWeek(int $weekId, int $childId, float $budget, float $totalExpenses, DateTimeImmutable $start, DateTimeImmutable $end): void
    {
        $this->weeks[$weekId] = [
            'childId' => $childId,
            'budget' => $budget,
            'totalExpenses' => $totalExpenses,
            'start' => $start,
            'end' => $end,
        ];
    }

    public function findByIdForChild(int $weekId, int $childId): ?array
    {
        $week = $this->weeks[$weekId] ?? null;
        if ($week === null || $week['childId'] !== $childId) {
            return null;
        }
        return $week;
    }

    public function getTotalExpenses(int $weekId): float
    {
        return $this->weeks[$weekId]['totalExpenses'] ?? 0.0;
    }

    public function incrementExpenses(int $weekId, float $amount): void
    {
        $this->weeks[$weekId]['totalExpenses'] += $amount;
    }
}

final class FakeExpenseRepository implements ExpenseRepositoryInterface
{
    /** @var array<int, array> */
    public array $expenses = [];

    public function save(int $weekId, int $childId, string $category, float $amount, DateTimeImmutable $date): void
    {
        $this->expenses[] = compact('weekId', 'childId', 'category', 'amount', 'date');
    }
}
