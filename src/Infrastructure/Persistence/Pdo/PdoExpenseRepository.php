<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Pdo;

use App\Domain\Repository\ExpenseRepositoryInterface;
use DateTimeImmutable;
use PDO;
use PDOException;
use RuntimeException;

final class PdoExpenseRepository implements ExpenseRepositoryInterface
{
    public function __construct(private PDO $pdo)
    {
    }

    public function save(int $weekId, string $category, float $amount, DateTimeImmutable $date, ?string $description = null): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO expenses (week_id, category, amount, date, description) VALUES (:week_id, :category, :amount, :date, :description)'
        );

        try {
            $stmt->execute([
                ':week_id' => $weekId,
                ':category' => $category,
                ':amount' => $amount,
                ':date' => $date->format('Y-m-d'),
                ':description' => $description,
            ]);
        } catch (PDOException $e) {
            throw new RuntimeException('Failed to save expense', previous: $e);
        }
    }
}
