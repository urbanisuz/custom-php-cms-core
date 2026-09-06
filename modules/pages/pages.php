<?php
// modules/pages/Pages.php

namespace Modules\pages;

use System\Context;

class Pages
{
    private Context $ctx;

    public function __construct(Context $ctx)
    {
        $this->ctx = $ctx;
    }

    /**
     * Экшн (capability) для вывода контента страницы
     */
    public function show(array $params): string
    {
        $slug = $params['slug'] ?? 'index';
        $db = $this->ctx->getDb();

        // Запрос к базе данных за контентом страницы с учетом текущего языка
        $sql = "SELECT content FROM page_contents WHERE slug = ? AND language_id = ?";
        // (Для примера упрощенно)
        
        return "<div>Контент страницы для слога: " . htmlspecialchars($slug) . "</div>";
    }
}