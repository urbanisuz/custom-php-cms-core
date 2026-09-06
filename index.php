<?php
// index.php (в корне сайта)

declare(strict_types=1);

define('ROOT_PATH', __DIR__);
define('SYSTEM_PATH', ROOT_PATH . '/system');

$configFile = ROOT_PATH . '/config.php';

// Если файла конфига нет или он пустой — отправляем на установщик
if (!file_exists($configFile) || filesize($configFile) === 0) {
    header('Location: /install/index.php');
    exit;
}
// Подключаем только инициализатор один раз
require_once SYSTEM_PATH . '/init.php';

use System\Kernel;

try {
    $configPath = ROOT_PATH . '/config.php';
    if (!file_exists($configPath)) {
        throw new \Exception("Configuration file config.php not found.");
    }
    
    $config = require $configPath;
    
    $kernel = new Kernel($config);
    $kernel->handle();

} catch (\Throwable $e) {
    http_response_code(500);
    echo "Critical Error: " . htmlspecialchars($e->getMessage());
}