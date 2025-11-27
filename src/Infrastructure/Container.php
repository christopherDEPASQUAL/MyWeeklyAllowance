<?php

declare(strict_types=1);

namespace App\Infrastructure;

use App\Application\AddExpenseToWeek;
use App\Application\ApplyWeeklyAllowance;
use App\Application\DepositToWeek;
use App\Application\GetWeekSummary;
use App\Domain\Repository\ExpenseRepositoryInterface;
use App\Domain\Repository\WeekRepositoryInterface;
use App\Infrastructure\Persistence\Pdo\PdoExpenseRepository;
use App\Infrastructure\Persistence\Pdo\PdoWeekRepository;
use PDO;

/**
 * Wiring minimal pour instancier PDO et les repositories SQL.
 * On laisse l’in-memory pour les tests unitaires rapides.
 */
final class Container
{
    private ?PDO $pdo = null;
    private ?WeekRepositoryInterface $weekRepository = null;
    private ?ExpenseRepositoryInterface $expenseRepository = null;

    public function pdo(): PDO
    {
        if ($this->pdo !== null) {
            return $this->pdo;
        }

        $host = getenv('DB_HOST') ?: 'localhost';
        $port = (int) (getenv('DB_PORT') ?: 3306);
        $db   = getenv('DB_NAME') ?: 'my_weekly_allowance';
        $user = getenv('DB_USER') ?: 'root';
        $pass = getenv('DB_PASSWORD') ?: '';

        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $host, $port, $db);

        $this->pdo = new PDO(
            $dsn,
            $user,
            $pass,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );

        return $this->pdo;
    }

    public function weekRepository(): WeekRepositoryInterface
    {
        if ($this->weekRepository === null) {
            $this->weekRepository = new PdoWeekRepository($this->pdo());
        }
        return $this->weekRepository;
    }

    public function expenseRepository(): ExpenseRepositoryInterface
    {
        if ($this->expenseRepository === null) {
            $this->expenseRepository = new PdoExpenseRepository($this->pdo());
        }
        return $this->expenseRepository;
    }

    // Use cases
    public function addExpenseToWeek(): AddExpenseToWeek
    {
        return new AddExpenseToWeek($this->weekRepository(), $this->expenseRepository());
    }

    public function depositToWeek(): DepositToWeek
    {
        return new DepositToWeek($this->weekRepository());
    }

    public function applyWeeklyAllowance(): ApplyWeeklyAllowance
    {
        return new ApplyWeeklyAllowance($this->weekRepository());
    }

    public function getWeekSummary(): GetWeekSummary
    {
        return new GetWeekSummary($this->weekRepository());
    }
}
