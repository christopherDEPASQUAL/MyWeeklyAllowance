<?php

declare(strict_types=1);

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

function csrfToken(): string
{
    if (!isset($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['_csrf'];
}

function fetchLatestWeekSummary(PDO $pdo, int $childId): array
{
    $week = $pdo->prepare('SELECT id, budget, start_date, end_date FROM weeks WHERE child_id = :child_id ORDER BY start_date DESC LIMIT 1');
    $week->execute([':child_id' => $childId]);
    $row = $week->fetch();
    if (!$row) {
        return ['week_id' => null, 'budget' => 0.0, 'expenses' => 0.0, 'balance' => 0.0, 'start_date' => null, 'end_date' => null];
    }

    $totalStmt = $pdo->prepare('SELECT COALESCE(SUM(amount),0) as total FROM expenses WHERE week_id = :week_id');
    $totalStmt->execute([':week_id' => $row['id']]);
    $total = (float) ($totalStmt->fetch()['total'] ?? 0);

    return [
        'week_id' => (int) $row['id'],
        'budget' => (float) $row['budget'],
        'expenses' => $total,
        'balance' => (float) $row['budget'] - $total,
        'start_date' => $row['start_date'],
        'end_date' => $row['end_date'],
    ];
}

function fetchExpenses(PDO $pdo, int $weekId, int $limit = 5): array
{
    $stmt = $pdo->prepare('SELECT category, amount, DATE_FORMAT(date, "%d/%m") as date FROM expenses WHERE week_id = :week_id ORDER BY date DESC, id DESC LIMIT :limit');
    $stmt->bindValue(':week_id', $weekId, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll() ?: [];
}

function fetchChildrenForParent(PDO $pdo, int $parentId): array
{
    $stmt = $pdo->prepare('SELECT id, name, email FROM users WHERE parent_id = :parent');
    $stmt->execute([':parent' => $parentId]);
    $children = [];
    foreach ($stmt->fetchAll() as $child) {
        $summary = fetchLatestWeekSummary($pdo, (int) $child['id']);
        $children[] = [
            'id' => (int) $child['id'],
            'name' => $child['name'],
            'budget' => $summary['budget'],
            'balance' => $summary['balance'],
            'link' => '/?view=child&child_id=' . (int) $child['id'],
        ];  
    }
    return $children;
}

$view = $_GET['view'] ?? 'login';
$userRole = $_SESSION['role'] ?? 'guest';
$flash = [
    'success' => $_SESSION['flash_success'] ?? [],
    'error' => $_SESSION['flash_error'] ?? [],
];
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

// Guards
if ($view === 'parent' && ($userRole !== 'parent' || empty($_SESSION['parent_id']))) {
    header('Location: /?view=login');
    exit;
}
if ($view === 'child' && !in_array($userRole, ['child', 'parent'], true)) {
    header('Location: /?view=login');
    exit;
}

$pdo = db();
$weekSummary = ['budget' => 0, 'expenses' => 0, 'balance' => 0, 'week_id' => null];
$expenses = [];
$children = [];
$userName = $userRole === 'parent' ? 'Parent' : 'Enfant';
$parentId = $_SESSION['parent_id'] ?? null;
$childId = $_SESSION['child_id'] ?? null;

try {
    if ($userRole === 'child') {
        if ($childId) {
            $userNameStmt = $pdo->prepare('SELECT name FROM users WHERE id = :id AND role = \'CHILD\'');
            $userNameStmt->execute([':id' => $childId]);
            $row = $userNameStmt->fetch();
            if ($row) {
                $userName = $row['name'];
                $weekSummary = fetchLatestWeekSummary($pdo, $childId);
                if ($weekSummary['week_id']) {
                    $expenses = fetchExpenses($pdo, $weekSummary['week_id'], 5);
                }
            }
        }
    } elseif ($userRole === 'parent') {
        if ($parentId) {
            $userNameStmt = $pdo->prepare('SELECT name FROM users WHERE id = :id AND role = \'PARENT\'');
            $userNameStmt->execute([':id' => $parentId]);
            $row = $userNameStmt->fetch();
            if ($row) {
                $userName = $row['name'];
                $children = fetchChildrenForParent($pdo, $parentId);
            }
        }
    }
} catch (Throwable $e) {
    $flash['error'][] = 'Impossible de charger les données : ' . $e->getMessage();
}

require __DIR__ . '/../templates/layout.php';
