<?php

declare(strict_types=1);

use App\Application\AddExpenseToWeek;
use App\Domain\Week;
use App\Infrastructure\Container;

session_start();

$autoLoader = __DIR__ . '/../vendor/autoload.php';
if (is_file($autoLoader)) {
    require_once $autoLoader;
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }
    $host = getenv('DB_HOST') ?: 'db';
    $port = (int) (getenv('DB_PORT') ?: 3306);
    $name = getenv('DB_NAME') ?: 'my_weekly_allowance';
    $user = getenv('DB_USER') ?: 'root';
    $pass = getenv('DB_PASSWORD') ?: '';
    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $host, $port, $name);

    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    return $pdo;
}

function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

function ensureCsrf(): void
{
    $token = $_POST['_csrf'] ?? '';
    if (!isset($_SESSION['_csrf']) || !hash_equals((string) $_SESSION['_csrf'], (string) $token)) {
        throw new RuntimeException('CSRF token invalide.');
    }
}

function currentWeekRange(): array
{
    $today = new DateTimeImmutable('today');
    $monday = $today->modify('monday this week');
    $sunday = $monday->modify('+6 days');
    return [$monday, $sunday];
}

function ensureWeekForDate(Container $container, PDO $pdo, int $childId, DateTimeImmutable $date): int
{
    $find = $pdo->prepare('SELECT id FROM weeks WHERE child_id = :child AND :date BETWEEN start_date AND end_date ORDER BY start_date DESC LIMIT 1');
    $find->execute([':child' => $childId, ':date' => $date->format('Y-m-d')]);
    $row = $find->fetch();
    if ($row) {
        return (int) $row['id'];
    }

    $last = $pdo->prepare('SELECT budget FROM weeks WHERE child_id = :child ORDER BY start_date DESC LIMIT 1');
    $last->execute([':child' => $childId]);
    $budget = (float) ($last->fetch()['budget'] ?? 0.0);

    $start = $date->modify('monday this week');
    $end = $start->modify('+6 days');
    $week = new Week($childId, $budget, $start, $end);

    return $container->weekRepository()->save($week);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/?view=login');
}

$action = $_POST['action'] ?? '';

