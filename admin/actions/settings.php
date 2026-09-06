<?php
// admin/actions/settings.php

require_once dirname(__DIR__, 2) . '/system/Database.php';


if (!isset($db)) {
    $config = require ROOT_PATH . '/config.php';

$dbConfig = [
    'host' => $config['db']['host'],
    'dbname' => $config['db']['dbname'],
    'username' => $config['db']['username'],
    'password' => $config['db']['password'],
];

$db = new \System\Database($dbConfig);
}

$message = '';
$error = '';

// Обработка сохранения настроек
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $settings = $_POST['settings'] ?? [];

    try {
        // Сохраняем каждую настройку через UPSERT
        foreach ($settings as $key => $value) {
            $db->query(
                "INSERT INTO theme_settings (`key`, `value`) VALUES (?, ?) 
                 ON DUPLICATE KEY UPDATE `value` = ?",
                [$key, trim($value), trim($value)]
            );
        }
        $message = 'Настройки успешно сохранены!';
    } catch (\Throwable $e) {
        $error = 'Ошибка при сохранении настроек: ' . $e->getMessage();
    }
}

// Загружаем текущие настройки из базы
$settingsRaw = [];
try {
    $settingsRaw = $db->fetchAll("SELECT * FROM theme_settings");
} catch (\Throwable $e) {
    // Таблица theme_settings может еще не существовать
}

$currentSettings = [];
foreach ($settingsRaw as $row) {
    $currentSettings[$row['key']] = $row['value'];
}
?>

<h2>Настройки сайта и темы</h2>
<p>Управление глобальными параметрами конфигурации и активным оформлением.</p>

<?php if ($message): ?>
    <div style="background: #d4edda; color: #155724; padding: 10px; border-radius: 4px; margin-bottom: 15px;">
        <?= htmlspecialchars($message) ?>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div style="background: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin-bottom: 15px;">
        <?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<form method="POST" style="background: #fff; padding: 20px; border: 1px solid #dee2e6; border-radius: 6px;">
    
    <div style="margin-bottom: 15px;">
        <label style="display: block; font-weight: bold; margin-bottom: 5px; font-size: 14px;">Название сайта (Site Title)</label>
        <input type="text" name="settings[site_title]" value="<?= htmlspecialchars($currentSettings['site_title'] ?? '') ?>" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;">
    </div>

    <div style="margin-bottom: 15px;">
        <label style="display: block; font-weight: bold; margin-bottom: 5px; font-size: 14px;">Описание сайта (Meta Description)</label>
        <textarea name="settings[site_description]" rows="3" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;"><?= htmlspecialchars($currentSettings['site_description'] ?? '') ?></textarea>
    </div>

    <div style="margin-bottom: 15px;">
        <label style="display: block; font-weight: bold; margin-bottom: 5px; font-size: 14px;">Активная тема (Theme Folder)</label>
        <input type="text" name="settings[active_theme]" value="<?= htmlspecialchars($currentSettings['active_theme'] ?? 'default') ?>" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;">
        <small style="color: #6c757d;">Название папки с темой в директории <code>/themes/</code></small>
    </div>

    <div style="margin-top: 20px;">
        <button type="submit" style="background: #28a745; color: white; border: none; padding: 10px 15px; border-radius: 4px; cursor: pointer; font-size: 14px;">Сохранить настройки</button>
    </div>
</form>