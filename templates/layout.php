<?php

declare(strict_types=1);

$currentView = $view ?? 'login';
$currentRole = $userRole ?? 'guest';
$csrf = csrfToken();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MyWeeklyAllowance</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&family=IBM+Plex+Mono:wght@400&display=swap" rel="stylesheet">
</head>
<body class="page">
<div class="shell">
    <?php include __DIR__ . '/components/header.php'; ?>

    <main class="main">
        <?php if (!empty($flash['success'])): ?>
            <div class="alert alert--success" tabindex="-1">
                <strong>Succès</strong>
                <?php foreach ($flash['success'] as $msg): ?>
                    <div><?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($flash['error'])): ?>
            <div class="alert alert--error" tabindex="-1">
                <strong>Erreur</strong>
                <?php foreach ($flash['error'] as $msg): ?>
                    <div><?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <?php
        switch ($currentView) {
            case 'child':
                include __DIR__ . '/pages/child-dashboard.php';
                break;
            case 'parent':
                include __DIR__ . '/pages/parent-dashboard.php';
                break;
            case 'register':
                include __DIR__ . '/pages/register.php';
                break;
            default:
                include __DIR__ . '/pages/login.php';
                break;
        }
        ?>
    </main>
</div>

<script>
    // Toggle enfant / parent sur la page de login
    const toggleButtons = document.querySelectorAll('[data-toggle-target]');
    toggleButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            const target = btn.getAttribute('data-toggle-target');
            document.querySelectorAll('.login-form').forEach(form => {
                form.classList.toggle('is-hidden', form.id !== target);
            });
            toggleButtons.forEach(b => b.classList.toggle('is-active', b === btn));
        });
    });

    // Burger menu
    const burger = document.querySelector('.header__burger');
    const nav = document.querySelector('.header__nav');
    if (burger && nav) {
        burger.addEventListener('click', () => {
            nav.classList.toggle('is-open');
            burger.classList.toggle('is-active');
        });
    }
</script>
</body>
</html>
