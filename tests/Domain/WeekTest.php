<?php

declare(strict_types=1);

namespace Tests\Domain;

use App\Domain\Week;
use DateTimeImmutable;
use DomainException;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class WeekTest extends TestCase
{
    private DateTimeImmutable $monday;
    private DateTimeImmutable $sunday;

    protected function setUp(): void
    {
        $this->monday = new DateTimeImmutable('2024-06-03');
        $this->sunday = new DateTimeImmutable('2024-06-09');
    }

    public function testWeekBelongsToChildAndKeepsBudget(): void
    {
        $week = new Week(42, 50.0, $this->monday, $this->sunday);

        $this->assertSame(42, $week->childId());
        $this->assertSame(50.0, $week->budget());
        $this->assertSame(50.0, $week->balance());
    }

    public function testBudgetMustBePositive(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Week(1, 0, $this->monday, $this->sunday);
    }

    public function testStartDateMustBeBeforeOrEqualEndDate(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Week(1, 10.0, $this->sunday, $this->monday->modify('-1 day'));
    }

    public function testAddExpenseWithinRangeUpdatesBalance(): void
    {
        $week = new Week(1, 50.0, $this->monday, $this->sunday);

        $week->addExpense('cinema', 12.5, $this->monday->modify('+2 days'));

        $this->assertEqualsWithDelta(37.5, $week->balance(), 0.001);
        $this->assertEqualsWithDelta(12.5, $week->totalExpenses(), 0.001);
    }

    public function testAddExpenseThrowsWhenDateOutOfRange(): void
    {
        $week = new Week(1, 50.0, $this->monday, $this->sunday);

        $this->expectException(DomainException::class);
        $week->addExpense('before', 5.0, $this->monday->modify('-1 day'));
    }

    public function testAddExpenseThrowsWhenAmountNotPositive(): void
    {
        $week = new Week(1, 50.0, $this->monday, $this->sunday);

        $this->expectException(InvalidArgumentException::class);
        $week->addExpense('bad', 0.0, $this->monday);
    }

    public function testAddFundsMustBePositive(): void
    {
        $week = new Week(1, 20.0, $this->monday, $this->sunday);

        $this->expectException(InvalidArgumentException::class);
        $week->addFunds(0.0);
    }

    public function testAddExpenseOnBoundsIsAllowed(): void
    {
        $week = new Week(1, 50.0, $this->monday, $this->sunday);

        $week->addExpense('start', 10.0, $this->monday);
        $week->addExpense('end', 5.0, $this->sunday);

        $this->assertEqualsWithDelta(35.0, $week->balance(), 0.001);
        $this->assertEqualsWithDelta(15.0, $week->totalExpenses(), 0.001);
    }

    public function testAddExpenseCannotExceedRemainingBudget(): void
    {
        $week = new Week(1, 20.0, $this->monday, $this->sunday);

        $week->addExpense('first', 15.0, $this->monday);

        $this->expectException(DomainException::class);
        $week->addExpense('too_much', 10.0, $this->monday->modify('+1 day'));
    }

    public function testSetTotalExpensesCannotBeNegative(): void
    {
        $week = new Week(1, 30.0, $this->monday, $this->sunday);

        $this->expectException(InvalidArgumentException::class);
        $week->setTotalExpenses(-1.0);
    }

    public function testSetTotalExpensesCannotExceedBudget(): void
    {
        $week = new Week(1, 30.0, $this->monday, $this->sunday);

        $this->expectException(DomainException::class);
        $week->setTotalExpenses(40.0);
    }

    public function testTotalExpensesStartsAtZero(): void
    {
        $week = new Week(1, 30.0, $this->monday, $this->sunday);

        $this->assertEqualsWithDelta(0.0, $week->totalExpenses(), 0.001);
    }
}
