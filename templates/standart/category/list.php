<?php
$products = $products ?? [];
?>
<div class="products-list">
    <?php if (!empty($products)): ?>
        <?php foreach ($products as $product): ?>
            <?php include __DIR__ . '/list_item.php'; ?>
        <?php endforeach; ?>
    <?php else: ?>
        <p class="no-products">В данной категории товаров пока нет.</p>
    <?php endif; ?>
</div>