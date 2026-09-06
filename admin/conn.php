<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once dirname(__DIR__) . '/system/Database.php';

$config = require __DIR__ . '/../config.php'; // подключаем общий файл

// Передаем именно массив настроек БД и учитываем ключи ('user' вместо 'username')
$dbConfig = [
    'host' => $config['db']['host'],
    'dbname' => $config['db']['dbname'],
    'username' => $config['db']['user'], // обрати внимание на ключ 'user'
    'password' => $config['db']['password'],
];

$db = new \System\Database($dbConfig);

if (!$db->isConnected()) {
    die("Нет соединения с БД: " . $db->getConnectionError());
}

// ДЕЛАЕМ ОТЛАДОЧНЫЙ ВЫВОД:
try {
    $userCount = $db->fetch("SELECT COUNT(*) as cnt FROM users");
    
    echo "<pre>";
    echo "Результат запроса COUNT(*):\n";
    var_dump($userCount);
    echo "</pre>";
    
    $count = (int)($userCount['cnt'] ?? 0);
    echo "Преобразованное число строк ($count): " . gettype($count) . "\n";
    
    if ($count === 0) {
        echo "Условие \$count === 0 сработало! Пробуем сделать INSERT...<br>";
        
        $defaultUser = 'admin';
        $defaultPasswordPlain = 'admin123';
        $hashedPassword = password_hash($defaultPasswordPlain, PASSWORD_DEFAULT);

        $db->query(
            "INSERT INTO users (username, password, role) VALUES (?, ?, 'admin')",
            [$defaultUser, $hashedPassword]
        );
        
        echo "INSERT выполнен успешно!";
    } else {
        echo "Условие НЕ сработало, потому что count не равен нулю.";
    }
} catch (\Throwable $e) {
    echo "ОШИБКА: " . $e->getMessage();
}
exit; // Останавливаем выполнение, чтобы посмотреть только на этот отладочный текст