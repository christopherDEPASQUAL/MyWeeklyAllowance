<?php
declare(strict_types=1);



$title = 'Tableau de bord';
$subtitle = 'Vue parent';


?>
<section class="section">
    <div class="stack stack--sm">
        <div class="section__title">Bonjour, <?= htmlspecialchars($userName ?? 'Parent', ENT_QUOTES, 'UTF-8') ?></div>
        <div class="muted">Suivez vos enfants et leurs budgets.</div>
    </div>

    <div class="stack stack--sm">
        <div class="section__subtitle">Ajouter un enfant</div>
        <div class="card card--panel">
            <form class="form form--inline" method="post" action="/actions.php">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="action" value="add_child">
                <input type="hidden" name="role" value="parent">
                <input type="hidden" name="parent_id" value="<?= htmlspecialchars((string) $parentId, ENT_QUOTES, 'UTF-8') ?>">
                <label class="form__field">
                    <span>Nom</span>
                    <input type="text" name="child_name" placeholder="Prénom de l’enfant" required value="<?= htmlspecialchars($_SESSION['old_child']['child_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </label>
                <label class="form__field">
                    <span>Email</span>
                    <input type="email" name="child_email" placeholder="enfant@example.com" required value="<?= htmlspecialchars($_SESSION['old_child']['child_email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </label>
                <label class="form__field">
                    <span>Mot de passe</span>
                    <input type="password" name="child_password" placeholder="••••••••" required>
                </label>
                <label class="form__field form__field--inline">
                    <span class="muted">Budget (€)</span>
                    <input type="number" name="child_budget" min="1" step="1" placeholder="30" required value="<?= htmlspecialchars($_SESSION['old_child']['child_budget'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </label>
                <button type="submit" class="btn btn--primary">Créer l’enfant</button>
            </form>
            <p class="muted">L’enfant est rattaché automatiquement à votre compte parent.</p>
        </div>

        <div class="section__subtitle">Enfants</div>
        <div class="grid grid--cards">
            <?php foreach ($children ?? [] as $child): ?>
                <?php
                $content = function () use ($child, $parentId) {
                    ?>
                    <div class="summary summary--compact">
                        <div class="summary__item">
                            <span class="muted">Budget</span>
                            <span class="mono"><?= number_format($child['budget'], 2) ?> €</span>
                        </div>
                        <div class="summary__item">
                            <span class="muted">Solde</span>
                            <span class="mono"><?= number_format($child['balance'], 2) ?> €</span>
                        </div>
                    </div>
                    <div class="card__actions">
                        <a class="link" href="<?= htmlspecialchars($child['link'], ENT_QUOTES, 'UTF-8') ?>">Voir le détail</a>
                        <form class="form form--inline" method="post" action="/actions.php">
                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="action" value="deposit">
                            <input type="hidden" name="role" value="parent">
                            <input type="hidden" name="parent_id" value="<?= htmlspecialchars((string) $parentId, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="child_id" value="<?= htmlspecialchars((string) $child['id'], ENT_QUOTES, 'UTF-8') ?>">
                            <label class="form__field form__field--inline">
                                <span class="muted">€</span>
                                <input type="number" name="amount" min="1" step="1" placeholder="10" required>
                            </label>
                            <button type="submit" class="btn btn--ghost">Envoyer</button>
                        </form>
                    </div>
                    <?php
                };
                $meta = 'Enfant';
                $title = $child['name'];
                include __DIR__ . '/../components/card.php';
            ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>
