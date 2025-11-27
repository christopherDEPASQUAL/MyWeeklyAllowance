<?php

declare(strict_types=1);

$title = $title ?? 'MyWeeklyAllowance';
$subtitle = $subtitle ?? '';
$role = $currentRole ?? 'guest';
$role = $currentRole ?? 'guest';
?>
<header class="header">
    <div class="header__brand">
        <div class="logo">MWA</div>
        <div>
            <div class="header__title"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></div>
            <?php if ($subtitle): ?>
                <div class="header__subtitle"><?= htmlspecialchars($subtitle, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
        </div>
    </div>
    <button class="header__burger" aria-label="Menu" aria-expanded="false" aria-controls="header-nav">
        <span></span><span></span><span></span>
    </button>
    <nav class="header__nav" id="header-nav">
        <a class="nav-link" href="/?view=login">Login</a>
        <?php if ($role !== 'parent'): ?>
            <a class="nav-link" href="/?view=register">Inscription parent</a>
        <?php endif; ?>
        <a class="nav-link" href="/?view=child">Espace enfant</a>
        <?php if ($role === 'parent'): ?>
            <a class="nav-link" href="/?view=parent">Espace parent</a>
        <?php endif; ?>
    </nav>
    <div class="header__actions">
        <span class="pill"><?= htmlspecialchars(ucfirst($role), ENT_QUOTES, 'UTF-8') ?></span>
        <?php if ($role !== 'guest'): ?>
            <form action="/actions.php" method="post">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="action" value="logout">
                <button type="submit" class="btn btn--ghost">Déconnexion</button>
            </form>
        <?php endif; ?>
    </div>
</header>
