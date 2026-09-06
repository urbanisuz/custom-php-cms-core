<?php
// admin/actions/layouts.php

if (!defined('ROOT_PATH')) {
    exit;
}

$dbConfig = require ROOT_PATH . '/config.php';
$db = new \System\Database($dbConfig['db'] ?? []);

$mode = $_GET['mode'] ?? 'list';
$layout_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$message = '';
$error = '';

// Получаем список установленных модулей (для выпадающего списка привязки)
$installedModules = [];
try {
    $installedModules = $db->fetchAll("SELECT module_id FROM installed_modules");
} catch (\Throwable $e) {}

// Обработка сохранения (Создание / Редактирование макета)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $routes = $_POST['routes'] ?? [];
    $modules = $_POST['modules'] ?? [];

    if ($name === '') {
        $error = "Название макета не может быть пустым.";
    } else {
        try {
            if ($layout_id > 0) {
                // Обновление макета
                $db->query("UPDATE layouts SET name = ? WHERE layout_id = ?", [$name, $layout_id]);
                
                // Очищаем старые роуты и модули
                $db->query("DELETE FROM layout_route WHERE layout_id = ?", [$layout_id]);
                $db->query("DELETE FROM layout_module WHERE layout_id = ?", [$layout_id]);
            } else {
                // Создание макета
                $db->query("INSERT INTO layouts (name) VALUES (?)", [$name]);
                $layout_id = $db->lastInsertId();
            }

            // Сохраняем роуты
            foreach ($routes as $route) {
                $route = trim($route);
                if ($route !== '') {
                    $db->query(
                        "INSERT INTO layout_route (layout_id, store_id, route) VALUES (?, 0, ?)",
                        [$layout_id, $route]
                    );
                }
            }

            // Сохраняем модули позиций
            foreach ($modules as $mod) {
                $code = trim($mod['code'] ?? '');
                $position = trim($mod['position'] ?? '');
                $sort_order = (int)($mod['sort_order'] ?? 0);

                if ($code !== '' && $position !== '') {
                    $db->query(
                        "INSERT INTO layout_module (layout_id, code, position, sort_order) VALUES (?, ?, ?, ?)",
                        [$layout_id, $code, $position, $sort_order]
                    );
                }
            }

            $message = "Макет успешно сохранен!";
            $mode = 'list';
        } catch (\Throwable $e) {
            $error = "Ошибка сохранения: " . $e->getMessage();
        }
    }
}

// Удаление макета
if ($mode === 'delete' && $layout_id > 0) {
    try {
        $db->query("DELETE FROM layouts WHERE layout_id = ?", [$layout_id]);
        $db->query("DELETE FROM layout_route WHERE layout_id = ?", [$layout_id]);
        $db->query("DELETE FROM layout_module WHERE layout_id = ?", [$layout_id]);
        header("Location: index.php?action=layouts&msg=deleted");
        exit;
    } catch (\Throwable $e) {
        $error = "Ошибка удаления: " . $e->getMessage();
        $mode = 'list';
    }
}

if (isset($_GET['msg']) && $_GET['msg'] === 'deleted') {
    $message = "Макет успешно удален!";
}
?>

<h2>Управление макетами страниц (Layouts)</h2>
<p>Настройка привязки страниц к роутам и расположению модулей по позициям блоков.</p>

