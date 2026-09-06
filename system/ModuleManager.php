<?php
// system/ModuleManager.php

namespace System;

class ModuleManager
{
    private static ?ModuleManager $instance = null;
    private array $loadedModules = [];

    private function __construct()
    {
        $this->scanModules();
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Сканирует директорию /modules/ в поисках валидных модулей с manifest.json
     */
    private function scanModules(): void
    {
        $modulesDir = ROOT_PATH . '/modules';
        if (!is_dir($modulesDir)) {
            return;
        }

        $dirs = scandir($modulesDir);
        foreach ($dirs as $dir) {
            if ($dir === '.' || $dir === '..') {
                continue;
            }

            $manifestPath = $modulesDir . '/' . $dir . '/manifest.json';
            if (file_exists($manifestPath)) {
                $manifest = json_decode(file_get_contents($manifestPath), true);
                
                // Используем имя папки ($dir) как системный и надежный ID модуля
                if ($manifest && isset($manifest['name'])) {
                    $this->loadedModules[$dir] = [
                        'name' => $manifest['name'],
                        'version' => $manifest['version'] ?? '1.0.0',
                        'path' => $modulesDir . '/' . $dir,
                        'class' => $manifest['main_class'] ?? 'Module',
                    ];
                }
            }
        }
    }

    /**
     * Получить список всех найденных модулей
     */
    public function getModules(): array
    {
        return $this->loadedModules;
    }

    /**
     * Безопасная установка модуля с использованием транзакций БД
     */
    public function install(string $moduleId, Database $db): bool
    {
        if (!isset($this->loadedModules[$moduleId])) {
            throw new \Exception("Модуль не найден.");
        }

        $modInfo = $this->loadedModules[$moduleId];
        $installerFile = $modInfo['path'] . '/Installer.php';

        if (!file_exists($installerFile)) {
            throw new \Exception("У установщика модуля отсутствует файл Installer.php");
        }

        require_once $installerFile;
        $installerClass = "Modules\\{$moduleId}\\Installer";

        if (!class_exists($installerClass) || !method_exists($installerClass, 'install')) {
            throw new \Exception("Некорректный класс установщика модуля.");
        }

        // Получаем PDO для работы с транзакциями
        $pdo = $db->getPdo();
        $pdo->beginTransaction();

        try {
            $installer = new $installerClass($db);
            $installer->install();

            // Фиксируем информацию об установке в системной таблице
            $db->query(
                "INSERT INTO installed_modules (module_id, version, installed_at) 
                 VALUES (?, ?, NOW()) 
                 ON DUPLICATE KEY UPDATE version = ?",
                [$moduleId, $modInfo['version'], $modInfo['version']]
            );
            
            $pdo->commit();
            return true;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            // Пробрасываем безопасное сообщение об ошибке (без утечки структуры БД наружу)
            throw new \Exception("Ошибка установки модуля: " . $e->getMessage());
        }
    }

    /**
     * Вызов метода модуля (Capability) из шаблона или ядра
     */
    public function call(string $moduleName, string $action, array $params, Context $ctx): mixed
    {
        if (!isset($this->loadedModules[$moduleName])) {
            return "<!-- Module [{$moduleName}] not found -->";
        }

        $modInfo = $this->loadedModules[$moduleName];
        $classFile = $modInfo['path'] . '/' . str_replace('\\', '/', $modInfo['class']) . '.php';

        if (file_exists($classFile)) {
            require_once $classFile;
            
            $fullClassName = "Modules\\{$moduleName}\\" . $modInfo['class'];
            
            if (class_exists($fullClassName)) {
                $moduleInstance = new $fullClassName($ctx);
                if (method_exists($moduleInstance, $action)) {
                    return $moduleInstance->$action($params);
                }
            }
        }

        return "<!-- Action [{$action}] in module [{$moduleName}] not found -->";
    }
	/**
     * Собирает пункты меню админки от всех установленных модулей
     */
    public function getAdminMenus(Database $db, Context $ctx): array
    {
        $menus = [];
        
        // Получаем только установленные модули из базы
        $installed = $db->fetchAll("SELECT module_id FROM installed_modules");
        $installedIds = array_column($installed, 'module_id');

        foreach ($installedIds as $moduleId) {
            if (!isset($this->loadedModules[$moduleId])) {
                continue;
            }

            $modInfo = $this->loadedModules[$moduleId];
            $classFile = $modInfo['path'] . '/' . str_replace('\\', '/', $modInfo['class']) . '.php';

            if (file_exists($classFile)) {
                require_once $classFile;
                $fullClassName = "Modules\\{$moduleId}\\" . $modInfo['class'];
                
                if (class_exists($fullClassName)) {
                    $instance = new $fullClassName($ctx);
                    // Если у модуля есть метод getAdminMenu — вызываем его
                    if (method_exists($instance, 'getAdminMenu')) {
                        $moduleMenus = $instance->getAdminMenu();
                        if (is_array($moduleMenus)) {
                            foreach ($moduleMenus as $item) {
                                $menus[] = $item;
                            }
                        }
                    }
                }
            }
        }

        return $menus;
    }
}