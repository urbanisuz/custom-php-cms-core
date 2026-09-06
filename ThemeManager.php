<?php
// system/ThemeManager.php

namespace System;

class ThemeManager
{
    private static ?ThemeManager $instance = null;
    private array $loadedThemes = [];
    private ?string $activeTheme = 'standart';

    private function __construct()
    {
        $this->scanThemes();
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Сканирует директорию /templates/ в поисках тем с theme.json
     */
    private function scanThemes(): void
    {
        $templatesDir = ROOT_PATH . '/templates';
        if (!is_dir($templatesDir)) {
            return;
        }

        $dirs = scandir($templatesDir);
        foreach ($dirs as $dir) {
            if ($dir === '.' || $dir === '..') {
                continue;
            }

            $themePath = $templatesDir . '/' . $dir;
            if (!is_dir($themePath)) {
                continue;
            }

            $manifestPath = $themePath . '/theme.json';
            if (file_exists($manifestPath)) {
                $manifest = json_decode(file_get_contents($manifestPath), true);
                
                if ($manifest && isset($manifest['name'])) {
                    $this->loadedThemes[$dir] = [
                        'name' => $manifest['name'],
                        'version' => $manifest['version'] ?? '1.0.0',
                        'author' => $manifest['author'] ?? 'Unknown',
                        'description' => $manifest['description'] ?? '',
                        'path' => $themePath,
                        'templates' => $manifest['templates'] ?? [],
                    ];
                }
            }
        }
    }

    public function getThemes(): array
    {
        return $this->loadedThemes;
    }

    /**
     * Получить информацию о конкретной теме (включая её структуру шаблонов)
     */
    public function getTheme(string $themeId): ?array
    {
        return $this->loadedThemes[$themeId] ?? null;
    }

    /**
     * Безопасная установка темы с вызовом её Installer.php и транзакциями БД
     */
    public function install(string $themeId, Database $db): bool
    {
        if (!isset($this->loadedThemes[$themeId])) {
            throw new \Exception("Тема не найдена.");
        }

        $themeInfo = $this->loadedThemes[$themeId];
        $installerFile = $themeInfo['path'] . '/Installer.php';

        $pdo = $db->getPdo();
        $pdo->beginTransaction();

        try {
            if (file_exists($installerFile)) {
                require_once $installerFile;
                $installerClass = "Templates\\{$themeId}\\Installer";

                if (class_exists($installerClass) && method_exists($installerClass, 'install')) {
                    $installer = new $installerClass($db);
                    $installer->install();
                }
            }
            
            $pdo->commit();
            return true;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw new \Exception("Ошибка установки темы: " . $e->getMessage());
        }
    }
}