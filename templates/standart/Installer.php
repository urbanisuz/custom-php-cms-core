<?php
// templates/standart/Installer.php

namespace Templates\standart;

use System\Database;

class Installer 
{
    private Database $db;

    public function __construct(Database $db) 
    {
        $this->db = $db;
    }

    public function install(): void 
    {
        $themePath = __DIR__;
        $manifestPath = $themePath . '/theme.json';

        if (!file_exists($manifestPath)) {
            throw new \Exception("Файл манифеста theme.json не найден.");
        }

        $manifest = json_decode(file_get_contents($manifestPath), true);
        if (!$manifest) {
            throw new \Exception("Ошибка парсинга theme.json.");
        }

        $themeName = 'standart'; // Привели к актуальному названию темы

        // 1. Заливаем настройки темы в таблицу theme_settings
        if (!empty($manifest['settings'])) {
            foreach ($manifest['settings'] as $setting) {
                $exists = $this->db->fetch(
                    "SELECT setting_id FROM theme_settings WHERE theme = ? AND `group` = ? AND `key` = ?",
                    [$themeName, $setting['group'], $setting['key']]
                );

                if (!$exists) {
                    $this->db->query(
                        "INSERT INTO theme_settings (context, context_id, theme, `group`, `key`, name, value) 
                         VALUES ('global', 0, ?, ?, ?, ?, ?)",
                        [$themeName, $setting['group'], $setting['key'], $setting['name'], $setting['value']]
                    );
                }
            }
        }

        // 2. Регистрация дефолтных страниц и разделов в seo_urls
        if (!empty($manifest['pages'])) {
            foreach ($manifest['pages'] as $page) {
                $keyword = trim($page['keyword'] ?? '', '/');
                $query = $page['query'] ?? '';

                $existsUrl = $this->db->fetch(
                    "SELECT seo_url_id FROM seo_urls WHERE keyword = ?",
                    [$keyword]
                );

                if (!$existsUrl) {
                    $this->db->query(
                        "INSERT INTO seo_urls (store_id, language_id, query, keyword) 
                         VALUES (0, 1, ?, ?)",
                        [$query, $keyword]
                    );
                }
            }
        }

        // 3. Создание базовых макетов и привязка роутов (как в OpenCart)
        $this->installDefaultLayouts();

        // 4. Установка демо-данных
        $this->installDemoContent();
    }

    /**
     * Создание дефолтных макетов страниц и привязка роутов
     */
    private function installDefaultLayouts(): void
    {
        $defaultLayouts = [
            [
                'name' => 'Главная страница',
                'routes' => ['common/index', '']
            ],
            [
                'name' => 'Категория товаров',
                'routes' => ['product/category']
            ],
            [
                'name' => 'Карточка товара',
                'routes' => ['product/product']
            ],
            [
                'name' => 'Информационные страницы (Статьи)',
                'routes' => ['information/information', 'page/default']
            ],
            [
                'name' => 'Корзина',
                'routes' => ['checkout/cart']
            ],
            [
                'name' => 'Личный кабинет / Аккаунт',
                'routes' => ['account/account', 'account/login']
            ]
        ];

        foreach ($defaultLayouts as $layoutData) {
            // Проверяем, существует ли уже макет с таким именем
            $layout = $this->db->fetch("SELECT layout_id FROM layouts WHERE name = ?", [$layoutData['name']]);
            
            if (!$layout) {
                $this->db->query("INSERT INTO layouts (name) VALUES (?)", [$layoutData['name']]);
                $layoutId = $this->db->lastInsertId();
            } else {
                $layoutId = $layout['layout_id'];
            }

            // Привязываем роуты, если они еще не привязаны к этому макету
            foreach ($layoutData['routes'] as $route) {
                $routeExists = $this->db->fetch(
                    "SELECT layout_route_id FROM layout_route WHERE layout_id = ? AND route = ?",
                    [$layoutId, $route]
                );

                if (!$routeExists) {
                    $this->db->query(
                        "INSERT INTO layout_route (layout_id, store_id, route) VALUES (?, 0, ?)",
                        [$layoutId, $route]
                    );
                }
            }
        }
    }

    private function installDemoContent(): void
    {
        $demoUrls = [
            ['query' => 'product/product&product_id=1', 'keyword' => 'catalog/sample-product-1'],
            ['query' => 'product/product&product_id=2', 'keyword' => 'catalog/sample-product-2'],
        ];

        foreach ($demoUrls as $item) {
            $exists = $this->db->fetch("SELECT seo_url_id FROM seo_urls WHERE keyword = ?", [$item['keyword']]);
            if (!$exists) {
                $this->db->query(
                    "INSERT INTO seo_urls (store_id, language_id, query, keyword) VALUES (0, 1, ?, ?)",
                    [$item['query'], $item['keyword']]
                );
            }
        }
    }
}