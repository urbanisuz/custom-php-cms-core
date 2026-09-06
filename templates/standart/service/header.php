<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Интернет-магазин' ?></title>
    <!-- Подключаем шрифт и стили строго из папки assets -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/templates/standart/css/style.css">
    <?php if (isset($extraCss)): ?>
        <link rel="stylesheet" href="css/<?= $extraCss ?>">
    <?php endif; ?>
</head>
<body>

<header>
    <div class="container header-inner">
        <a href="/" class="logo">StoreBrand</a>
        <nav>
            <ul class="nav-links">
                <li><a href="/catalog.php">Каталог</a></li>
                <li><a href="/page.php?slug=delivery">Доставка</a></li>
                <li><a href="/page.php?slug=payment">Оплата</a></li>
                <li><a href="/page.php?slug=about">О компании</a></li>
            </ul>
        </nav>
        <div class="header-actions">
            <a href="/cart.php" class="btn btn-outline">Корзина (0)</a>
        </div>
    </div>
</header>
<main class="container" style="padding-top: 40px; padding-bottom: 40px;">