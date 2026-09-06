<?php
$pageTitle = "Главная страница — StoreBrand";
$extraCss = "home.css"; // Если нужны уникальные стили для главной
include 'service/header.php';
?>

<div class="hero-section" style="background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%); color: white; padding: 60px 40px; border-radius: var(--radius); margin-bottom: 40px; display: flex; align-items: center; justify-content: space-between;">
    <div>
        <h1 style="font-size: 38px; margin-bottom: 15px;">Новая коллекция товаров</h1>
        <p style="font-size: 18px; margin-bottom: 25px; opacity: 0.9;">Скидки до 30% на популярные позиции каталога.</p>
        <a href="/catalog.php" class="btn" style="background: white; color: var(--primary);">Смотреть каталог</a>
    </div>
</div>

<section>
    <h2 style="margin-bottom: 25px;">Популярные товары</h2>
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 20px;">
        <!-- Пример карточки товара -->
        <?php for ($i = 1; $i <= 4; $i++): ?>
            <div style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius); padding: 20px; display: flex; flex-direction: column;">
                <div style="height: 180px; background: #f1f5f9; border-radius: 8px; margin-bottom: 15px; display: flex; align-items: center; justify-content: center; color: var(--text-muted);">Фото товара</div>
                <h3 style="font-size: 16px; margin-bottom: 10px;"><a href="/product.php?id=<?= $i ?>">Товар №<?= $i ?></a></h3>
                <div style="font-size: 18px; font-weight: 700; color: var(--primary); margin-bottom: 15px;">1 990 ₽</div>
                <a href="/product.php?id=<?= $i ?>" class="btn btn-primary" style="margin-top: auto;">Подробнее</a>
            </div>
        <?php endfor; ?>
    </div>
</section>

<?php include 'service/footer.php'; ?>