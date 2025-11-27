<?php

declare(strict_types=1);

namespace Tests\Domain;

use App\Domain\Expense;
use App\Domain\Exception\InvalidAmountException;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class ExpenseTest extends TestCase
{
    public function testExpenseBelongsToChildAndWeekAndStoresFields(): void
    {
        $date = new DateTimeImmutable('2024-06-05');
        $expense = new Expense(
            childId: 7,
            weekId: 3,
            date: $date,
            category: 'food',
            amount: 12.5,
            description: 'Burger'
        );

        $this->assertSame(7, $expense->childId());
        $this->assertSame(3, $expense->weekId());
        $this->assertSame($date, $expense->date());
        $this->assertSame('food', $expense->category());
        $this->assertSame(12.5, $expense->amount());
        $this->assertSame('Burger', $expense->description());
    }

    public function testDateIsRequired(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Expense(
            childId: 1,
            weekId: 1,
            date: null,
            category: 'transport',
            amount: 5.0
        );
    }

    public function testCategoryIsRequired(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Expense(
            childId: 1,
            weekId: 1,
            date: new DateTimeImmutable('2024-06-05'),
            category: '',
            amount: 5.0
        );
    }

    public function testAmountMustBeStrictlyPositive(): void
    {
        $this->expectException(InvalidAmountException::class);
        new Expense(
            childId: 1,
            weekId: 1,
            date: new DateTimeImmutable('2024-06-05'),
            category: 'transport',
            amount: 0.0
        );
    }

    public function testDescriptionPeutEtreNull(): void
    {
        $date = new DateTimeImmutable('2024-06-05');
        $expense = new Expense(
            childId: 3,
            weekId: 2,
            date: $date,
            category: 'books',
            amount: 8.5,
            description: null
        );

        $this->assertNull($expense->description());
        $this->assertSame(8.5, $expense->amount());
    }
}
