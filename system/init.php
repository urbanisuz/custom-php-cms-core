<?php
// system/init.php

declare(strict_types=1);

// Регистрируем автозагрузчик классов
spl_autoload_register(function ($class) {
    // 1. Проверяем системные классы (System\)
    $prefixSystem = 'System\\';
    if (strncmp($prefixSystem, $class, strlen($prefixSystem)) === 0) {
        $relative_class = substr($class, strlen($prefixSystem));
        $file = __DIR__ . '/' . str_replace('\\', '/', $relative_class) . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }

    // 2. Проверяем классы тем (Templates\) — например, Templates\default\Installer
    $prefixTemplate = 'Templates\\';
    if (strncmp($prefixTemplate, $class, strlen($prefixTemplate)) === 0) {
        $relative_class = substr($class, strlen($prefixTemplate));
        // Превращаем Templates\default\Installer в templates/default/Installer.php
        $file = dirname(__DIR__) . '/templates/' . str_replace('\\', '/', $relative_class) . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});