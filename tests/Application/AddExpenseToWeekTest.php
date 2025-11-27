<?php

declare(strict_types=1);

namespace Tests\Application;

use App\Application\AddExpenseToWeek;
use App\Application\Dto\ExpenseSummaryDto;
use App\Domain\Week;
use DateTimeImmutable;
use DomainException;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Tests\Application\Double\FakeExpenseRepository;
use Tests\Application\Double\FakeWeekRepository;

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
        $week = $this->createWeek(
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
        $this->assertEqualsWithDelta(35.0, $dto->newBalance, 0.001); // budget 50 - (10+5)
        $this->assertEqualsWithDelta(15.0, $this->weekRepository->findByIdForChild(1, 10)?->totalExpenses(), 0.001);
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
        $this->createWeek(
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
        $this->createWeek(
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

    public function testAjoutDepenseDepasseBudgetDoitLeverException(): void
    {
        $this->createWeek(
            weekId: 1,
            childId: 10,
            budget: 20.0,
            totalExpenses: 18.0,
            start: new DateTimeImmutable('2024-06-03'),
            end: new DateTimeImmutable('2024-06-09')
        );

        $this->expectException(DomainException::class);

        $this->useCase->execute(
            childId: 10,
            weekId: 1,
            category: 'food',
            amount: 5.0,
            date: new DateTimeImmutable('2024-06-05')
        );
    }

    public function testAjoutDepenseLeJourDeDebutOuFinEstAccepte(): void
    {
        $this->createWeek(
            weekId: 1,
            childId: 10,
            budget: 50.0,
            totalExpenses: 0.0,
            start: new DateTimeImmutable('2024-06-03'),
            end: new DateTimeImmutable('2024-06-09')
        );

        $dtoStart = $this->useCase->execute(
            childId: 10,
            weekId: 1,
            category: 'food',
            amount: 10.0,
            date: new DateTimeImmutable('2024-06-03')
        );

        $dtoEnd = $this->useCase->execute(
            childId: 10,
            weekId: 1,
            category: 'movies',
            amount: 5.0,
            date: new DateTimeImmutable('2024-06-09')
        );

        $this->assertEqualsWithDelta(35.0, $dtoEnd->newBalance, 0.001);
        $this->assertEqualsWithDelta(15.0, $this->weekRepository->findByIdForChild(1, 10)?->totalExpenses(), 0.001);
        $this->assertEquals(new DateTimeImmutable('2024-06-03'), $dtoStart->date);
        $this->assertEquals(new DateTimeImmutable('2024-06-09'), $dtoEnd->date);
    }

    public function testAjoutDepensePourUnAutreEnfantDoitLeverException(): void
    {
        $this->createWeek(
            weekId: 1,
            childId: 11,
            budget: 50.0,
            totalExpenses: 0.0,
            start: new DateTimeImmutable('2024-06-03'),
            end: new DateTimeImmutable('2024-06-09')
        );

        $this->expectException(RuntimeException::class);

        $this->useCase->execute(
            childId: 10,
            weekId: 1,
            category: 'food',
            amount: 5.0,
            date: new DateTimeImmutable('2024-06-05')
        );
    }

    public function testLaDepenseEstPersisteeAvecLesBonnesValeurs(): void
    {
        $date = new DateTimeImmutable('2024-06-05');
        $this->createWeek(
            weekId: 4,
            childId: 12,
            budget: 40.0,
            totalExpenses: 0.0,
            start: new DateTimeImmutable('2024-06-03'),
            end: new DateTimeImmutable('2024-06-09')
        );

        $this->useCase->execute(
            childId: 12,
            weekId: 4,
            category: 'books',
            amount: 7.5,
            date: $date
        );

        $all = $this->expenseRepository->all();
        $this->assertCount(1, $all);
        $expense = $all[0];
        $this->assertSame(4, $expense['weekId']);
        $this->assertSame('books', $expense['category']);
        $this->assertEqualsWithDelta(7.5, $expense['amount'], 0.001);
        $this->assertEquals($date, $expense['date']);
    }

    public function testLeDTORetourneContientLesBonnesValeurs(): void
    {
        $this->createWeek(
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
        $this->assertEqualsWithDelta(65.0, $dto->newBalance, 0.001); // budget 100 - (20+15)
    }
    
    private function createWeek(int $weekId, int $childId, float $budget, float $totalExpenses, DateTimeImmutable $start, DateTimeImmutable $end): Week
    {
        $week = new Week($childId, $budget, $start, $end);
        $week->setTotalExpenses($totalExpenses);
        $this->weekRepository->save($week, $weekId);

        return $week;
    }
}
