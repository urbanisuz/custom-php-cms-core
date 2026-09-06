<?php
$slug = $_GET['slug'] ?? 'about';

// Простейший массив данных под сервисные страницы (позже можно брать из БД)
$pages = [
    'about' => ['title' => 'О компании', 'content' => 'Здесь находится подробная информация о нашей компании, миссии и преимуществах.'],
    'delivery' => ['title' => 'Условия доставки', 'content' => 'Мы осуществляем доставку по всей стране курьерскими службами и до пунктов выдачи.'],
    'payment' => ['title' => 'Способы оплаты', 'content' => 'Вы можете оплатить заказ банковской картой онлайн, при получении или через систему быстрых платежей (СБП).'],
    'returns' => ['title' => 'Возврат товара', 'content' => 'Вы можете вернуть товар надлежащего качества в течение 14 дней после покупки.'],
];

$currentPage = $pages[$slug] ?? $pages['about'];

$pageTitle = $currentPage['title'] . " — StoreBrand";
$extraCss = "page.css";
include 'templates/standart/service/header.php';
?>

<div style="background: var(--bg-card); padding: 40px; border-radius: var(--radius); border: 1px solid var(--border-color); max-width: 800px; margin: 0 auto;">
    <h1 style="margin-bottom: 20px; font-size: 32px;"><?= $currentPage['title'] ?></h1>
    <div style="font-size: 16px; line-height: 1.8; color: var(--text-main);">
        <?= $currentPage['content'] ?>
    </div>
</div>

<?php include 'templates/standart/service/footer.php'; ?>