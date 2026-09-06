<?php
/**
 * Хлебные крошки
 * Ожидает массив $breadcrumbs = [['title' => 'Главная', 'url' => '/'], ...]
 */
if (!empty($breadcrumbs)): 
?>
<nav aria-label="breadcrumb" class="breadcrumbs-wrap">
    <ol class="breadcrumbs">
        <?php foreach ($breadcrumbs as $index => $item): ?>
            <li class="breadcrumbs-item <?= ($index === count($breadcrumbs) - 1) ? 'active' : '' ?>">
                <?php if (!empty($item['url']) && $index !== count($breadcrumbs) - 1): ?>
                    <a href="<?= htmlspecialchars($item['url']) ?>"><?= htmlspecialchars($item['title']) ?></a>
                <?php else: ?>
                    <span><?= htmlspecialchars($item['title']) ?></span>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ol>
</nav>
<?php endif; ?>