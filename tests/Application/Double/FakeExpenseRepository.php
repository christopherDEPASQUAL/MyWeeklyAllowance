<?php

declare(strict_types=1);

namespace Tests\Application\Double;

use App\Domain\Repository\ExpenseRepositoryInterface;
use DateTimeImmutable;

final class FakeExpenseRepository implements ExpenseRepositoryInterface
{
    /** @var array<int, array{weekId:int, category:string, amount:float, date:DateTimeImmutable}> */
    public array $expenses = [];

    public function save(int $weekId, string $category, float $amount, DateTimeImmutable $date): void
    {
        $this->expenses[] = [
            'weekId' => $weekId,
            'category' => $category,
            'amount' => $amount,
            'date' => $date,
        ];
    }

    /**
     * @return array<int, array{weekId:int, category:string, amount:float, date:DateTimeImmutable}>
     */
    public function all(): array
    {
        return $this->expenses;
    }
}
