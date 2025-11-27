<?php

declare(strict_types=1);

$title = 'Connexion';
$subtitle = 'Accède à ton espace';
?>
<section class="section section--centered">
    <div class="stack stack--xs muted">Choisis ton profil</div>
    <div class="toggle">
        <button class="toggle__btn is-active" data-toggle-target="form-child">Je suis enfant</button>
        <button class="toggle__btn" data-toggle-target="form-parent">Je suis parent</button>
    </div>

    <div class="card card--panel login-form" id="form-child">
        <h2 class="section__title">Connexion enfant</h2>
        <p class="muted">Entre ton email et ton mot de passe pour voir ton argent de poche.</p>
        <form class="form" method="post" action="/actions.php">
            <input type="hidden" name="role" value="child">
            <input type="hidden" name="action" value="login">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
            <label class="form__field">
                <span>Email</span>
                <input type="email" name="email" placeholder="enfant@example.com" required value="<?= htmlspecialchars($_SESSION['old']['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </label>
            <label class="form__field">
                <span>Mot de passe</span>
                <input type="password" name="password" placeholder="••••••••" required>
            </label>
            <button type="submit" class="btn btn--primary">Se connecter</button>
        </form>
        <p class="muted">Pas de compte ? Demande à ton parent de t’inviter.</p>
    </div>

    <div class="card card--panel login-form is-hidden" id="form-parent">
        <h2 class="section__title">Connexion parent</h2>
        <p class="muted">Gérez les allocations et suivez les dépenses de vos enfants.</p>
        <form class="form" method="post" action="/actions.php">
            <input type="hidden" name="role" value="parent">
            <input type="hidden" name="action" value="login">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
            <label class="form__field">
                <span>Email</span>
                <input type="email" name="email" placeholder="parent@example.com" required value="<?= htmlspecialchars($_SESSION['old']['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </label>
            <label class="form__field">
                <span>Mot de passe</span>
                <input type="password" name="password" placeholder="••••••••" required>
            </label>
            <button type="submit" class="btn btn--primary">Se connecter</button>
        </form>
        <p class="muted">Pas encore inscrit ? <a class="link" href="/?view=register">Créer un compte parent</a></p>
    </div>
</section>
