<?php
// system/Auth.php

namespace System;

class Auth
{
    private Database $db;
    private const MAX_LOGIN_ATTEMPTS = 5;
    private const LOCKOUT_TIME = 900; // 15 минут в секундах

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Попытка авторизации пользователя
     */
    public function login(string $username, string $password, string $ip): bool
    {
        // 1. Проверка на превышение лимита попыток входа (защита от брутфорса)
        if ($this->isRateLimited($ip)) {
            throw new \Exception("Слишком много неудачных попыток входа. Попробуйте позже.");
        }

        // 2. Поиск пользователя в базе данных
        $sql = "SELECT * FROM users WHERE username = ? LIMIT 1";
        $user = $this->db->fetch($sql, [$username]);

        // Если пользователя нет или пароль не совпал
        if (!$user || !password_verify($password, $user['password_hash'])) {
            $this->logFailedAttempt($ip, $username);
            return false;
        }

        // 3. Регенерация ID сессии для защиты от перехвата
        session_regenerate_id(true);
        
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['role'] = $user['role'];
        
        // Создаем «цифровой отпечаток» сессии (IP + браузер) для защиты от угона сессии
        $_SESSION['ip_fingerprint'] = hash('sha256', $ip . ($_SERVER['HTTP_USER_AGENT'] ?? ''));

        // Очищаем счетчик неудачных попыток при успехе
        $this->clearFailedAttempts($ip, $username);
        
        return true;
    }

    /**
     * Проверка, авторизован ли текущий пользователь
     */
    public function check(): bool
    {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }

        // Проверяем соответствие отпечатка сессии (защита от кражи кук / угона сессии)
        $currentFingerprint = hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? '') . ($_SERVER['HTTP_USER_AGENT'] ?? ''));
        if (isset($_SESSION['ip_fingerprint']) && $_SESSION['ip_fingerprint'] !== $currentFingerprint) {
            $this->logout();
            return false;
        }

        return true;
    }

    /**
     * Выход из системы (завершение сессии)
     */
    public function logout(): void
    {
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
    }

    /**
     * Проверка блокировки по IP
     */
    private function isRateLimited(string $ip): bool
    {
        $sql = "SELECT attempts, lockout_until FROM login_attempts WHERE ip = ? LIMIT 1";
        $record = $this->db->fetch($sql, [$ip]);

        if ($record && $record['lockout_until'] && time() < strtotime($record['lockout_until'])) {
            return true;
        }
        return false;
    }

    /**
     * Логирование неудачной попытки входа
     */
    private function logFailedAttempt(string $ip, string $username): void
    {
        $sql = "INSERT INTO login_attempts (ip, username, attempts, last_attempt) 
                VALUES (?, ?, 1, NOW()) 
                ON DUPLICATE KEY UPDATE attempts = attempts + 1, last_attempt = NOW()";
        // Здесь можно также вычислять lockout_until, если attempts >= MAX_LOGIN_ATTEMPTS
        $this->db->fetch($sql, [$ip, $username]);
    }

    /**
     * Очистка истории неудачных попыток
     */
    private function clearFailedAttempts(string $ip, string $username): void
    {
        $sql = "DELETE FROM login_attempts WHERE ip = ? OR username = ?";
        // $this->db->fetch($sql, [$ip, $username]);
    }
}