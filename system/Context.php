<?php
// system/Context.php

namespace System;

class Context
{
    private Database $db;
    private array $appConfig;
    private string $language = 'ru';

    public function __construct(Database $db, array $appConfig)
    {
        $this->db = $db;
        $this->appConfig = $appConfig;
    }

    public function getDb(): Database
    {
        return $this->db;
    }

    public function setLanguage(string $lang): void
    {
        $this->language = $lang;
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    /**
     * Безопасный вызов возможностей (Capabilities) модулей из шаблона
     * Пример в шаблоне: <?= $ctx->module('catalog', 'showProduct', ['id' => 15]) ?>
     */
    public function module(string $moduleName, string $action, array $params = []): mixed
    {
        return ModuleManager::getInstance()->call($moduleName, $action, $params, $this);
    }
}