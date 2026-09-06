<?php
// admin/actions/modules.php
// Предполагается, что $db и сессия уже инициализированы в index.php
require_once dirname(__DIR__, 2) . '/system/ModuleManager.php';
use System\ModuleManager;

// Инициализируем соединение с БД (если еще не доступно глобально, подключаем конфиг)
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

$moduleManager = ModuleManager::getInstance();
$allModules = $moduleManager->getModules();

$message = '';
$error = '';

// Обработка POST-запроса на установку модуля
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['install_module'])) {
    $moduleId = trim($_POST['module_id'] ?? '');
    
    try {
        $moduleManager->install($moduleId, $db);
        $message = "Модуль «{$moduleId}» успешно установлен!";
    } catch (\Throwable $e) {
        $error = "Ошибка при установке модуля: " . $e->getMessage();
    }
}

// Получаем список уже установленных модулей из базы данных
$installedModulesRaw = $db->fetchAll("SELECT module_id FROM installed_modules");
$installedModules = array_column($installedModulesRaw, 'module_id');
?>

<h2>Управление модулями</h2>
<p>Здесь отображаются все модули, найденные в директории <code>/modules/</code>.</p>

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

<table style="width: 100%; border-collapse: collapse; margin-top: 20px;">
    <thead>
        <tr style="background: #f1f3f5; text-align: left;">
            <th style="padding: 10px; border-bottom: 1px solid #dee2e6;">Системное имя (ID)</th>
            <th style="padding: 10px; border-bottom: 1px solid #dee2e6;">Название</th>
            <th style="padding: 10px; border-bottom: 1px solid #dee2e6;">Версия</th>
            <th style="padding: 10px; border-bottom: 1px solid #dee2e6;">Статус</th>
            <th style="padding: 10px; border-bottom: 1px solid #dee2e6; text-align: right;">Действие</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($allModules)): ?>
            <tr>
                <td colspan="5" style="padding: 15px; text-align: center; color: #6c757d;">Модули не найдены в папке /modules/</td>
            </tr>
        <?php else: ?>
            <?php foreach ($allModules as $id => $mod): ?>
                <?php $isInstalled = in_array($id, $installedModules); ?>
                <tr>
                    <td style="padding: 10px; border-bottom: 1px solid #dee2e6;"><code><?= htmlspecialchars($id) ?></code></td>
                    <td style="padding: 10px; border-bottom: 1px solid #dee2e6;"><strong><?= htmlspecialchars($mod['name']) ?></strong></td>
                    <td style="padding: 10px; border-bottom: 1px solid #dee2e6;"><?= htmlspecialchars($mod['version']) ?></td>
                    <td style="padding: 10px; border-bottom: 1px solid #dee2e6;">
                        <?php if ($isInstalled): ?>
                            <span style="color: green; font-weight: bold;">Установлен</span>
                        <?php else: ?>
                            <span style="color: #6c757d;">Не установлен</span>
                        <?php endif; ?>
                    </td>
                    <td style="padding: 10px; border-bottom: 1px solid #dee2e6; text-align: right;">
                        <?php if (! $isInstalled): ?>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="module_id" value="<?= htmlspecialchars($id) ?>">
                                <button type="submit" name="install_module" style="background: #28a745; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer;">Установить</button>
                            </form>
                        <?php else: ?>
                            <button disabled style="background: #e9ecef; color: #adb5bd; border: none; padding: 6px 12px; border-radius: 4px; cursor: not-allowed;">Активен</button>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>