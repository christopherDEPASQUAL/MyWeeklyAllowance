<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Pdo;

use App\Domain\Repository\WeekRepositoryInterface;
use App\Domain\Week;
use DateTimeImmutable;
use PDO;
use PDOException;
use RuntimeException;

final class PdoWeekRepository implements WeekRepositoryInterface
{
    public function __construct(private PDO $pdo)
    {
    }

    public function findByIdForChild(int $weekId, int $childId): ?Week
    {
        $stmt = $this->pdo->prepare('SELECT id, child_id, budget, start_date, end_date FROM weeks WHERE id = :id AND child_id = :child_id');
        $stmt->execute([':id' => $weekId, ':child_id' => $childId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            return null;
        }

        $week = new Week(
            childId: (int) $row['child_id'],
            budget: (float) $row['budget'],
            startDate: new DateTimeImmutable($row['start_date']),
            endDate: new DateTimeImmutable($row['end_date'])
        );

        $total = $this->computeTotalExpenses($weekId);
        $week->setTotalExpenses($total);

        return $week;
    }

    public function save(Week $week, ?int $weekId = null): int
    {
        $this->pdo->beginTransaction();
        try {
            if ($weekId === null) {
                $insert = $this->pdo->prepare(
                    'INSERT INTO weeks (child_id, budget, start_date, end_date) VALUES (:child_id, :budget, :start_date, :end_date)'
                );
                $insert->execute([
                    ':child_id' => $week->childId(),
                    ':budget' => $week->budget(),
                    ':start_date' => $week->startDate()->format('Y-m-d'),
                    ':end_date' => $week->endDate()->format('Y-m-d'),
                ]);
                $generatedId = (int) $this->pdo->lastInsertId();
            } else {
                $update = $this->pdo->prepare(
                    'UPDATE weeks SET child_id = :child_id, budget = :budget, start_date = :start_date, end_date = :end_date WHERE id = :id'
                );
                $update->execute([
                    ':id' => $weekId,
                    ':child_id' => $week->childId(),
                    ':budget' => $week->budget(),
                    ':start_date' => $week->startDate()->format('Y-m-d'),
                    ':end_date' => $week->endDate()->format('Y-m-d'),
                ]);
                if ($update->rowCount() === 0) {
                    $exists = $this->pdo->prepare('SELECT 1 FROM weeks WHERE id = :id');
                    $exists->execute([':id' => $weekId]);
                    if (!$exists->fetchColumn()) {
                        throw new RuntimeException('Week not found for update');
                    }
                }
                $generatedId = $weekId;
            }

            $this->pdo->commit();
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            throw new RuntimeException('Failed to save week', previous: $e);
        }

        return $generatedId;
    }

    public function incrementExpenses(int $weekId, float $amount): void
    {
        // With expenses normalized, totals are computed on read; increment not needed.
        // This is kept for interface compatibility.
        throw new RuntimeException('incrementExpenses not supported; totals are derived from expenses');
    }

    private function computeTotalExpenses(int $weekId): float
    {
        $stmt = $this->pdo->prepare('SELECT COALESCE(SUM(amount),0) as total FROM expenses WHERE week_id = :week_id');
        $stmt->execute([':week_id' => $weekId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row === false ? 0.0 : (float) $row['total'];
    }
}
