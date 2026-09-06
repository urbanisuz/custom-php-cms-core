<?php
// admin/index.php
session_start();

// Защита: если не авторизован — редирект на вход
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Определяем базовые константы
define('ROOT_PATH', dirname(__DIR__));
define('SYSTEM_PATH', ROOT_PATH . '/system');

// Подключаем автозагрузчик один раз для всей админки
require_once SYSTEM_PATH . '/init.php';

// Определяем экшен (по умолчанию dashboard)
$action = $_GET['action'] ?? 'dashboard';
// Санитизация имени экшена для предотвращения Local File Inclusion (LFI)
$action = preg_replace('/[^a-zA-Z0-9_-]/', '', $action);

$actionFile = __DIR__ . '/actions/' . $action . '.php';

// Буферизация вывода, чтобы подключить шаблон админки
ob_start();
if (file_exists($actionFile)) {
    include $actionFile;
} else {
    echo "<h2>404</h2><p>Действие (страница) не найдено.</p>";
}
$content = ob_get_clean();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Админ-панель</title>
    <style>
        body { font-family: sans-serif; margin: 0; display: flex; background: #f8f9fa; }
        .sidebar { width: 250px; background: #343a40; color: white; height: 100vh; position: fixed; padding: 20px; box-sizing: border-box; }
        .sidebar h2 { font-size: 18px; margin-top: 0; color: #adb5bd; border-bottom: 1px solid #495057; padding-bottom: 10px; }
        .sidebar ul { list-style: none; padding: 0; margin: 0; }
        .sidebar ul li { margin-bottom: 10px; }
        .sidebar ul li a { color: #cfd4da; text-decoration: none; display: block; padding: 8px 12px; border-radius: 4px; }
        .sidebar ul li a:hover, .sidebar ul li a.active { background: #495057; color: white; }
        .content { margin-left: 250px; padding: 30px; width: calc(100% - 250px); box-sizing: border-box; }
        .card { background: white; padding: 20px; border-radius: 6px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2>CMS Админка</h2>
        <ul>
            <li><a href="index.php?action=dashboard" class="<?= $action === 'dashboard' ? 'active' : '' ?>">Главная</a></li>
            <li><a href="index.php?action=modules" class="<?= $action === 'modules' ? 'active' : '' ?>">Модули</a></li>
			<li><a href="index.php?action=layouts" class="<?= $action === 'layouts' ? 'active' : '' ?>">Макеты</a></li>
			<li><a href="index.php?action=themes" class="<?= $action === 'themes' ? 'active' : '' ?>">Темы</a></li>
            <li><a href="index.php?action=settings" class="<?= $action === 'settings' ? 'active' : '' ?>">Настройки</a></li>
            <li style="margin-top: 30px;"><a href="logout.php" style="color: #ff6b6b;">Выход</a></li>
        </ul>
    </div>
    <div class="content">
        <div class="card">
            <?= $content ?>
        </div>
    </div>
</body>
</html>