<?php if ($message): ?>
    <div style="padding: 10px; background: #d4edda; color: #155724; border-radius: 4px; margin-bottom: 15px;"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div style="padding: 10px; background: #f8d7da; color: #721c24; border-radius: 4px; margin-bottom: 15px;"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php if ($mode === 'list'): ?>
    <div style="margin-bottom: 15px;">
        <a href="index.php?action=layouts&mode=create" style="padding: 8px 14px; background: #28a745; color: white; text-decoration: none; border-radius: 4px; font-size: 14px;">+ Добавить макет</a>
    </div>

    <?php 
    $layouts = $db->fetchAll("SELECT * FROM layouts ORDER BY layout_id DESC");
    ?>
    <table border="1" cellpadding="10" cellspacing="0" style="width: 100%; border-collapse: collapse; border-color: #dee2e6; background: white;">
        <thead>
            <tr style="background: #f1f3f5; text-align: left;">
                <th>ID</th>
                <th>Название макета</th>
                <th>Привязанные роуты (маршруты)</th>
                <th style="text-align: right;">Действия</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($layouts)): ?>
                <tr><td colspan="4" style="text-align: center; color: #6c757d;">Макеты пока не созданы.</td></tr>
            <?php else: ?>
                <?php foreach ($layouts as $l): ?>
                    <?php 
                    $routes = $db->fetchAll("SELECT route FROM layout_route WHERE layout_id = ?", [$l['layout_id']]);
                    $routeList = implode(', ', array_column($routes, 'route'));
                    ?>
                    <tr>
                        <td><?= $l['layout_id'] ?></td>
                        <td><strong><?= htmlspecialchars($l['name']) ?></strong></td>
                        <td><code><?= htmlspecialchars($routeList ?: 'Не привязан') ?></code></td>
                        <td style="text-align: right;">
                            <a href="index.php?action=layouts&mode=edit&id=<?= $l['layout_id'] ?>" style="color: #007bff; text-decoration: none; margin-right: 10px;">Ред.</a>
                            <a href="index.php?action=layouts&mode=delete&id=<?= $l['layout_id'] ?>" onclick="return confirm('Точно удалить макет?');" style="color: #dc3545; text-decoration: none;">Удалить</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

