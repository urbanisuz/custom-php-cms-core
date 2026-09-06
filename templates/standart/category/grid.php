<?php
/**
 * Сетка товаров
 * Ожидает массив товаров $products
 */
$products = $products ?? [];
?>
<div class="products-grid">
    <?php if (!empty($products)): ?>
        <?php foreach ($products as $product): ?>
            <?php include __DIR__ . '/grid_item.php'; ?>
        <?php endforeach; ?>
    <?php else: ?>
        <p class="no-products">В данной категории товаров пока нет.</p>
    <?php endif; ?>
</div>