<?php
// system/Database.php

namespace System;

class Database
{
    private ?\PDO $pdo = null;
    private ?string $connectionError = null;

    public function __construct(array $config)
    {
        $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8mb4";
        
        try {
            $this->pdo = new \PDO($dsn, $config['username'], $config['password'], [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            ]);
        } catch (\PDOException $e) {
            // Сохраняем ошибку, чтобы её можно было вывести при диагностике
            $this->connectionError = $e->getMessage();
        }
    }

    /**
     * Проверяет, установлено ли соединение с базой данных
     */
    public function isConnected(): bool
    {
        if ($this->pdo === null) {
            return false;
        }

        try {
            // Делаем легкий пинг-запрос к базе
            $this->pdo->query("SELECT 1");
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Возвращает текст ошибки подключения, если оно не удалось
     */
    public function getConnectionError(): ?string
    {
        return $this->connectionError;
    }

    public function query(string $sql, array $params = []): \PDOStatement
    {
        if (!$this->isConnected()) {
            throw new \RuntimeException("Нет подключения к базе данных. Ошибка: " . $this->connectionError);
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function fetch(string $sql, array $params = []): ?array
    {
        return $this->query($sql, $params)->fetch() ?: null;
    }

    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAll();
    }
	/**
     * Возвращает внутренний объект PDO (нужно для внешних установщиков и библиотек)
     */
    public function getPdo(): ?\PDO
    {
        return $this->pdo;
    }
}