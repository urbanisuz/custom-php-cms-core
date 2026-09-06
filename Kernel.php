<?php
// system/Kernel.php

namespace System;

class Kernel
{
    private array $config;
    private Database $db;
    private Context $ctx;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Главный метод обработки входящего запроса
     */
    public function handle(): void
    {
        // 1. Инициализация базы данных
        $dbConfig = $this->config['db'] ?? [];
        if (empty($dbConfig['dbname']) || empty($dbConfig['username'])) {
            throw new \RuntimeException("Database configuration is missing or incomplete in config.php");
        }
        $this->db = new Database($dbConfig);

        // 2. Создание глобального контекста приложения ($ctx)
        $this->ctx = new Context($this->db, $this->config['app'] ?? []);

        // 2.1. Инициализация темы и загрузка её настроек в контекст
        $activeThemeName = $this->config['app']['theme'] ?? 'default';
        $themeManager = ThemeManager::getInstance();
        $themeInfo = $themeManager->getTheme($activeThemeName);

        if (!$themeInfo) {
            throw new \RuntimeException("Active theme '{$activeThemeName}' not found or missing theme.json");
        }

        // Передаем информацию о теме в контекст
        $this->ctx->theme = $themeInfo;

        // Загружаем настройки темы из таблицы theme_settings
        $settingsRows = $this->db->fetchAll(
            "SELECT `group`, `key`, value FROM theme_settings WHERE theme = ?", 
            [$activeThemeName]
        );
        $themeSettings = [];
        foreach ($settingsRows as $row) {
            $themeSettings[$row['group']][$row['key']] = $row['value'];
        }
        $this->ctx->themeSettings = $themeSettings;

        // 3. Получение текущего URI и метода запроса
        $uri = $_SERVER['REQUEST_URI'] ?? '/';

        if (($pos = strpos($uri, '?')) !== false) {
            $uri = substr($uri, 0, $pos);
        }

        // 4. Определение языка
        $uri = $this->resolveLanguage($uri);

        // 5. Запуск роутера
        $router = new Router($this->db);
        $languageId = $this->config['app']['language_id'] ?? 1; 
        $storeId = $this->config['app']['store_id'] ?? 0;
        
        $route = $router->resolve($uri, (int)$languageId, (int)$storeId);

        if (!$route) {
            $this->renderNotFound();
            return;
        }

        // 6. Определяем имя шаблона на основе параметров или сырого запроса
        $templateFileRelative = 'index.php'; // по умолчанию главная
        
        if (!empty($route['params']['route'])) {
            $routePath = trim($route['params']['route'], '/');
            $parts = explode('/', $routePath);
            $lastPart = end($parts);
            
            // Проверяем по структуре шаблонов из темы, есть ли соответствие (например, category или page)
            if ($lastPart === 'home'|| $lastPart === 'index' || $routePath === '') {
                $templateFileRelative = $themeInfo['templates']['index'] ?? 'index.php';
            } elseif ($lastPart === 'category') {
                $templateFileRelative = $themeInfo['templates']['category']['main'] ?? 'category/category.php';
            } else {
                $templateFileRelative = $themeInfo['templates']['pages']['default'] ?? 'pages/default.php';
            }
			var_dump($lastPart);
        } elseif (!empty($route['raw_query'])) {
            parse_str($route['raw_query'], $queryParts);
            if (isset($queryParts['route'])) {
                $parts = explode('/', trim($queryParts['route'], '/'));
                if (in_array('category', $parts, true)) {
                    $templateFileRelative = $themeInfo['templates']['category']['main'] ?? 'category/category.php';
                }
            }
        }

        // 7. Рендеринг шаблона из активной темы
        $this->renderTemplate($templateFileRelative, $route['params'] ?? []);
    }

    /**
     * Определение языка интерфейса по URI и установка его в контекст
     */
    private function resolveLanguage(string $uri): string
    {
        $defaultLang = $this->config['app']['default_language'] ?? 'ru';
        
        $segments = explode('/', trim($uri, '/'));
        $supportedLangs = $this->config['app']['supported_languages'] ?? ['ru', 'en'];

        if (!empty($segments[0]) && in_array($segments[0], $supportedLangs, true)) {
            $lang = $segments[0];
            $this->ctx->setLanguage($lang);
            
            array_shift($segments);
            return '/' . implode('/', $segments);
        }

        $this->ctx->setLanguage($defaultLang);
        return $uri;
    }

    /**
     * Подключение и вывод файла шаблона темы
     */
    private function renderTemplate(string $templateFileRelative, array $params): void
    {
        $activeThemeName = $this->ctx->theme['name'] ?? 'default'; // или по ключу папки темы
        // Лучше брать путь прямо из данных темы в контексте:
        $activeThemePath = $this->ctx->theme['path'] ?? (ROOT_PATH . '/templates/default');
        
        $templateFile = $activeThemePath . '/' . $templateFileRelative;

        if (!file_exists($templateFile)) {
            throw new \RuntimeException("Template not found: {$templateFileRelative} (Path: {$templateFile})");
        }

        // Делаем объект $ctx и параметры доступными внутри файла шаблона
        $ctx = $this->ctx;
        
        // Буферизация вывода шаблона
        require $templateFile;
    }

    /**
     * Обработка ошибки 404
     */
    private function renderNotFound(): void
    {
        http_response_code(404);
        echo "<h1>404 Not Found</h1><p>The requested page does not exist.</p>";
    }
}