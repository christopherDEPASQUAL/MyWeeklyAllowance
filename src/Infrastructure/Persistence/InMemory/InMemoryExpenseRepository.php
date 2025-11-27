<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\InMemory;

use App\Domain\Repository\ExpenseRepositoryInterface;
use DateTimeImmutable;

final class InMemoryExpenseRepository implements ExpenseRepositoryInterface
{
    /** @var array<int, array{weekId:int,category:string,amount:float,date:DateTimeImmutable}> */
    private array $expenses = [];

    public function save(int $weekId, string $category, float $amount, DateTimeImmutable $date, ?string $description = null): void
    {
        $this->expenses[] = [
            'weekId' => $weekId,
            'category' => $category,
            'amount' => $amount,
            'date' => $date,
            'description' => $description,
        ];
    }

    /** @return array<int, array{weekId:int,category:string,amount:float,date:DateTimeImmutable,description:?string}> */
    public function all(): array
    {
        return $this->expenses;
    }
}