<?php elseif ($mode === 'create' || $mode === 'edit'): ?>
    <?php
    $layoutName = '';
    $currentRoutes = [];
    $currentModules = [];

    if ($mode === 'edit' && $layout_id > 0) {
        $layoutData = $db->fetch("SELECT * FROM layouts WHERE layout_id = ?", [$layout_id]);
        if ($layoutData) {
            $layoutName = $layoutData['name'];
            $currentRoutes = $db->fetchAll("SELECT route FROM layout_route WHERE layout_id = ?", [$layout_id]);
            $currentModules = $db->fetchAll("SELECT * FROM layout_module WHERE layout_id = ? ORDER BY sort_order ASC", [$layout_id]);
        }
    }
    // Если роутов нет, дадим хотя бы одно пустое поле по умолчанию
    if (empty($currentRoutes)) {
        $currentRoutes = [['route' => '']];
    }
    ?>

    <form method="POST" style="background: white; padding: 20px; border: 1px solid #dee2e6; border-radius: 4px;">
        <h3><?= $mode === 'edit' ? 'Редактировать макет #' . $layout_id : 'Создать новый макет' ?></h3>
        
        <div style="margin-bottom: 20px;">
            <label style="display: block; font-weight: bold; margin-bottom: 5px;">Название макета:</label>
            <input type="text" name="name" value="<?= htmlspecialchars($layoutName) ?>" required style="width: 100%; max-width: 400px; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
        </div>

        <div style="margin-bottom: 20px;">
            <label style="display: block; font-weight: bold; margin-bottom: 5px;">Маршруты (Роуты, например: <code>common/index</code>, <code>product/category</code>):</label>
            <div id="routes-container">
                <?php foreach ($currentRoutes as $r): ?>
                    <div style="display: flex; gap: 10px; margin-bottom: 8px;">
                        <input type="text" name="routes[]" value="<?= htmlspecialchars($r['route']) ?>" placeholder="common/index" style="width: 100%; max-width: 400px; padding: 6px; border: 1px solid #ccc; border-radius: 4px;">
                        <button type="button" onclick="this.parentElement.remove()" style="background: #dc3545; color: white; border: none; padding: 6px 10px; border-radius: 4px; cursor: pointer;">Удалить</button>
                    </div>
                <?php endforeach; ?>
            </div>
            <button type="button" id="add-route-btn" style="background: #6c757d; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 13px;">+ Добавить роут</button>
        </div>

        <hr style="border: 0; border-top: 1px solid #dee2e6; margin: 20px 0;">

        <h3>Привязка модулей к позициям макета</h3>
        <p style="font-size: 13px; color: #6c757d;">Укажите, какие модули выводятся в колонках (column_left, column_right) или блоках контента (content_top, content_bottom).</p>

        <table border="1" cellpadding="8" cellspacing="0" style="width: 100%; border-collapse: collapse; border-color: #dee2e6; margin-bottom: 15px;" id="modules-table">
            <thead>
                <tr style="background: #f1f3f5; text-align: left;">
                    <th>Модуль (Код)</th>
                    <th>Позиция</th>
                    <th>Порядок (sort_order)</th>
                    <th style="width: 80px; text-align: center;">Действие</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($currentModules)): ?>
                    <?php foreach ($currentModules as $index => $m): ?>
                        <tr>
                            <td>
                                <select name="modules[<?= $index ?>][code]" style="width: 100%; padding: 6px; border: 1px solid #ccc; border-radius: 4px;">
                                    <option value="">-- Выберите модуль --</option>
                                    <?php foreach ($installedModules as $im): ?>
                                        <option value="<?= htmlspecialchars($im['module_id']) ?>" <?= $im['module_id'] === $m['code'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($im['module_id']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <select name="modules[<?= $index ?>][position]" style="width: 100%; padding: 6px; border: 1px solid #ccc; border-radius: 4px;">
                                    <option value="column_left" <?= $m['position'] === 'column_left' ? 'selected' : '' ?>>column_left (Левая колонка)</option>
                                    <option value="column_right" <?= $m['position'] === 'column_right' ? 'selected' : '' ?>>column_right (Правая колонка)</option>
                                    <option value="content_top" <?= $m['position'] === 'content_top' ? 'selected' : '' ?>>content_top (Верх контента)</option>
                                    <option value="content_bottom" <?= $m['position'] === 'content_bottom' ? 'selected' : '' ?>>content_bottom (Низ контента)</option>
                                </select>
                            </td>
                            <td>
                                <input type="number" name="modules[<?= $index ?>][sort_order]" value="<?= (int)$m['sort_order'] ?>" style="width: 80px; padding: 6px; border: 1px solid #ccc; border-radius: 4px;">
                            </td>
                            <td style="text-align: center;">
                                <button type="button" onclick="this.closest('tr').remove()" style="background: #dc3545; color: white; border: none; padding: 6px 10px; border-radius: 4px; cursor: pointer;">X</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        
        <button type="button" id="add-module-btn" style="background: #17a2b8; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 13px; margin-bottom: 20px;">+ Привязать модуль</button>

        <div style="margin-top: 20px;">
            <button type="submit" style="padding: 10px 20px; background: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 15px;">Сохранить макет</button>
            <a href="index.php?action=layouts" style="margin-left: 10px; color: #6c757d; text-decoration: none;">Отмена</a>
        </div>
    </form>

    <script>
    document.getElementById('add-route-btn').addEventListener('click', function() {
        const container = document.getElementById('routes-container');
        const div = document.createElement('div');
        div.style.cssText = 'display: flex; gap: 10px; margin-bottom: 8px;';
        div.innerHTML = '<input type="text" name="routes[]" value="" placeholder="product/category" style="width: 100%; max-width: 400px; padding: 6px; border: 1px solid #ccc; border-radius: 4px;"><button type="button" onclick="this.parentElement.remove()" style="background: #dc3545; color: white; border: none; padding: 6px 10px; border-radius: 4px; cursor: pointer;">Удалить</button>';
        container.appendChild(div);
    });

    document.getElementById('add-module-btn').addEventListener('click', function() {
        const tbody = document.querySelector('#modules-table tbody');
        const index = tbody.rows.length;
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>
                <select name="modules[${index}][code]" style="width: 100%; padding: 6px; border: 1px solid #ccc; border-radius: 4px;">
                    <option value="">-- Выберите модуль --</option>
                    <?php foreach ($installedModules as $im): ?>
                        <option value="<?= htmlspecialchars($im['module_id']) ?>"><?= htmlspecialchars($im['module_id']) ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
            <td>
                <select name="modules[${index}][position]" style="width: 100%; padding: 6px; border: 1px solid #ccc; border-radius: 4px;">
                    <option value="column_left">column_left (Левая колонка)</option>
                    <option value="column_right">column_right (Правая колонка)</option>
                    <option value="content_top">content_top (Верх контента)</option>
                    <option value="content_bottom">content_bottom (Низ контента)</option>
                </select>
            </td>
            <td>
                <input type="number" name="modules[${index}][sort_order]" value="0" style="width: 80px; padding: 6px; border: 1px solid #ccc; border-radius: 4px;">
            </td>
            <td style="text-align: center;">
                <button type="button" onclick="this.closest('tr').remove()" style="background: #dc3545; color: white; border: none; padding: 6px 10px; border-radius: 4px; cursor: pointer;">X</button>
            </td>
        `;
        tbody.appendChild(tr);
    });
    </script>
<?php endif; ?>