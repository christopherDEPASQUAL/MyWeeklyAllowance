<?php

declare(strict_types=1);

$title = 'Tableau de bord';
$subtitle = 'Vue enfant';
?>
<section class="section">
    <div class="stack stack--sm">
        <div class="section__title">Bonjour, <?= htmlspecialchars($userName ?? 'Enfant', ENT_QUOTES, 'UTF-8') ?></div>
        <div class="muted">Voici un aperçu de ta semaine.</div>
    </div>

    <div class="card card--panel">
        <h3 class="section__subtitle">Ajouter une dépense</h3>
        <p class="muted">Solde actuel : <?= number_format($weekSummary['balance'] ?? 0, 2) ?> €</p>
        <form class="form form--inline" method="post" action="/actions.php">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="action" value="add_expense">
            <label class="form__field">
                <span>Catégorie</span>
                <input type="text" name="category" placeholder="Snacks, transport..." required>
            </label>
            <label class="form__field form__field--inline">
                <span class="muted">€</span>
                <input type="number" name="amount" min="0.01" step="0.01" placeholder="5.00" required>
            </label>
            <label class="form__field">
                <span>Date</span>
                <input type="date" name="date" value="<?= htmlspecialchars((new DateTimeImmutable('today'))->format('Y-m-d'), ENT_QUOTES, 'UTF-8') ?>" required>
            </label>
            <button type="submit" class="btn btn--primary">Enregistrer</button>
        </form>
    </div>

    <div class="grid grid--cards">
        <?php
        $summaryContent = function () use ($weekSummary) {
            ?>
            <div class="summary">
                <div class="summary__item">
                    <span class="muted">Budget</span>
                    <span class="mono"><?= number_format($weekSummary['budget'] ?? 0, 2) ?> €</span>
                </div>
                <div class="summary__item">
                    <span class="muted">Dépenses</span>
                    <span class="mono"><?= number_format($weekSummary['expenses'] ?? 0, 2) ?> €</span>
                </div>
                <div class="summary__item">
                    <span class="muted">Solde</span>
                    <span class="mono"><?= number_format($weekSummary['balance'] ?? 0, 2) ?> €</span>
                </div>
            </div>
            <?php
        };
        $content = $summaryContent;
        include __DIR__ . '/../components/card.php';
        ?>
    </div>

    <div class="stack stack--sm">
        <div class="section__subtitle">Dernières dépenses</div>
        <div class="list">
            <?php foreach ($expenses ?? [] as $expense): ?>
                <div class="list__item card card--inline">
                    <div>
                        <div class="list__title"><?= htmlspecialchars($expense['category'], ENT_QUOTES, 'UTF-8') ?></div>
                        <div class="muted">le <?= htmlspecialchars($expense['date'], ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                    <div class="mono"><?= number_format((float) $expense['amount'], 2) ?> €</div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
