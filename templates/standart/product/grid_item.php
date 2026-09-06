<?php
/**
 * Элемент товара для сетки (grid)
 * Ожидает объект/массив $product
 */
$product = $product ?? ['id' => 1, 'title' => 'Товар по умолчанию', 'price' => 1990, 'image' => '/assets/images/placeholders/no-photo.svg'];
?>
<div class="product-card grid-view-item">
    <div class="product-image-wrap">
        <a href="/product/view.php?id=<?= $product['id'] ?>">
            <img src="<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['title']) ?>" loading="lazy">
        </a>
        <button class="wishlist-btn" aria-label="В избранное">❤️</button>
    </div>
    <div class="product-content">
        <h3 class="product-title">
            <a href="/product/view.php?id=<?= $product['id'] ?>"><?= htmlspecialchars($product['title']) ?></a>
        </h3>
        <div class="product-price-row">
            <span class="product-price"><?= number_format($product['price'], 0, '', ' ') ?> ₽</span>
        </div>
        <button class="btn btn-primary btn-sm add-to-cart" data-id="<?= $product['id'] ?>">В корзину</button>
    </div>
</div>