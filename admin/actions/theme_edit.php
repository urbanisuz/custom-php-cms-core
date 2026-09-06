<?php
// admin/actions/theme_edit.php

if (!defined('ROOT_PATH')) {
    exit;
}

$themeId = trim($_GET['theme'] ?? '');
if (!$themeId) {
    echo "<div style='color: red;'>Не указана тема для редактирования.</div>";
    return;
}

$themeManager = \System\ThemeManager::getInstance();
$themeInfo = $themeManager->getTheme($themeId);

if (!$themeInfo) {
    echo "<div style='color: red;'>Тема не найдена.</div>";
    return;
}

// Инициализируем подключение к БД
$dbConfig = require ROOT_PATH . '/config.php';
$db = new \System\Database($dbConfig['db'] ?? []);

$message = '';
$error = '';

// Обработка сохранения настроек
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_theme_settings'])) {
    $settings = $_POST['setting'] ?? [];

    try {
        foreach ($settings as $key => $value) {
            $value = trim($value);
            // Обновляем значение по ключу и имени темы
            $db->query(
                "UPDATE theme_settings SET value = ? WHERE theme = ? AND `key` = ?",
                [$value, $themeId, $key]
            );
        }
        $message = "Настройки темы успешно сохранены!";
    } catch (\Throwable $e) {
        $error = "Ошибка при сохранении настроек: " . $e->getMessage();
    }
}

// Получаем настройки для конкретной темы, исключая системный ключ active_theme
$settingsRows = $db->fetchAll(
    "SELECT * FROM theme_settings WHERE theme = ? AND `key` != 'active_theme'",
    [$themeId]
);
?>

<h2>Редактирование темы: <?= htmlspecialchars($themeInfo['name']) ?> (<code><?= htmlspecialchars($themeId) ?></code>)</h2>

<p><a href="index.php?action=themes">&larr; Назад к списку тем</a></p>

<?php if ($message): ?>
    <div style="padding: 10px; background: #d4edda; color: #155724; border-radius: 4px; margin-bottom: 15px;">
        <?= htmlspecialchars($message) ?>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div style="padding: 10px; background: #f8d7da; color: #721c24; border-radius: 4px; margin-bottom: 15px;">
        <?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<form method="POST" style="background: white; padding: 20px; border: 1px solid #dee2e6; border-radius: 4px; max-width: 600px;">
    <h3>Параметры темы</h3>
    
    <?php if (empty($settingsRows)): ?>
        <p style="color: #6c757d;">Для этой темы в базе данных пока нет индивидуальных настроек. Вы можете добавить их через установщик темы или структуру манифеста.</p>
    <?php else: ?>
        <?php foreach ($settingsRows as $row): ?>
            <div style="margin-bottom: 15px;">
                <label style="display: block; font-weight: bold; margin-bottom: 5px;">
                    <?= htmlspecialchars($row['name']) ?> <span style="font-weight: normal; color: #6c757d; font-size: 12px;">(<?= htmlspecialchars($row['key']) ?>)</span>:
                </label>
                <input type="text" name="setting[<?= htmlspecialchars($row['key']) ?>]" value="<?= htmlspecialchars($row['value']) ?>" style="width: 100%; padding: 8px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 4px;">
            </div>
        <?php endforeach; ?>

        <button type="submit" name="save_theme_settings" style="padding: 10px 20px; background: #228be6; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px;">
            Сохранить изменения
        </button>
    <?php endif; ?>
</form>