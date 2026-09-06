<?php
$pageTitle = "Карточка товара — StoreBrand";
$extraCss = "catalog.css";
include 'includes/header.php';

// Здесь в будущем будет запрос в БД по $_GET['id']
$productId = (int)($_GET['id'] ?? 1);
?>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 40px; background: var(--bg-card); padding: 30px; border-radius: var(--radius); border: 1px solid var(--border-color);">
    <div style="background: #f1f5f9; height: 400px; border-radius: var(--radius); display: flex; align-items: center; justify-content: center; color: var(--text-muted);">
        Большое фото товара #<?= $productId ?>
    </div>
    <div>
        <h1 style="margin-bottom: 15px; font-size: 28px;">Премиальный товар №<?= $productId ?></h1>
        <div style="font-size: 24px; font-weight: 700; color: var(--primary); margin-bottom: 20px;">4 990 ₽</div>
        <p style="color: var(--text-muted); margin-bottom: 25px;">Описание товара. Высокое качество, современные материалы, долгий срок службы. Идеально подходит для повседневного использования.</p>
        
        <button class="btn btn-primary" style="width: 100%; padding: 14px; font-size: 16px;">Добавить в корзину</button>
    </div>
</div>

<?php include 'includes/footer.php'; ?>