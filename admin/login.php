<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once dirname(__DIR__) . '/system/Database.php';

$config = require __DIR__ . '/../config.php';

$dbConfig = [
    'host' => $config['db']['host'],
    'dbname' => $config['db']['dbname'],
    'username' => $config['db']['username'],
    'password' => $config['db']['password'],
];

$db = new \System\Database($dbConfig);

if (!$db->isConnected()) {
    die("Нет соединения с БД: " . $db->getConnectionError());
}

$error = '';
$setupMessage = '';

try {
    // 1. Проверяем, есть ли вообще пользователи в таблице
    $userCount = $db->fetch("SELECT COUNT(*) as cnt FROM users");
    $count = (int)($userCount['cnt'] ?? 0);

    // Если пользователей нет — создаем дефолтного админа
    if ($count === 0) {
        $defaultUser = 'admin';
        $defaultEmail = 'admin@local.host'; 
        $defaultPasswordPlain = 'admin123';
        $hashedPassword = password_hash($defaultPasswordPlain, PASSWORD_DEFAULT);

        $db->query(
            "INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, 'admin')",
            [$defaultUser, $defaultEmail, $hashedPassword]
        );

        $setupMessage = "Внимание! Создан временный администратор по умолчанию.<br>" .
                        "Логин: <b>admin</b> | Пароль: <b>admin123</b><br>" .
                        "Пожалуйста, зайдите под ним и сразу смените пароль!";
    }
} catch (\Throwable $e) {
    die("Ошибка при работе с таблицей users: " . $e->getMessage());
}

// 2. Обработка отправки формы входа (когда пользователь нажал кнопку «Войти»)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username !== '' && $password !== '') {
        $user = $db->fetch("SELECT * FROM users WHERE username = ?", [$username]);

        // Проверяем существование пользователя и правильность хеша пароля
        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            // Успешный вход — перенаправляем в админку на index.php
            header('Location: index.php');
            exit;
        } else {
            $error = 'Неверный логин или пароль.';
        }
    } else {
        $error = 'Заполните все поля.';
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Вход в панель управления</title>
    <style>
        body { font-family: sans-serif; background: #f4f7f6; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .login-box { background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); width: 320px; }
        h2 { margin-top: 0; text-align: center; color: #333; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; color: #666; font-size: 14px; }
        input[type="text"], input[type="password"] { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button { width: 100%; padding: 10px; background: #007bff; border: none; border-radius: 4px; color: #fff; font-size: 16px; cursor: pointer; }
        button:hover { background: #0056b3; }
        .alert-success { background: #e2f0d9; color: #385723; padding: 10px; border-radius: 4px; margin-bottom: 15px; font-size: 13px; line-height: 1.4; }
        .alert-error { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin-bottom: 15px; font-size: 13px; }
    </style>
</head>
<body>

<div class="login-box">
    <h2>Вход в CMS</h2>

    <?php if (!empty($setupMessage)): ?>
        <div class="alert-success"><?= $setupMessage ?></div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="form-group">
            <label for="username">Логин</label>
            <input type="text" id="username" name="username" required value="admin">
        </div>
        <div class="form-group">
            <label for="password">Пароль</label>
            <input type="password" id="password" name="password" required value="admin123">
        </div>
        <button type="submit">Войти</button>
    </form>
</div>

</body>
</html>