try {
    ensureCsrf();
    $pdo = db();

    if ($action === 'logout') {
        session_destroy();
        session_start();
        $_SESSION['flash_success'][] = 'Déconnecté.';
        redirect('/?view=login');
    }

    if ($action === 'login') {
        $email = trim($_POST['email'] ?? '');
        $password = (string) ($_POST['password'] ?? '');

        if ($email === '' || $password === '') {
            throw new InvalidArgumentException('Identifiants invalides.');
        }

        $stmt = $pdo->prepare('SELECT id, role, password_hash, parent_id FROM users WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);
        $row = $stmt->fetch();
        if (!$row || !password_verify($password, $row['password_hash'])) {
            throw new RuntimeException('Email ou mot de passe incorrect.');
        }

        $_SESSION['role'] = strtolower($row['role']);
        if ($row['role'] === 'PARENT') {
            $_SESSION['parent_id'] = (int) $row['id'];
            redirect('/?view=parent');
        }
        if ($row['role'] === 'CHILD') {
            $_SESSION['child_id'] = (int) $row['id'];
            redirect('/?view=child');
        }

        throw new RuntimeException('Rôle utilisateur inconnu.');
    }

    if ($action === 'register') {
        $parentEmail = trim($_POST['parent_email'] ?? '');
        $parentName = trim($_POST['parent_name'] ?? '');
        $parentPassword = (string) ($_POST['parent_password'] ?? '');
        $childName = trim($_POST['child_name'] ?? '');
        $childEmail = trim($_POST['child_email'] ?? '');
        $childPassword = (string) ($_POST['child_password'] ?? '');
        $childBudget = (float) ($_POST['child_budget'] ?? 0);

        $_SESSION['old'] = $_POST;

        if ($parentEmail === '' || $parentName === '' || $parentPassword === '' || $childName === '' || $childEmail === '' || $childPassword === '' || $childBudget <= 0) {
            throw new InvalidArgumentException('Tous les champs sont obligatoires et le budget doit être positif.');
        }

        $pdo->beginTransaction();
        $parentStmt = $pdo->prepare('INSERT INTO users (role, email, name, password_hash, parent_id) VALUES (\'PARENT\', :email, :name, :hash, NULL)');
        $parentStmt->execute([
            ':email' => $parentEmail,
            ':name' => $parentName,
            ':hash' => password_hash($parentPassword, PASSWORD_BCRYPT),
        ]);
        $parentId = (int) $pdo->lastInsertId();

        $childStmt = $pdo->prepare('INSERT INTO users (role, email, name, password_hash, parent_id) VALUES (\'CHILD\', :email, :name, :hash, :parent_id)');
        $childStmt->execute([
            ':email' => $childEmail,
            ':name' => $childName,
            ':hash' => password_hash($childPassword, PASSWORD_BCRYPT),
            ':parent_id' => $parentId,
        ]);
        $childId = (int) $pdo->lastInsertId();

        [$start, $end] = currentWeekRange();
        $weekStmt = $pdo->prepare('INSERT INTO weeks (child_id, budget, start_date, end_date) VALUES (:child, :budget, :start, :end)');
        $weekStmt->execute([
            ':child' => $childId,
            ':budget' => $childBudget,
            ':start' => $start->format('Y-m-d'),
            ':end' => $end->format('Y-m-d'),
        ]);

        $pdo->commit();
        unset($_SESSION['old']);
        $_SESSION['flash_success'][] = "Compte parent et enfant créés. Vous pouvez vous connecter.";
        redirect('/?view=login');
    }

    if ($action === 'deposit') {
        if (($_SESSION['role'] ?? '') !== 'parent') {
            throw new RuntimeException('Accès parent requis pour envoyer de l’argent.');
        }
        $parentId = (int) ($_SESSION['parent_id'] ?? 0);
        $childId = (int) ($_POST['child_id'] ?? 0);
        $amount = (float) ($_POST['amount'] ?? 0);

        if ($parentId <= 0 || $childId <= 0 || $amount <= 0) {
            throw new InvalidArgumentException('Données de dépôt invalides.');
        }

        $pdo->beginTransaction();
        $weekStmt = $pdo->prepare('SELECT id FROM weeks WHERE child_id = :child ORDER BY start_date DESC LIMIT 1 FOR UPDATE');
        $weekStmt->execute([':child' => $childId]);
        $week = $weekStmt->fetch();
        if ($week) {
            $update = $pdo->prepare('UPDATE weeks SET budget = budget + :amount WHERE id = :id');
            $update->execute([':amount' => $amount, ':id' => $week['id']]);
        } else {
            [$start, $end] = currentWeekRange();
            $insert = $pdo->prepare('INSERT INTO weeks (child_id, budget, start_date, end_date) VALUES (:child, :budget, :start, :end)');
            $insert->execute([
                ':child' => $childId,
                ':budget' => $amount,
                ':start' => $start->format('Y-m-d'),
                ':end' => $end->format('Y-m-d'),
            ]);
        }
        $pdo->commit();
        $_SESSION['flash_success'][] = "Dépôt de {$amount} € pour l’enfant #{$childId}.";
        redirect('/?view=parent');
    }

    if ($action === 'add_expense') {
        $role = $_SESSION['role'] ?? '';
        if (!in_array($role, ['child', 'parent'], true)) {
            throw new RuntimeException('Connexion requise.');
        }
        $childId = (int) ($_SESSION['child_id'] ?? 0);
        if ($role === 'parent') {
            $childId = (int) ($_POST['child_id'] ?? $childId);
        }
        if ($childId <= 0) {
            throw new RuntimeException('Aucun enfant en session.');
        }

        $category = trim($_POST['category'] ?? '');
        $amount = (float) ($_POST['amount'] ?? 0);
        $dateInput = $_POST['date'] ?? '';

        if ($category === '' || $amount <= 0) {
            throw new InvalidArgumentException('Catégorie et montant positif requis.');
        }

        $date = DateTimeImmutable::createFromFormat('Y-m-d', $dateInput) ?: new DateTimeImmutable('today');

        $container = new Container();
        $weekRepo = $container->weekRepository();
        $expenseRepo = $container->expenseRepository();
        $useCase = new AddExpenseToWeek($weekRepo, $expenseRepo);

        $weekId = ensureWeekForDate($container, $pdo, $childId, $date);
        $useCase->execute($childId, $weekId, $category, $amount, $date);

        $_SESSION['flash_success'][] = "Dépense ajoutée : {$category} - {$amount} €.";
        redirect('/?view=child');
    }

    if ($action === 'add_child') {
        if (($_SESSION['role'] ?? '') !== 'parent') {
            throw new RuntimeException('Accès parent requis pour ajouter un enfant.');
        }
        $parentId = (int) ($_SESSION['parent_id'] ?? 0);
        $childName = trim($_POST['child_name'] ?? '');
        $childEmail = trim($_POST['child_email'] ?? '');
        $childPassword = (string) ($_POST['child_password'] ?? '');
        $childBudget = (float) ($_POST['child_budget'] ?? 0);

        $_SESSION['old_child'] = $_POST;

        if ($parentId <= 0 || $childName === '' || $childEmail === '' || $childPassword === '' || $childBudget <= 0) {
            throw new InvalidArgumentException('Tous les champs enfant sont obligatoires et le budget doit être positif.');
        }

        $pdo->beginTransaction();
        $parentExists = $pdo->prepare('SELECT id FROM users WHERE id = :id AND role = \'PARENT\'');
        $parentExists->execute([':id' => $parentId]);
        if (!$parentExists->fetch()) {
            throw new RuntimeException('Parent introuvable.');
        }

        $childStmt = $pdo->prepare('INSERT INTO users (role, email, name, password_hash, parent_id) VALUES (\'CHILD\', :email, :name, :hash, :parent_id)');
        $childStmt->execute([
            ':email' => $childEmail,
            ':name' => $childName,
            ':hash' => password_hash($childPassword, PASSWORD_BCRYPT),
            ':parent_id' => $parentId,
        ]);
        $childId = (int) $pdo->lastInsertId();

        [$start, $end] = currentWeekRange();
        $weekStmt = $pdo->prepare('INSERT INTO weeks (child_id, budget, start_date, end_date) VALUES (:child, :budget, :start, :end)');
        $weekStmt->execute([
            ':child' => $childId,
            ':budget' => $childBudget,
            ':start' => $start->format('Y-m-d'),
            ':end' => $end->format('Y-m-d'),
        ]);

        $pdo->commit();
        unset($_SESSION['old_child']);
        $_SESSION['flash_success'][] = "Enfant \"{$childName}\" ajouté avec un budget de {$childBudget} €.";
        redirect('/?view=parent');
    }

    throw new RuntimeException('Action inconnue.');
} catch (Throwable $e) {
    $_SESSION['flash_error'][] = $e->getMessage();
    redirect($_SERVER['HTTP_REFERER'] ?? '/?view=login');
}
