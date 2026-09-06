<?php
// system/Router.php

namespace System;

class Router
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Превращает входящий ЧПУ (keyword) во внутренний запрос модуля (query)
     */
    public function resolve(string $keyword, $languageId = 1, $storeId = 0): ?array
    {
        $keyword = trim($keyword, '/');
        
        $languageId = (int)$languageId;
        $storeId = (int)$storeId;

        // 1. Пытаемся найти ЧПУ в базе данных
        if ($keyword !== '') {
            $sql = "SELECT `query` FROM seo_urls WHERE keyword = ? AND language_id = ? AND store_id = ? LIMIT 1";
            $result = $this->db->fetch($sql, [$keyword, $languageId, $storeId]);

            if ($result) {
                parse_str($result['query'], $queryParams);
                return [
                    'raw_query' => $result['query'],
                    'params'    => $queryParams
                ];
            }
        }

        // 2. Фолбэк (Fallback): Если в seo_urls ничего не найдено (или это главная),
        // разбираем URL напрямую или отдаем дефолтную страницу.
        
        // Если адрес пустой или равен index.php — открываем главную страницу
        if ($keyword === '' || $keyword === 'index.php') {
            return [
                'raw_query' => 'route=common/index',
                'params'    => ['route' => 'common/index']
            ];
        }

        // Если переданы прямые параметры через GET (например, ?route=catalog)
        if (isset($_GET['route'])) {
            $route = $_GET['route'];
            return [
                'raw_query' => 'route=' . $route,
                'params'    => $_GET
            ];
        }

        // 3. Если совсем ничего не подошло — тогда уже возвращаем null (404)
        return null;
    }

    /**
     * Обратное преобразование: по системному запросу получает ЧПУ
     */
    public function rewrite(string $query, $languageId = 1, $storeId = 0): ?string
    {
        $languageId = (int)$languageId;
        $storeId = (int)$storeId;

        $sql = "SELECT `keyword` FROM seo_urls WHERE query = ? AND language_id = ? AND store_id = ? LIMIT 1";
        $result = $this->db->fetch($sql, [$query, $languageId, $storeId]);

        // Если ЧПУ в базе нет, возвращаем стандартный системный вид ссылки
        return $result ? '/' . $result['keyword'] : '/index.php?route=' . ltrim($query, '/');
    }
}