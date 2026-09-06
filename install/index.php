<?php
// install/index.php
declare(strict_types=1);

define('ROOT_PATH', dirname(__DIR__));

// Подключаем автозагрузчик системы в самом начале, чтобы классы (System\Database, System\ThemeManager) были доступны
require_once ROOT_PATH . '/system/init.php';

$configFile = ROOT_PATH . '/config.php';

// Если конфиг уже есть и не пустой — блокируем повторный запуск установщика
if (file_exists($configFile) && filesize($configFile) > 0) {
    die("Система уже установлена. Для переустановки удалите файл config.php.");
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dbHost     = trim($_POST['db_host'] ?? 'localhost');
    $dbName     = trim($_POST['db_name'] ?? '');
    $dbUser     = trim($_POST['db_user'] ?? '');
    $dbPass     = $_POST['db_pass'] ?? '';
    
    $adminUser  = trim($_POST['admin_user'] ?? 'admin');
    $adminEmail = trim($_POST['admin_email'] ?? 'admin@local.host');
    $adminPass  = $_POST['admin_pass'] ?? '';

    if (!$dbName || !$dbUser || !$adminPass) {
        $error = "Заполните все обязательные поля для базы данных и администратора.";
    } else {
        try {
            // 1. Создаем временное подключение без указания dbname, чтобы создать саму базу данных
            $pdoMaster = new \PDO("mysql:host={$dbHost};charset=utf8mb4", $dbUser, $dbPass, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
            ]);
            $pdoMaster->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            unset($pdoMaster);

            // 2. Инициализируем твой класс Database для работы с созданной базой
            $dbInstance = new \System\Database([
                'host' => $dbHost,
                'dbname' => $dbName,
                'username' => $dbUser,
                'password' => $dbPass,
                'charset' => 'utf8mb4'
            ]);

            // 3. Читаем schema.sql и выполняем запросы поочередно через твой метод query()
            $schemaFile = __DIR__ . '/schema.sql';
            if (!file_exists($schemaFile)) {
                throw new \Exception("Файл схемы schema.sql не найден в папке install.");
            }
            
            $sqlScript = file_get_contents($schemaFile);
            $queries = array_filter(array_map('trim', explode(';', $sqlScript)));
            foreach ($queries as $query) {
                if (!empty($query)) {
                    $dbInstance->query($query);
                }
            }

            // 4. Безопасно создаем или обновляем учетную запись администратора
            $passwordHash = password_hash($adminPass, PASSWORD_DEFAULT);
            $dbInstance->query(
                "INSERT INTO users (username, email, password_hash, role, status) 
                 VALUES (?, ?, ?, 'admin', 1)
                 ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash), email = VALUES(email)",
                [$adminUser, $adminEmail, $passwordHash]
            );

            // 5. Генерируем config.php в корне проекта
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $baseUrl = $protocol . $host;

            $configContent = "<?php\n// Сгенерировано установщиком " . date('Y-m-d H:i:s') . "\n\n";
            $configContent .= "return [\n";
            $configContent .= "    'db' => [\n";
            $configContent .= "        'host' => '{$dbHost}',\n";
            $configContent .= "        'dbname' => '{$dbName}',\n";
            $configContent .= "        'username' => '{$dbUser}',\n";
            $configContent .= "        'password' => '{$dbPass}',\n";
            $configContent .= "        'charset' => 'utf8mb4',\n";
            $configContent .= "    ],\n";
            $configContent .= "    'app' => [\n";
            $configContent .= "        'base_url' => '{$baseUrl}',\n";
            $configContent .= "        'default_language' => 'ru',\n";
            $configContent .= "        'supported_languages' => ['ru'],\n";
            $configContent .= "        'language_id' => 1,\n";
            $configContent .= "        'store_id' => 0,\n";
            $configContent .= "        'theme' => 'standart',\n";
            $configContent .= "    ]\n";
            $configContent .= "];\n";

            file_put_contents($configFile, $configContent);

            // 6. Запускаем установку темы через ThemeManager (тема standart)
            $themeManager = \System\ThemeManager::getInstance();
            $themeManager->install('standart', $dbInstance);

            // Фиксируем активную тему в настройках
            $dbInstance->query(
                "INSERT INTO theme_settings (context, context_id, theme, `group`, `key`, name, value) 
                 VALUES ('global', 0, 'standart', 'general', 'active_theme', 'Активная тема', 'standart')
                 ON DUPLICATE KEY UPDATE value = 'standart'"
            );

            // Успех! Перенаправляем на страницу входа в админку
            header('Location: /admin/login.php');
            exit;

        } catch (\Throwable $e) {
            $error = "Ошибка установки: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Установка CMS</title>
    <style>
        body { font-family: sans-serif; background: #f0f2f5; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .install-box { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); width: 400px; }
        h2 { margin-top: 0; color: #333; }
        label { display: block; margin-top: 15px; font-weight: bold; font-size: 14px; color: #555; }
        input { width: 100%; padding: 8px; margin-top: 5px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 4px; }
        button { width: 100%; background: #228be6; color: white; border: none; padding: 10px; margin-top: 20px; border-radius: 4px; font-size: 16px; cursor: pointer; }
        button:hover { background: #1c7ed6; }
        .error { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin-bottom: 15px; font-size: 14px; }
    </style>
</head>
<body>
    <div class="install-box">
        <h2>Установка CMS</h2>
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="POST">
            <h3>База данных</h3>
            <label>Хост БД</label>
            <input type="text" name="db_host" value="localhost" required>
            
            <label>Имя базы данных</label>
            <input type="text" name="db_name" value="cms_db" required>
            
            <label>Пользователь БД</label>
            <input type="text" name="db_user" value="root" required>
            
            <label>Пароль БД</label>
            <input type="password" name="db_pass">

            <h3>Администратор</h3>
            <label>Логин администратора</label>
            <input type="text" name="admin_user" value="admin" required>
            
            <label>Email администратора</label>
            <input type="email" name="admin_email" value="admin@local.host" required>
            
            <label>Пароль администратора</label>
            <input type="password" name="admin_pass" required>

            <button type="submit">Установить систему</button>
        </form>
    </div>
</body>
</html>