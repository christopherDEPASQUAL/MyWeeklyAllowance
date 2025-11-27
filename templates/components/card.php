<?php

declare(strict_types=1);

/**
 * Variables attendues :
 * - $title (string)
 * - $content (callable|array|string) contenu ou texte
 * - $meta (string|null) petit texte en haut à droite
 * - $class (string|null) classes supplémentaires
 */

$title = $title ?? '';
$meta = $meta ?? null;
$class = $class ?? '';
?>
<section class="card <?= htmlspecialchars($class, ENT_QUOTES, 'UTF-8') ?>">
    <div class="card__head">
        <h3 class="card__title"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h3>
        <?php if ($meta): ?>
            <span class="card__meta"><?= htmlspecialchars($meta, ENT_QUOTES, 'UTF-8') ?></span>
        <?php endif; ?>
    </div>
    <div class="card__body">
        <?php
        if (is_callable($content ?? null)) {
            ($content)();
        } elseif (is_array($content ?? null)) {
            foreach ($content as $line) {
                echo '<div class="card__line">' . htmlspecialchars((string) $line, ENT_QUOTES, 'UTF-8') . '</div>';
            }
        } elseif (isset($content)) {
            echo '<p>' . htmlspecialchars((string) $content, ENT_QUOTES, 'UTF-8') . '</p>';
        }
        ?>
    </div>
</section>
