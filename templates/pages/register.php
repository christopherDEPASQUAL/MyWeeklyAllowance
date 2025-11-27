<?php

declare(strict_types=1);

$title = 'Création de compte parent';
$subtitle = 'Un parent crée le compte et ajoute son enfant';
?>
<section class="section section--centered">
    <div class="stack stack--sm">
        <div class="section__title"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></div>
        <div class="muted"><?= htmlspecialchars($subtitle, ENT_QUOTES, 'UTF-8') ?></div>
    </div>

    <div class="card card--panel">
        <h2 class="section__subtitle">Informations du parent</h2>
        <form class="form" method="post" action="/actions.php">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="action" value="register">
            <label class="form__field">
                <span>Email du parent</span>
                <input type="email" name="parent_email" placeholder="parent@example.com" required value="<?= htmlspecialchars($_SESSION['old']['parent_email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </label>
            <label class="form__field">
                <span>Nom</span>
                <input type="text" name="parent_name" placeholder="Alice Parent" required value="<?= htmlspecialchars($_SESSION['old']['parent_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </label>
            <label class="form__field">
                <span>Mot de passe</span>
                <input type="password" name="parent_password" placeholder="••••••••" required>
            </label>

            <div class="divider"></div>

            <h3 class="section__subtitle">Ajouter un enfant</h3>
            <label class="form__field">
                <span>Nom de l’enfant</span>
                <input type="text" name="child_name" placeholder="Léa" required value="<?= htmlspecialchars($_SESSION['old']['child_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </label>
            <label class="form__field">
                <span>Email de l’enfant</span>
                <input type="email" name="child_email" placeholder="enfant@example.com" required value="<?= htmlspecialchars($_SESSION['old']['child_email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </label>
            <label class="form__field">
                <span>Mot de passe de l’enfant</span>
                <input type="password" name="child_password" placeholder="••••••••" required>
            </label>
            <label class="form__field">
                <span>Budget hebdomadaire (€)</span>
                <input type="number" name="child_budget" min="0" step="0.5" placeholder="50" required value="<?= htmlspecialchars($_SESSION['old']['child_budget'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </label>

            <button type="submit" class="btn btn--primary">Créer le compte parent + enfant</button>
        </form>
        <p class="muted">L’enfant ne peut pas créer de compte. Seul le parent peut inscrire l’enfant.</p>
    </div>
</section>
