<?php

declare(strict_types=1);

namespace App\Domain;

use App\Domain\Exception\InvalidAmountException;
use DateTimeImmutable;
use InvalidArgumentException;

final class Expense
{
    private int $childId;
    private int $weekId;
    private DateTimeImmutable $date;
    private string $category;
    private float $amount;
    private ?string $description;

    public function __construct(
        int $childId,
        int $weekId,
        ?DateTimeImmutable $date,
        string $category,
        float $amount,
        ?string $description = null
    ) {
        if ($date === null) {
            throw new InvalidArgumentException('Date is required');
        }
        if ($category === '') {
            throw new InvalidArgumentException('Category is required');
        }
        if ($amount <= 0) {
            throw new InvalidAmountException('Amount must be positive');
        }

        $this->childId = $childId;
        $this->weekId = $weekId;
        $this->date = $date;
        $this->category = $category;
        $this->amount = $amount;
        $this->description = $description;
    }

    public function childId(): int
    {
        return $this->childId;
    }

    public function weekId(): int
    {
        return $this->weekId;
    }

    public function date(): DateTimeImmutable
    {
        return $this->date;
    }

    public function category(): string
    {
        return $this->category;
    }

    public function amount(): float
    {
        return $this->amount;
    }

    public function description(): ?string
    {
        return $this->description;
    }
}
