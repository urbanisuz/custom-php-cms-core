<?php
// admin/actions/themes.php

if (!defined('ROOT_PATH')) {
    exit;
}

$themeManager = \System\ThemeManager::getInstance();
$message = '';
$error = '';

// Инициализируем соединение с БД для установщика тем
$dbConfig = require ROOT_PATH . '/config.php';
$db = new \System\Database($dbConfig['db'] ?? []);

// Обработка установки/активации темы
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['install_theme'])) {
    $themeId = trim($_POST['theme_id'] ?? '');
    
    try {
        $themeManager->install($themeId, $db);
        
        // Сохраняем активную тему в общие настройки (в таблицу theme_settings)
        $db->query(
            "INSERT INTO theme_settings (context, context_id, theme, `group`, `key`, name, value) 
             VALUES ('global', 0, ?, 'general', 'active_theme', 'Активная тема', ?)
             ON DUPLICATE KEY UPDATE value = ?",
            [$themeId, $themeId, $themeId]
        );
        
        $message = "Тема «{$themeId}» успешно установлена и активирована!";
    } catch (\Throwable $e) {
        $error = "Ошибка при установке темы: " . $e->getMessage();
    }
}

$themes = $themeManager->getThemes();

// Получаем текущую активную тему из базы
$activeThemeSetting = $db->fetch(
    "SELECT value FROM theme_settings WHERE `group` = 'general' AND `key` = 'active_theme' LIMIT 1"
);
$activeThemeId = $activeThemeSetting['value'] ?? 'standart';
?>

<h2>Управление темами оформления</h2>

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

<table border="1" cellpadding="10" cellspacing="0" style="width: 100%; border-collapse: collapse; border-color: #dee2e6;">
    <thead>
        <tr style="background: #f1f3f5; text-align: left;">
            <th>Название</th>
            <th>Версия</th>
            <th>Автор</th>
            <th>Описание</th>
            <th>Действие</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($themes as $id => $theme): ?>
            <tr>
                <td>
                    <strong><?= htmlspecialchars($theme['name']) ?></strong>
                    <?php if ($id === $activeThemeId): ?>
                        <span style="color: #2b8a3e; font-weight: bold; margin-left: 5px;">(Активная)</span>
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($theme['version']) ?></td>
                <td><?= htmlspecialchars($theme['author']) ?></td>
                <td><?= htmlspecialchars($theme['description']) ?></td>
                <td>
                    <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                        <!-- Кнопка Редактировать есть всегда -->
                        <a href="index.php?action=theme_edit&theme=<?= htmlspecialchars($id) ?>" style="padding: 6px 12px; background: #228be6; color: white; text-decoration: none; border-radius: 4px; font-size: 14px;">
    Редактировать
</a>

                        <?php if ($id === $activeThemeId): ?>
                            <!-- Дополнительная кнопка Посмотреть для активной темы -->
                            <a href="/" target="_blank" style="padding: 6px 12px; background: #40c057; color: white; text-decoration: none; border-radius: 4px; font-size: 14px;">
                                Посмотреть
                            </a>
                        <?php else: ?>
                            <!-- Кнопка Активации для неактивных тем -->
                            <form method="POST" style="margin: 0;">
                                <input type="hidden" name="theme_id" value="<?= htmlspecialchars($id) ?>">
                                <button type="submit" name="install_theme" style="padding: 6px 12px; background: #868e96; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 14px;">
                                    Активировать
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>