<?php
// admin/actions/dashboard.php
require_once dirname(__DIR__, 2) . '/system/Database.php';


if (!isset($db)) {
    $config = require ROOT_PATH . '/config.php';

$dbConfig = [
    'host' => $config['db']['host'],
    'dbname' => $config['db']['dbname'],
    'username' => $config['db']['username'],
    'password' => $config['db']['password'],
];

$db = new \System\Database($dbConfig);
}

// Собираем базовую статистику для дашборда
$modulesCount = 0;
try {
    $res = $db->fetch("SELECT COUNT(*) as cnt FROM installed_modules");
    $modulesCount = $res['cnt'] ?? 0;
} catch (\Throwable $e) {
    // Таблица installed_modules может еще не существовать
}

$tablesCount = 0;
try {
    $tablesQuery = $db->fetchAll("SHOW TABLES");
    $tablesCount = count($tablesQuery);
} catch (\Throwable $e) {
}
?>

<h2>Панель управления</h2>
<p>Добро пожаловать в административную панель вашей CMS.</p>

<div style="display: flex; gap: 20px; margin-top: 20px;">
    <!-- Карточка 1: Модули -->
    <div style="background: #f1f3f5; padding: 20px; border-radius: 6px; flex: 1; border-left: 4px solid #007bff;">
        <h3 style="margin-top: 0; color: #495057;">Установлено модулей</h3>
        <p style="font-size: 28px; font-weight: bold; margin: 10px 0 0 0; color: #007bff;"><?= $modulesCount ?></p>
        <p style="margin: 10px 0 0 0;"><a href="index.php?action=modules" style="color: #007bff; text-decoration: none; font-size: 14px;">Управление модулями →</a></p>
    </div>

    <!-- Карточка 2: Таблицы БД -->
    <div style="background: #f1f3f5; padding: 20px; border-radius: 6px; flex: 1; border-left: 4px solid #28a745;">
        <h3 style="margin-top: 0; color: #495057;">Таблиц в базе данных</h3>
        <p style="font-size: 28px; font-weight: bold; margin: 10px 0 0 0; color: #28a745;"><?= $tablesCount ?></p>
        <p style="margin: 10px 0 0 0;"><a href="index.php?action=crud&table=installed_modules" style="color: #28a745; text-decoration: none; font-size: 14px;">Открыть таблицы (CRUD) →</a></p>
    </div>
</div>

<div style="margin-top: 30px; background: #fff; padding: 20px; border: 1px solid #dee2e6; border-radius: 6px;">
    <h3 style="margin-top: 0;">Быстрый старт</h3>
    <p>Используйте левое меню для навигации. Вы можете устанавливать новые модули через папку <code>/modules/</code> и управлять их данными напрямую через универсальный инструмент CRUD.</p>
</div>