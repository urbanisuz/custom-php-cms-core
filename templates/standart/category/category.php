<?php
// 1. Подключаем инициализацию, сессии, подключение к БД (если нужно)
// require_once dirname(__DIR__) . '/system/init.php';

$pageTitle = "Каталог товаров — StoreBrand";
$extraCss = "catalog.css"; // Специфичные стили для каталога

// Пример данных хлебных крошек
$breadcrumbs = [
    ['title' => 'Главная', 'url' => '/'],
    ['title' => 'Каталог', 'url' => '']
];

// Определяем выбранный вид (grid или list), по умолчанию — grid
$view = $_GET['view'] ?? 'grid';
if (!in_array($view, ['grid', 'list'])) {
    $view = 'grid';
}

// Пример массива модулей для левой колонки (фильтры, категории)
$leftModules = [
    [
        'name' => 'categories',
        'title' => 'Категории',
        'file' => __DIR__ . '/templates/default/modules/categories_filter.php' // или вывод через html
    ],
    [
        'name' => 'price-filter',
        'title' => 'Цена',
        'file' => __DIR__ . '/templates/default/modules/price_filter.php'
    ]
];

// Пример товаров (в будущем здесь будет запрос через $db)
$products = [
    ['id' => 1, 'title' => 'Смартфон X', 'price' => 45990, 'description' => 'Мощный современный смартфон с отличной камерой.', 'image' => '/assets/images/placeholders/no-photo.svg'],
    ['id' => 2, 'title' => 'Ноутбук Pro', 'price' => 89990, 'description' => 'Производительный ноутбук для работы и тяжелых задач.', 'image' => '/assets/images/placeholders/no-photo.svg'],
    ['id' => 3, 'title' => 'Беспроводные наушники', 'price' => 7990, 'description' => 'Чистый звук и активное шумоподавление.', 'image' => '/assets/images/placeholders/no-photo.svg'],
];

include 'templates/default/includes/header.php';
?>

<!-- Вывод хлебных крошек -->
<?php include 'templates/default/service/breadcrumbs.php'; ?>

<div class="catalog-layout" style="display: grid; grid-template-columns: 260px 1fr; gap: 30px; margin-top: 20px;">
    
    <!-- Левая колонка с модулями (фильтры и т.д.) -->
    <?php include 'templates/default/service/left_column.php'; ?>

    <!-- Основная часть каталога -->
    <main class="catalog-content">
        <div class="catalog-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h1 style="font-size: 24px;">Каталог товаров</h1>
            
            <!-- Переключатель видов (Сетка / Список) -->
            <div class="view-switcher" style="display: flex; gap: 8px;">
                <a href="?view=grid<?= isset($_GET['cat']) ? '&cat=' . htmlspecialchars($_GET['cat']) : '' ?>" 
                   class="btn btn-sm <?= $view === 'grid' ? 'btn-primary' : 'btn-outline' ?>" title="Сетка">
                   ⊞ Сетка
                </a>
                <a href="?view=list<?= isset($_GET['cat']) ? '&cat=' . htmlspecialchars($_GET['cat']) : '' ?>" 
                   class="btn btn-sm <?= $view === 'list' ? 'btn-primary' : 'btn-outline' ?>" title="Список">
                   ☰ Список
                </a>
            </div>
        </div>

        <!-- Динамический вывод товаров в зависимости от выбранного вида -->
        <?php 
        if ($view === 'list') {
            include 'templates/default/category/list.php';
        } else {
            include 'templates/default/category/grid.php';
        }
        ?>
    </main>

</div>

<?php include 'templates/default/includes/footer.php'; ?>