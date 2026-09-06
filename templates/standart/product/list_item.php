<?php
$product = $product ?? ['id' => 1, 'title' => 'Товар по умолчанию', 'price' => 1990, 'description' => 'Краткое описание товара...', 'image' => '/assets/images/placeholders/no-photo.svg'];
?>
<div class="product-card list-view-item">
    <div class="product-image-wrap">
        <a href="/product/view.php?id=<?= $product['id'] ?>">
            <img src="<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['title']) ?>" loading="lazy">
        </a>
    </div>
    <div class="product-content">
        <h3 class="product-title">
            <a href="/product/view.php?id=<?= $product['id'] ?>"><?= htmlspecialchars($product['title']) ?></a>
        </h3>
        <p class="product-desc"><?= htmlspecialchars($product['description'] ?? '') ?></p>
    </div>
    <div class="product-actions-col">
        <span class="product-price"><?= number_format($product['price'], 0, '', ' ') ?> ₽</span>
        <button class="btn btn-primary btn-sm add-to-cart" data-id="<?= $product['id'] ?>">В корзину</button>
    </div>
</div>